<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use AppBundle\Manager\FactureManager;
use AppBundle\Manager\DevisManager;
use AppBundle\Manager\PassageManager;
use AppBundle\Manager\ContratManager;
use AppBundle\Model\FacturableControllerTrait;
use AppBundle\Document\Facture;
use AppBundle\Document\LigneFacturable;
use AppBundle\Type\FactureType;
use AppBundle\Document\Contrat;
use AppBundle\Document\Societe;
use AppBundle\Document\Etablissement;
use AppBundle\Document\Relance;
use AppBundle\Type\FactureChoiceType;
use AppBundle\Type\SocieteChoiceType;
use AppBundle\Type\RelanceType;
use AppBundle\Type\FacturesEnRetardFiltresType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Facture controller.
 *
 * @Route("/facture")
 */
class FactureController extends Controller
{
    use FacturableControllerTrait;

    /**
     * @Route("/", name="facture")
     */
    public function indexAction(Request $request) {
        return $this->redirectToRoute('facture_mouvements');
    }

    /**
     * @Route("/previsionnel", name="facture_previsionnel")
     */
    public function previsionnelAction(Request $request)
    {
        $factureManager = $this->get('facture.manager');
        $dateLimite = new \DateTimeImmutable('+1 month');
        $facturesEnAttente = $factureManager->getRepository()->findBy(['numeroFacture' => null, 'numeroDevis' => null, 'dateFacturation' => ['$lte' => new \MongoDate($dateLimite->getTimestamp())]], ['dateFacturation' => 'desc']);

        return $this->render('facture/previsionnel.html.twig', compact('facturesEnAttente'));
    }

    /**
     * @Route("/mouvements", name="facture_mouvements")
     */
    public function mouvementsAction(Request $request)
    {
        $contratManager = $this->get('contrat.manager');
        $contratsFactureAEditer = $contratManager->getRepository()->findAllContratWithFactureAFacturer();
        $secteur = $this->getParameter('secteurs');

        $mouvementsSansPassage = array();
        $mouvements = array();

        foreach ($contratsFactureAEditer as $c) {
            foreach($c->getMouvements() as $m) {
                if (!$m->getFacturable() || $m->getFacture()) {
                    continue;
                }

                if($m->getOrigineDocumentGeneration()){
                    $mouvements[$m->getOrigineDocumentGeneration()->getDateDebut()->format('Y-m-d H:i:s').uniqid()] = $m;
                } else {
                    $mouvementsSansPassage[] = $m;
                }
            }
        }
        ksort($mouvements);

        return $this->render('facture/mouvements.html.twig', compact('mouvements', 'mouvementsSansPassage', 'secteur'));
    }

    /**
     * @Route("/devis", name="facture_devis")
     */
    public function devisAction(Request $request)
    {
        $devisManager = $this->get('devis.manager');
        $factureManager = $this->get('facture.manager');

        $factures = $factureManager->getRepository()->findBy(['numeroDevis' => ['$ne' => null]]);

        $numeros = [];
        foreach($factures as $facture) {
            $numeros[] = $facture->getNumeroDevis();
        }

        $devisAFacturer = $devisManager->getRepository('AppBundle:Devis')->findBy(['numeroDevis' => ['$nin' => $numeros], 'dateAcceptation' => ['$ne' => null]], ['datePrevision' => 'desc']);

        return $this->render('facture/devis.html.twig', compact('devisAFacturer'));
    }

    /**
     * @Route("/societe/{societe}/creation-facture/{contratId}", name="facture_creation", defaults={"contratId" = "0"})
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function creationAction(Request $request, Societe $societe, $contratId) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('configuration.manager');
        $contratManager = $this->get('contrat.manager');
        $fm = $this->get('facture.manager');

        if ($request->get('id')) {
            $facture = $fm->getRepository()->findOneById($request->get('id'));
        }

        if ($request->get('id') && !$facture) {

            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(sprintf("La facture %s n'a pas été trouvé", $request->get('id')));
        }

        $contrat = null;
        if($contratId) {
            $contrat = $contratManager->getRepository()->findOneById($contratId);
        }

        if (!isset($facture)) {
            $facture = $fm->createVierge($societe, $contrat);
            $df = ($this->container->getParameter('date_facturation'))? new \DateTime($this->container->getParameter('date_facturation')) : new \DateTime();
            $facture->setDateFacturation($df);
            $factureLigne = new LigneFacturable();
            $factureLigne->setTauxTaxe(0.2);
            $factureLigne->setQuantite(1);
            if ($contrat) {
                $factureLigne->setLibelle($contrat->getLibelleMouvement());
                if($contrat->getTvaReduite()){
                  $factureLigne->setTauxTaxe(0.1);
                }
            }
            $facture->addLigne($factureLigne);
        }

        $facture->setSociete($societe);

        if(!$facture->getId()) {
            $facture->setDateEmission(new \DateTime());
        }

        $appConf = $this->container->getParameter('application');
        if(!$facture->getCommercial()) {
            $commercial = $dm->getRepository('AppBundle:Compte')->findOneByIdentifiant($appConf['commercial']);
            if ($commercial === null) {
                throw new \LogicException("Il n'y a pas de commercial dans la config.");
            }
            $facture->setCommercial($commercial);
        }

        if (! $facture->getDateFacturation()) {
            $df = ($this->container->getParameter('date_facturation'))? new \DateTime($this->container->getParameter('date_facturation')) : new \DateTime();
            $facture->setDateFacturation($df);
        }

        $produitsSuggestion = array();
        foreach ($cm->getConfiguration()->getProduits() as $produit) {
            $produitsSuggestion[] = array("libelle" => $produit->getNom(), "conditionnement" => $produit->getConditionnement(), "identifiant" => $produit->getIdentifiant(), "prix" => $produit->getPrixVente());
        }

        $form = $this->createForm(new FactureType($dm, $cm, $appConf['commercial'], $contrat), $facture, array(
            'action' => "",
            'method' => 'POST',
        ));

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {

            return $this->render('facture/libre.html.twig', array('form' => $form->createView(), 'produitsSuggestion' => $produitsSuggestion, 'societe' => $societe, 'facture' => $facture));
        }

        $facture->update();
        if ($request->get('previsualiser')) {
            return $this->pdfAction($request, $facture->getId());
        }

        if (!$facture->getId()) {
            if($contrat){
              foreach ($facture->getLignes() as $ligne) {
                $ligne->setOrigineDocument($contrat);
              }
              $contrat->generateMouvement($facture);
            }
            $dm->persist($facture);
        } elseif ($facture->isFacture() && !$facture->getNumeroFacture() && !$contrat) {
            $fm->getRepository()->getClassMetadata()->idGenerator->generateNumeroFacture($dm, $facture);
        }

        if ($origine = $facture->getOrigineAvoir()) {
            $origine->setAvoirObject($facture);
            $origine->updateMontantPaye();
        }

        $fm->updateEmetteur($facture,$contrat);

        $dm->flush();

        return $this->redirectToRoute('facture_societe', array('id' => $societe->getId(),"etablissement_id" => $request->get('etablissement_id')));
    }

    /**
     * @Route("/societe/{societe}/facture/{id}/suppression", name="facture_suppression")
     * @ParamConverter("societe", class="AppBundle:Societe")
     * @ParamConverter("facture", class="AppBundle:Facture")
     */
    public function suppressionAction(Request $request, Societe $societe, Facture $facture) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        if (!$facture->getNumeroFacture() && $facture->getContrat() && !$facture->isAvoir()) {
            $dm->remove($facture);
            $dm->flush();
        }
        return $this->redirectToRoute('facture_societe', array('id' => $societe->getId()));
    }

    /**
     * @Route("/societe/{id}", name="facture_societe")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function societeAction(Request $request, Societe $societe) {
        $fm = $this->get('facture.manager');
        $em = $this->get('etablissement.manager');
        $formSociete = $this->createForm(SocieteChoiceType::class, array('societe' => $societe), array(
            'action' => $this->generateUrl('societe'),
            'method' => 'GET',
        ));
        $etablissements = $societe->getEtablissementsByStatut(true);
        $factures = $fm->findBySociete($societe);
        $mouvements = $fm->getMouvementsBySociete($societe);
        $hasDevis = false;
        $factureIdsEtablissement = null;

        if($request->get('etablissement_id')) {
            $factureIdsEtablissement = array();
            foreach($factures as $key => $facture) {
                if(!$facture->getContrat() || !in_array($request->get('etablissement_id'), $facture->getContrat()->getEtablissementIds())) {
                    unset($factures[$key]);
                    continue;
                }
                $factureIdsEtablissement[] = $facture->getId();
            }
            foreach($mouvements as $key => $mouvement) {
                if(!in_array($request->get('etablissement_id'), $facture->getContrat()->getEtablissementIds())) {
                    unset($mouvements[$key]);
                }
            }
        }

        $sommeMontantPayeReelParmiCeuxQuiUtiliseTropPercu = 0;
        $facturesPrevisionnel = array();

        foreach($factures as $facture) {
            if($facture->isDevis()){
                $hasDevis = true;
            }
            if (!$facture->isDevis() && !($facture->isPaye() || $facture->isAvoir() || $facture->isRedressee()) && !$facture->getNumeroFacture()) {
                $facturesPrevisionnel[] = $facture;
            }
            if($facture->getMesPaiements() and $facture->getPayeeAvecTropPercu()){
                $sommeMontantPayeReelParmiCeuxQuiUtiliseTropPercu += $facture->getMontantReelPaye();
            }
        }

        $solde = $fm->getSolde($societe, $factureIdsEtablissement);
        $totalFacture = $fm->getTotalFacture($societe, $factureIdsEtablissement);
        $totalPaye = $fm->getTotalPaye($societe, $factureIdsEtablissement);
        $resteTropPaye = $fm->getResteTropPercu($societe, $factureIdsEtablissement) + $sommeMontantPayeReelParmiCeuxQuiUtiliseTropPercu;

        $etablissement = ($request->get('etablissement_id')) ? $em->getRepository()->find($request->get('etablissement_id')) : null;
        $exportSocieteForm = $this->createExportSocieteForm($societe,$etablissement);

        $defaultDate = new \DateTime();

        if($this->container->getParameter('date_facturation')) {
            $defaultDate = new \DateTime($this->container->getParameter('date_facturation'));
        }
        $etablissement_id = $etablissement ? $etablissement->getId() : null;
        return $this->render('facture/societe.html.twig', array('societe' => $societe, 'etablissements' => $etablissements, 'mouvements' => $mouvements,'hasDevis' => $hasDevis,  'factures' => $factures, 'facturesPrevisionnel' => $facturesPrevisionnel, 'exportSocieteForm' => $exportSocieteForm->createView(), 'solde' => $solde, 'totalFacture' => $totalFacture, 'totalPaye' => $totalPaye, 'defaultDate' => $defaultDate, 'resteTropPaye' => $resteTropPaye, 'etablissement_id' => $etablissement_id));
    }

    /**
     * @Route("/societe/{id}/generation", name="facture_societe_generation")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function societeGenerationAction(Request $request, Societe $societe) {
        $fm = $this->get('facture.manager');
        $dm = $this->get('doctrine_mongodb')->getManager();

        if ($this->container->getParameter('date_facturation')) {
          $date = new \DateTime($this->container->getParameter('date_facturation'));
        } else {
          $date = \DateTime::createFromFormat('d/m/Y', $request->get('dateFacturation', date('d/m/Y')));
        }

        $mouvements = $fm->getMouvementsBySociete($societe);

        $afacturer = $request->get('afacturer', array());

        foreach($mouvements as $mouvement) {
            if (!in_array($mouvement->getIdentifiant(), $afacturer)) {
                continue;
            }
            $facture = $fm->create($societe, array($mouvement), $date);
            $facture->setDateFacturation($date);

            $contrat =  $facture->getContrat();

            if($contrat && $contrat->isBonbleu()){
              $facture->setDescription($contrat->getDescription());
            }

            $fm->updateEmetteur($facture,$contrat);

            $dm->persist($facture);
            $dm->flush();
        }

        return $this->redirectToRoute('facture_societe', array('id' => $societe->getId()));
    }

    /**
     * @Route("/cloturer/{id}/{factureId}", name="facture_cloturer")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function cloturerAction(Request $request, Societe $societe, $factureId) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $retour = ($request->get('retour', null));
        $facture = $this->get('facture.manager')->getRepository()->findOneById($factureId);
        $facture->cloturer();
        $dm->persist($facture);
        $dm->flush();

        if($request->isXmlHttpRequest()) {

            return new Response();
        }

        if($retour && ($retour == "relance")){
          return $this->redirectToRoute('factures_retard');
        }

        return $this->redirectToRoute('facture_societe', array('id' => $societe->getId(),"etablissement_id" => $request->get('etablissement_id')));
    }


    /**
     * @Route("/payer-avec-trop-percu/{id}/{factureId}", name="facture_cloturer_payer_avec_trop_percu")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function cloturerEtPayerAvecTropPercuAction(Request $request, Societe $societe, $factureId) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $retour = ($request->get('retour', null));
        $facture = $this->get('facture.manager')->getRepository()->findOneById($factureId);
        $facture->cloturer();
        $facture->payerAvecTropPercu();
        $dm->persist($facture);
        $dm->flush();

        if($request->isXmlHttpRequest()) {

            return new Response();
        }

        if($retour && ($retour == "relance")){
          return $this->redirectToRoute('factures_retard');
        }

        return $this->redirectToRoute('facture_societe', array('id' => $societe->getId(),"etablissement_id" => $request->get('etablissement_id')));
    }


    /**
     * @Route("/ajouter_aux_prelevements/{id}/{factureId}", name="ajouter_aux_prelevements")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function ajouterFactuerAuxPrelevementAction(Request $request, Societe $societe, $factureId) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $retour = ($request->get('retour', null));
        $facture = $this->get('facture.manager')->getRepository()->findOneById($factureId);
        if($facture->getSepa()->isFullAndActif()){
            $facture->setInPrelevement(null);
            $dm->persist($facture);
            $dm->flush();
        }
        return $this->redirectToRoute('facture_societe', array('id' => $societe->getId(),"etablissement_id" => $request->get('etablissement_id')));
    }


    /**
     * @Route("/facture-en-attente/{factureId}/facturer", name="facture_en_attente_facturer")
     */
    public function facturesEnAttenteAction(Request $request, $factureId) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $fm = $this->get('facture.manager');
        $facture = $fm->getRepository()->findOneById($factureId);

        if ($facture->getDateDevis() && ! $facture->getDateFacturation()) {
          $df = ($this->container->getParameter('date_facturation'))? new \DateTime($this->container->getParameter('date_facturation')) : new \DateTime();
          $facture->setDateFacturation($df);
        }

        $fm->getRepository()->getClassMetadata()->idGenerator->generateNumeroFacture($dm, $facture);
        $dm->persist($facture);
        $dm->flush();

        return $this->redirectToRoute('facture_societe', array('id' => $facture->getSociete()->getId(),"etablissement_id" => $request->get('etablissement_id')));
    }



  /**
    * @Route("/avoir/{id}/{factureId}/{mouvement}/{remboursement}", name="facture_avoir", defaults={"mouvement" = "1", "remboursement" = "0"})
   * @ParamConverter("societe", class="AppBundle:Societe")
   */
  public function avoirAction(Request $request, Societe $societe, $factureId, $mouvement, $remboursement) {
      $dm = $this->get('doctrine_mongodb')->getManager();

      $facture = $this->get('facture.manager')->getRepository()->findOneById($factureId);
      $df = ($this->container->getParameter('date_facturation'))? new \DateTime($this->container->getParameter('date_facturation')) : new \DateTime();
      $avoir = $facture->genererAvoir($df);
      if($remboursement){
        $avoir->setAvoirPartielRemboursementCheque(true);
      }
      $dm->persist($avoir);
      $dm->flush();

      $facture->setAvoir($avoir->getNumeroFacture());
      $facture->setAvoirObject($avoir);
      $facture->ajoutMontantPaye($avoir->getMontantTTC() * -1);
      $dm->persist($facture);
      $devis = $this->get('devis.manager')->getRepository()->findOneByIdentifiantFacture($factureId);
      if($devis){
        $devis->setIdentifiantFacture(null);
      }
      $dm->flush();

      if($mouvement){
        $contrat = $facture->getContrat();
        if($contrat){
          $contrat->restaureMouvements($facture);
          $dm->persist($contrat);
          $dm->flush();
        }
      }

      return $this->redirectToRoute('facture_societe', array('id' => $societe->getId(),"etablissement_id" => $request->get('etablissement_id')));
  }


    /**
     * @Route("/decloturer/{id}/{factureId}", name="facture_decloturer")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function decloturerAction(Request $request, Societe $societe, $factureId) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $facture = $this->get('facture.manager')->getRepository()->findOneById($factureId);
        $facture->decloturer();
        $dm->persist($facture);
        $dm->flush();

        return $this->redirectToRoute('facture_societe', array('id' => $societe->getId(),"etablissement_id" => $request->get('etablissement_id')));
    }

    /**
     * @Route("/facturer/{id}/{identifiant}", name="facture_defacturer")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function defacturerAction(Request $request, Contrat $contrat, $identifiant) {
        $dm = $this->get('doctrine_mongodb')->getManager();
    	$contrat->resetFacturableMouvement($identifiant);
        $dm->flush();

        return $this->redirectToRoute('facture_societe', array('id' => $contrat->getSociete()->getId(),"etablissement_id" => $request->get('etablissement_id')));
    }

    public function createExportSocieteForm(Societe $societe, Etablissement $etablissement = null) {
      $formBuilder = $this->createFormBuilder();
      $formBuilder->add('dateDebut', DateType::class, array('required' => true,
            "attr" => array('class' => 'input-inline datepicker',
                'data-provide' => 'datepicker',
                'data-date-format' => 'dd/mm/yyyy'
                ),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'label' => 'Date de début* :',
      ));
      $formBuilder->add('dateFin', DateType::class, array('required' => true,
            "attr" => array('class' => 'input-inline datepicker',
                'data-provide' => 'datepicker',
                'data-date-format' => 'dd/mm/yyyy'
                ),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'label' => 'Date de fin* :',
      ));
      $formBuilder->add('pdf', CheckboxType::class, array('label' => 'PDF', 'required' => false, 'label_attr' => array('class' => 'small')));


      $etablissementId = ($etablissement) ? $etablissement->getId() : null;
      $formBuilder->setAction($this->generateUrl('factures_export_client',array('societe' => $societe->getId(), 'etablissement'=> $etablissementId)));
      $exportForm = $formBuilder->getForm();

      return $exportForm;
    }

    /**
     * @Route("/facture/rechercher/{filter}", name="facture_search", defaults={"filter" = "0"})
     */
    public function factureSearchAction(Request $request, $filter) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $response = new Response();
        $facturesResult = array();
        $this->contructSearchResult($dm->getRepository('AppBundle:Facture')->findByTerms($request->get('term'),$filter), $facturesResult);
        $data = json_encode($facturesResult);
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($data);
        return $response;
    }

    /**
     * @Route("/facture/all/rechercher/{filter}", name="all_facture_search", defaults={"filter" = "0"})
     */
    public function allFactureSearchAction(Request $request, $filter) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $response = new Response();
        $facturesResult = array();
        $this->contructSearchResult($dm->getRepository('AppBundle:Facture')->findByTerms($request->get('term'),$filter, true), $facturesResult);
        $data = json_encode($facturesResult);
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($data);
        return $response;
    }

    public function contructSearchResult($criterias, &$result) {

        foreach ($criterias as $id => $nom) {
            $newResult = new \stdClass();
            $newResult->id = $id;
            $newResult->text = $nom;
            $result[] = $newResult;
        }
    }

    /**
     * @Route("/facture/export", name="factures_export")
     */
    public function exportComptableAction(Request $request) {
        ini_set('memory_limit', '-1');
      // $response = new StreamedResponse();
        $formRequest = $request->request->get('form');

        $dateDebutString = $formRequest['dateDebut']." 00:00:00";
        $dateFinString = $formRequest['dateFin']." 23:59:59";

        $dateDebut = \DateTime::createFromFormat('d/m/Y H:i:s',$dateDebutString);
        $dateFin = \DateTime::createFromFormat('d/m/Y H:i:s',$dateFinString);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $fm = $this->get('facture.manager');
        $facturesForCsv = $fm->getFacturesForCsv($dateDebut,$dateFin);

        $filename = sprintf("export_factures_du_%s_au_%s.csv", $dateDebut->format("Y-m-d"),$dateFin->format("Y-m-d"));
        $handle = fopen('php://memory', 'r+');

        foreach ($facturesForCsv as $paiement) {
            fputcsv($handle, $paiement,';');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $response = new Response(utf8_decode($content), 200, array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ));
        $response->setCharset('UTF-8');

        return $response;
    }

    /**
     * @Route("/facture/export_factures_en_retard", name="factures_en_retard_export")
     */
    public function exportFactureEnRetardAction(Request $request) {
        ini_set('memory_limit', '-1');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $fm = $this->get('facture.manager');

        $facturesForCsv = $fm->getRetardDePaiementCSV();

        $filename = sprintf("export_factures_en_retard.csv");
        $handle = fopen('php://memory', 'r+');

        foreach ($facturesForCsv as $paiement) {
            fputcsv($handle, $paiement,';');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $response = new Response(utf8_decode($content), 200, array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ));
        $response->setCharset('UTF-8');

        return $response;
    }

    /**
     * @Route("/prelevements/export", name="factures_prelevements")
     */
    public function exportPrelevementsAction(Request $request) {
    	ini_set('memory_limit', '-1');

    	$dm = $this->get('doctrine_mongodb')->getManager();
    	$fm = $this->get('facture.manager');

    	$facturesForCsv = $fm->getFacturesPrelevementsForCsv();

    	var_dump(count($facturesForCsv));exit;
    }



        /**
         * @Route("/facture/export-detailca", name="detailca_export")
         */
    	public function exportDetailCaAction(Request $request) {
            ini_set('memory_limit', '256M');
        	// $response = new StreamedResponse();
        	$formRequest = $request->request->get('form');
            $commercial = (isset($formRequest['commercial']) && $formRequest['commercial'] && ($formRequest['commercial']!= ""))?
            $formRequest['commercial'] : null;

        	$pdf = (isset($formRequest["pdf"]) && $formRequest["pdf"]);

        	$dateDebutString = $formRequest['dateDebut']." 00:00:00";
        	$dateFinString = $formRequest['dateFin']." 23:59:59";

        	$dateDebut = \DateTime::createFromFormat('d/m/Y H:i:s',$dateDebutString);
        	$dateFin = \DateTime::createFromFormat('d/m/Y H:i:s',$dateFinString);

        	$fm = $this->get('facture.manager');

        	$detailCaFromFactures = $fm->getDetailCaFromFactures($dateDebut,$dateFin,$commercial);


        	if(!$pdf){
        		$filename = sprintf("export_details_chiffre_affaire_du_%s_au_%s.csv", $dateDebut->format("Y-m-d"),$dateFin->format("Y-m-d"));
        		$handle = fopen('php://memory', 'r+');

        		foreach ($detailCaFromFactures as $stat) {
        			fputcsv($handle, $stat,';');
        		}

        		rewind($handle);
        		$content = stream_get_contents($handle);
        		fclose($handle);

        		$response = new Response(utf8_decode($content), 200, array(
        				'Content-Type' => 'text/csv',
        				'Content-Disposition' => 'attachment; filename=' . $filename,
        		));
        		$response->setCharset('UTF-8');

        		return $response;
        	}else{
        		$html = $this->renderView('contrat/pdfStatsCommerciaux.html.twig', array(
        				'statsForCommerciaux' => $detailCaFromFactures,
        				'dateDebut' => $dateDebut,
        				'dateFin' => $dateFin
        		));


        		$filename = sprintf("export_stats_rentabilite_du_%s_au_%s.pdf",  $dateDebut->format("Y-m-d"), $dateFin->format("Y-m-d"));

        		if ($request->get('output') == 'html') {

        			return new Response($html, 200);
        		}

        		return new Response(
        				$this->get('knp_snappy.pdf')->getOutputFromHtml($html, array(
        						'disable-smart-shrinking' => null,
        						'encoding' => 'utf-8',
        						'margin-left' => 10,
        						'margin-right' => 10,
        						'margin-top' => 10,
        						'margin-bottom' => 10),$this->getPdfGenerationOptions()), 200, array(
        								'Content-Type' => 'application/pdf',
        								'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        						));
        	}
        }

        /**
         * @Route("/facture/export-detailcapresta", name="detailcapresta_export")
         */
      public function exportDetailCaPrestaAction(Request $request) {
            ini_set('memory_limit', '256M');
          // $response = new StreamedResponse();
          $formRequest = $request->request->get('form');
            $prestation = (isset($formRequest['prestation']) && $formRequest['prestation'] && ($formRequest['prestation']!= ""))?
            $formRequest['prestation'] : null;

          $pdf = (isset($formRequest["pdf"]) && $formRequest["pdf"]);

          $dateDebutString = $formRequest['dateDebut']." 00:00:00";
          $dateFinString = $formRequest['dateFin']." 23:59:59";

          $dateDebut = \DateTime::createFromFormat('d/m/Y H:i:s',$dateDebutString);
          $dateFin = \DateTime::createFromFormat('d/m/Y H:i:s',$dateFinString);

          $fm = $this->get('facture.manager');

          $detailCaFromFactures = $fm->getDetailCaFromFacturesByPrestation($dateDebut,$dateFin,$prestation);


          if(!$pdf){
            $filename = sprintf("export_details_chiffre_affaire_par_prestation_du_%s_au_%s.csv", $dateDebut->format("Y-m-d"),$dateFin->format("Y-m-d"));
            $handle = fopen('php://memory', 'r+');

            foreach ($detailCaFromFactures as $stat) {
              fputcsv($handle, $stat,';');
            }

            rewind($handle);
            $content = stream_get_contents($handle);
            fclose($handle);

            $response = new Response(utf8_decode($content), 200, array(
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=' . $filename,
            ));
            $response->setCharset('UTF-8');

            return $response;
          }else{
            $html = $this->renderView('contrat/pdfStatsCommerciaux.html.twig', array(
                'statsForCommerciaux' => $detailCaFromFactures,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin
            ));


            $filename = sprintf("export_stats_rentabilite_du_%s_au_%s.pdf",  $dateDebut->format("Y-m-d"), $dateFin->format("Y-m-d"));

            if ($request->get('output') == 'html') {

              return new Response($html, 200);
            }

            return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($html, array(
                    'disable-smart-shrinking' => null,
                    'encoding' => 'utf-8',
                    'margin-left' => 10,
                    'margin-right' => 10,
                    'margin-top' => 10,
                    'margin-bottom' => 10),$this->getPdfGenerationOptions()), 200, array(
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                    ));
          }
        }

    /**
     * @Route("/facture/export-client/{societe}/{etablissement} ", name="factures_export_client", defaults={"etablissement" = null})
     * @ParamConverter("societe", class="AppBundle:Societe")

     */
    public function exportFactureClientAction(Request $request, Societe $societe) {
        $etablissementId = $request->get('etablissement');
        $etablissement = null;

        if($etablissementId){
            $em = $this->get('etablissement.manager');
            $etablissement = $em->getRepository()->find($etablissementId);
        }

        $formRequest = $request->request->get('form');

        $dateDebutString = $formRequest['dateDebut']." 00:00:00";
        $dateFinString = $formRequest['dateFin']." 23:59:59";

        $dateDebut = \DateTime::createFromFormat('d/m/Y H:i:s',$dateDebutString);
        $dateFin = \DateTime::createFromFormat('d/m/Y H:i:s',$dateFinString);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $fm = $this->get('facture.manager');

        $facturesForCsv = $fm->getFacturesSocieteForCsv($societe, $dateDebut,$dateFin,$etablissement);

        if($etablissement){
            foreach($facturesForCsv as $key => $factureObj) {
                $factureObj = $factureObj->facture;
                if(!$factureObj){
                    continue;
                }
                if(!$factureObj->getContrat() || !in_array($etablissement->getId(), $factureObj->getContrat()->getEtablissementIds())) {
                    unset($facturesForCsv[$key]);
                    continue;
                }
            }
        }

        $pdf = (isset($formRequest["pdf"]) && $formRequest["pdf"]);

        $nom = ($etablissement && $etablissement->getLibelleComplet()) ? str_replace(array("'"," ",'"'),array('','',''),$etablissement->getLibelleComplet()) : str_replace(array("'"," ",'"'),array('','',''),$societe->getRaisonSociale());

        if(!$pdf){
          $filename = sprintf("export_%s_factures_du_%s_au_%s.csv",$nom, $dateDebut->format("Y-m-d"),$dateFin->format("Y-m-d"));
          $handle = fopen('php://memory', 'r+');

          foreach ($facturesForCsv as $factureObj) {
              fputcsv($handle, $factureObj->row,';');
          }

          rewind($handle);
          $content = stream_get_contents($handle);
          fclose($handle);

          $response = new Response(utf8_decode($content), 200, array(
              'Content-Type' => 'text/csv',
              'Content-Disposition' => 'attachment; filename=' . $filename,
          ));
          $response->setCharset('UTF-8');

          return $response;
      }else{

          $nbPages = 0;
          $facturesForPdf = array();
          $currentPage = 0;
          $cpt = 0;
          foreach ($facturesForCsv as $key => $factureObj) {
            if($cpt > 27){
              $currentPage++;
              $cpt = 0;
            }
            if(!array_key_exists($currentPage,$facturesForPdf)){
              $facturesForPdf[$currentPage] = array();
            }
              $facturesForPdf[$currentPage][$key] = $factureObj;
            if($factureObj->facture){
              if($factureObj->facture->getAvoirPartielRemboursementCheque()){
                $cpt+=2;
              }else{
                $cpt++;
              }
            }
          }

          $html = $this->renderView('facture/pdfSociete.html.twig', array(
            'societe' => $societe,
            'facturesForPdf' => $facturesForPdf,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'parameters' => $fm->getParameters()
        ));


        $filename = sprintf("export_%s_factures_du_%s_au_%s.pdf",$nom, $dateDebut->format("Y-m-d"),$dateFin->format("Y-m-d"));

        if ($request->get('output') == 'html') {

            return new Response($html, 200);
         }

        return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($html, array(
                  'disable-smart-shrinking' => null,
                   'encoding' => 'utf-8',
                    'margin-left' => 10,
                    'margin-right' => 10,
                    'margin-top' => 10,
                    'margin-bottom' => 10),$this->getPdfGenerationOptions()), 200, array(
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
          ));
      }
    }

    public function exportStatsForDates($dateDebutString, $dateFinString, $dateDebut, $dateFin, $commercialFiltre = null){

      $dateDebutFirstOfMonth = \DateTime::createFromFormat('d/m/Y H:i:s', $dateDebutString);
      $dateDebutFirstOfMonth->modify('first day of this month');
      $dateFinFirstOfMonth = \DateTime::createFromFormat('d/m/Y H:i:s', $dateFinString);
      $dateFinFirstOfMonth->modify('last day of this month');

      $interval = \DateInterval::createFromDateString('1 month');
      $period = new \DatePeriod($dateDebutFirstOfMonth, $interval, $dateFinFirstOfMonth);

      $arrayOfDates = array();
      $cpt = 0;
      $nbPeriod = count(iterator_to_array($period));
      foreach ($period as $dt) {
          $arrayOfDates[$dt->format("Y-m")] = array();
          $firstDay = clone $dt;
          $lastDay = clone $dt;
          $arrayOfDates[$dt->format("Y-m")]['dateDebut'] = $firstDay->modify('first day of this month');
          $arrayOfDates[$dt->format("Y-m")]['dateFin'] = $lastDay->modify('last day of this month +23 hours +59 minutes +59 seconds');
        if(!$cpt){
          $arrayOfDates[$dt->format("Y-m")]['dateDebut'] = $dateDebut;
        }
        $cpt++;
        if($cpt == $nbPeriod){
          $arrayOfDates[$dt->format("Y-m")]['dateFin'] = $dateFin;
        }
      }

      $dm = $this->get('doctrine_mongodb')->getManager();
      $fm = $this->get('facture.manager');

      $completeArray = array();

      $totalArray = array();
      foreach ($arrayOfDates as $dates) {
          $facturesStatsForCsv = $fm->getStatsForCsv($dates['dateDebut'],$dates['dateFin'], $commercialFiltre);

          foreach ($facturesStatsForCsv as $rowId => $paiement) {
            if($rowId == "ENTETE_TITRE"){
              $totalArray[$rowId] = array();
              $totalArray[$rowId][] = array();
            }elseif($rowId == "ENTETE"){
              foreach (FactureManager::$export_stats_libelle as $key => $entete) {
                  $totalArray[$rowId][$key] = str_replace('{X}', 'Année courante',$entete);
                  $totalArray[$rowId][$key] = str_replace('{X-1}', 'Année préc.',$totalArray[$rowId][$key]);
              }
            }
            else{
              if(!array_key_exists($rowId,$totalArray)){
                $totalArray[$rowId] = array();
                $totalArray[$rowId] = $paiement;
              }else{
                foreach ($paiement as $key => $value) {
                  if(!$key){
                    $totalArray[$rowId][$key] = $value;
                  }else{
                    $floatValue = floatval(str_replace(array(' ',','),array('','.'),$value));
                    $oldArrayValue = floatval(str_replace(array(' ',','),array('','.'),$totalArray[$rowId][$key]));
                    $totalArray[$rowId][$key] = $oldArrayValue + $floatValue;
                  }
                }
              }
            }
            $completeArray[] = $paiement;
          }
          $completeArray[] = array("","","","","","","","","","");
      }

      $totalArray["ENTETE_TITRE"][0] = sprintf("TOTAL des statistiques du %s au %s", $dateDebut->format("d/m/Y"), $dateFin->format("d/m/Y"));

      if(count($arrayOfDates) > 1){
        foreach ($totalArray as $rowId => $paiement) {
          $tmpArray = array();
          foreach ($paiement as $key => $paiementVal) {
            if(is_numeric($paiementVal)){
              $tmpArray[$key] = number_format($paiementVal, 2, ',', '');
            }else{
              $tmpArray[$key] = $paiementVal;
            }
          }
            $completeArray[] = $tmpArray;
        }
        $completeArray[] =  array("","","","","","","","","","");
      }

      return $completeArray;
    }

    /**
     * @Route("/stats/export", name="stats_export")
     */
    public function exportStatsAction(Request $request) {

      // $response = new StreamedResponse();
        ini_set('memory_limit', '-1');
        $formRequest = $request->request->get('form');
        $pdf = (isset($formRequest["pdf"]) && $formRequest["pdf"]);

        $dateDebutString = $formRequest['dateDebut']."00:00:00";
        $dateFinString = $formRequest['dateFin']."23:59:59";

        $dateDebut = \DateTime::createFromFormat('d/m/Y H:i:s',$dateDebutString);
        $dateFin = \DateTime::createFromFormat('d/m/Y H:i:s',$dateFinString);

        $exportStatsArray = $this->exportStatsForDates($dateDebutString,$dateFinString,$dateDebut,$dateFin, ($formRequest['commercial']) ? $formRequest['commercial'] : null);

        if(!$pdf){
          $handle = fopen('php://memory', 'r+');
          $filename = sprintf("export_stats_du_%s_au_%s.csv", $dateDebut->format("Y-m-d"), $dateFin->format("Y-m-d"));
          foreach ($exportStatsArray as $paiement) {
              fputcsv($handle, $paiement,';');
            }
          fputcsv($handle, array("",""),';');
          rewind($handle);
          $content = stream_get_contents($handle);
          fclose($handle);
          $response = new Response(utf8_decode($content), 200, array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ));
        $response->setCharset('UTF-8');

        return $response;
      }else{
          $html = $this->renderView('facture/pdfStats.html.twig', array(
            'exportStatsArray' => $exportStatsArray,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ));


        $filename = sprintf("export_stats_du_%s_au_%s.pdf",  $dateDebut->format("Y-m-d"), $dateFin->format("Y-m-d"));

        if ($request->get('output') == 'html') {

            return new Response($html, 200);
         }

        return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($html, array(
                  'disable-smart-shrinking' => null,
                   'encoding' => 'utf-8',
                    'margin-left' => 10,
                    'margin-right' => 10,
                    'margin-top' => 10,
                    'margin-bottom' => 10,
                    'orientation' => 'landscape'),$this->getPdfGenerationOptions()), 200, array(
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
          ));
      }
    }

    /**
     * @Route("/retards-de-facture-societe/{id}", name="factures_retard_societe")
      * @ParamConverter("societe", class="AppBundle:Societe")
     */

     public function retardsSocieteAction(Request $request, Societe $societe) {
       $societe = $societe->getId();
       return $this->retardsFilters($request, $societe);
     }


    /**
     * @Route("/retards-de-facture", name="factures_retard")
     */
    public function retardsAction(Request $request) {
        if($request->get('secteur')) { // <-- requete http
            $response = new RedirectResponse($this->generateUrl('factures_retard'));
            $response->headers->setCookie(new Cookie('secteurZone', $request->get('secteur'), time() + (365 * 24 * 60 * 60)));

            return $response;
        }

        return $this->retardsFilters($request);

    }

    private function retardsFilters($request, $societe = null, $route = 'factures_retard'){
        $secteur = null;

        if($this->getParameter('secteurs')) { // <-- CONFIG parameters.yml
            $secteur = $request->cookies->get('secteurZone', 'PARIS');
        }
      $dm = $this->get('doctrine_mongodb')->getManager();
      $fm = $this->get('facture.manager');
      $sm = $this->get('societe.manager');

      $pdf = $request->get('pdf',null);

      $dateFactureBasse = null;
      $dateFactureHaute = null;
      $nbRelances = null;
      $dateMois = null;

      $formFacturesEnRetard = $this->createForm(new FacturesEnRetardFiltresType($this->container, $this->get('doctrine_mongodb')->getManager(),$societe), null, array(
          'action' => $this->generateUrl('factures_retard'),
          'method' => 'GET',
      ));

      $formFacturesEnRetard->handleRequest($request);
      if ($formFacturesEnRetard->isSubmitted() && $formFacturesEnRetard->isValid()) {
        $formValues =  $formFacturesEnRetard->getData();
        $dateFactureBasse = $formValues["dateFactureBasse"];
        $dateFactureHaute = $formValues["dateFactureHaute"] ? $formValues["dateFactureHaute"]->add(new \DateInterval('PT23H59M')) : null;
        $dateMois = $formValues["dateMois"];
        $nbRelances = intval($formValues["nbRelances"]) -1;
        $societe = $formValues["societe"];
      }
      $facturesEnRetard = $fm->getRepository()->findFactureRetardDePaiement($dateFactureBasse, $dateFactureHaute, $nbRelances, $societe, $secteur,$dateMois, $this->getParameter("commercial_seine_et_marne"));
      $formRelance = $this->createForm(new RelanceType($facturesEnRetard), null, array(
          'action' => $this->generateUrl('factures_relance_massive'),
          'method' => 'post',
      ));

      $arrayFacturesBySociete = array();
      foreach($facturesEnRetard as $f){
          $arrayFacturesBySociete[$f->getSociete()->getId()][] = $f->getId();
      }

      $tabNbFacturesBySociete = array();
      foreach($arrayFacturesBySociete as $k=>$v){
         $tabNbFacturesBySociete[$k] = count($arrayFacturesBySociete[$k]);
      }

      return $this->render('facture/retardPaiements.html.twig', array('facturesEnRetard' => $facturesEnRetard, "formRelance" => $formRelance->createView(), 'nbRelances' => $nbRelances, 'pdf' => $pdf,
      'formFacturesARelancer' => $formFacturesEnRetard->createView(), 'tabNbFacturesBySociete'=> $tabNbFacturesBySociete, 'secteur' => $secteur, 'etablissementManager' => $this->get('etablissement.manager')));
    }


    /**
     * @Route("/relance-massive", name="factures_relance_massive")
     */
    public function relanceMassiveAction(Request $request) {

      set_time_limit(0);
      $dm = $this->get('doctrine_mongodb')->getManager();
      $fm = $this->get('facture.manager');
      $factureARelancer = array();
      $formRequest = $request->request->get('relance');


      foreach ($formRequest as $key => $value) {
        if(preg_match("/^FACTURE-/",$key)){
            $factureARelancer[$key] = $fm->getRepository()->findOneById($key);
        }
      }

      $lignesFacturesRelancees = array();

      foreach ($factureARelancer as $facture) {

          $lignesFacturesRelancees[$facture->getId()] = array();
          foreach($facture->getLignes() as $key => $ligneFacture) {

              $ligne = $this->buildLignePDFFacture($ligneFacture);
              $lignesFacturesRelancees[$facture->getId()][] = $ligne;
          }

          if($facture->getNbRelance() > 2) {
              continue;
          }
          $nbRelance = intval($facture->getNbRelance()) + 1;
          $facture->setNbRelance($nbRelance);
          $dm->flush();
          $relance = new Relance();
          $relance->setDateRelance(new \DateTime());
          $relance->setNumeroRelance($nbRelance);
          $facture->addRelance($relance);
          $dm->flush();
      }


      $html = $this->renderView('facture/pdfRelanceMassive.html.twig', array(
          'facturesRelancees' => $factureARelancer,
          'lignesFacturesRelancees' => $lignesFacturesRelancees,

          'parameters' => $fm->getParameters()
      ));


      $filename = sprintf("relances_massives_%s_%s.pdf", (new \DateTime())->format("Y-m-d_His") , uniqid() );

      if ($request->get('output') == 'html') {

          return new Response($html, 200);
      }
      $path = './pdf/relances/';
      $this->get('knp_snappy.pdf')->generateFromHtml($html, $path.$filename);
      return $this->redirectToRoute('factures_retard',array('pdf' => $filename));

    }

    /**
     * @Route("/relance-pdf/{id}/{numeroRelance}", name="facture_relance_pdf")
     * @ParamConverter("facture", class="AppBundle:Facture")
     */
    public function relancePdfAction(Request $request, Facture $facture, $numeroRelance = 0 ) {

      set_time_limit(0);
      $fm = $this->get('facture.manager');

      $lignes = array();
      foreach($facture->getLignes() as $key => $ligneFacture) {

          $ligne = $this->buildLignePDFFacture($ligneFacture);
          $lignes[] = $ligne;
      }

      $relance = $facture->getRelanceObjNumero($numeroRelance);
      $fileDate = (new \DateTime())->format("Y-m-d_His");
      if($relance){
        $fileDate = $relance->getDateRelance()->format("Y-m-d_His");
      }
      $html = $this->renderView('facture/pdfGeneriqueRelance.html.twig', array(
          'facture' => $facture,
          'lignes' => $lignes,
          'numeroRelance' => $numeroRelance,
          'relance' => $relance,
          'parameters' => $fm->getParameters()
      ));

      $terme_relance = FactureManager::$types_nb_relance[$numeroRelance];

      $filename = sprintf("relance_%s_facture_%s_%s.pdf",$terme_relance, $facture->getNumeroFacture(), $fileDate);

      if ($request->get('output') == 'html') {

          return new Response($html, 200);
      }

      return new Response(
              $this->get('knp_snappy.pdf')->getOutputFromHtml($html, $this->getPdfGenerationOptions()), 200, array(
          'Content-Type' => 'application/pdf',
          'Content-Disposition' => 'attachment; filename="' . $filename . '"'
              )
      );

    }

    /**
     * @Route("/relance-commentaire/{id}", name="facture_relance_commentaire_add")
     * @ParamConverter("facture", class="AppBundle:Facture")
     */
    public function relanceCommentaireAction(Request $request, Facture $facture) {

      if (!$request->isXmlHttpRequest()) {
          throw $this->createNotFoundException();
      }

      $dm = $this->get('doctrine_mongodb')->getManager();
      if ($facture) {
          try {
              $facture->setRelanceCommentaire($request->get('value'));
              $dm->persist($facture);
              $dm->flush();
              return new Response(json_encode(array("success" => true)));
          } catch (\Exception $e) {

          }
      }

      throw new \Exception('Une erreur s\'est produite');
      }


      /**
       * @Route("/passage/relance-email/{id}", name="relance_email")
       * @ParamConverter("Facture", class="AppBundle:Facture")
       */
      public function relanceEmailAction(Request $request, Facture $facture){
        $response = new Response();

        $fm = $this->get('facture.manager');

        $parameters = $fm->getParameters();

        $fromEmail = $parameters['coordonnees']['email'];
        $fromName = $parameters['coordonnees']['nom'];
        $subject = "FACTURE NON PAYEE ( FACTURE n°".$facture->getNumeroFacture()." de ".$facture->getMontantTTC()." € )";

        $email_footer = $this->container->getParameter('email_footer');

        $commercial_SEINE_ET_MARNE = ($this->container->getParameter("commercial_seine_et_marne")) ? $this->container->getParameter("commercial_seine_et_marne") : null;

        if(($facture->getCommercial() && $facture->getCommercial()->getNom() == $commercial_SEINE_ET_MARNE) or ($facture->getContrat() && $facture->getContrat()->getZone() == ContratManager::ZONE_SEINE_ET_MARNE) or ($facture->getContrat() && $facture->getContrat()->getCommercial() && $facture->getContrat()->getCommercial()->getNom() == $commercial_SEINE_ET_MARNE)){
            $email_footer = $this->container->getParameter('email_footer_SEINE_ET_MARNE');
        }

        if( !$facture->getNbRelance()){
            $body = $this->render('facture/mailPremiereRelance.html.twig', ['facture' => $facture, 'dateLimite' => date('d/m/Y', strtotime(' + 10 days')),'email_footer' => $email_footer])->getContent();
        }
        else{
            $body = $this->render('facture/mailDeuxiemeRelance.html.twig', ['facture' => $facture, 'dateLimite' => date('d/m/Y', strtotime(' + 8 days')),'email_footer' => $email_footer])->getContent();
        }

        if($facture->getSociete()->getContactCoordonnee()->getEmailFacturation()){
          $toEmail = $facture->getSociete()->getContactCoordonnee()->getEmailFacturation();
        }
        elseif($facture->getSociete()->getContactCoordonnee()->getEmail()) {
          $toEmail = $facture->getSociete()->getContactCoordonnee()->getEmail();
        }
        else{
          var_dump('NO mailer config');
          $request->getSession()->getFlashBag()->add('notice', 'success');
          $referer = $request->headers->get('referer');
          return $this->redirect($referer);
        }

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom(array($fromEmail => $fromName))
            ->setTo(explode(";", $toEmail))
            ->setBody($body,'text/plain')
            ->setReadReceiptTo($fromEmail);

            $pdf = $this->createPdfFacture($request,$facture->getId());
            $namePdf = "FACTURE-".$facture->getNumeroFacture().".pdf";
            $attachment = \Swift_Attachment::newInstance($pdf,$namePdf,'application/pdf');
            $message->attach($attachment);



        try {
            $this->get('mailer')->send($message);
            $dm = $this->get('doctrine_mongodb')->getManager();

            if(!$facture->getNbRelance()){
                $facture->setNbRelance(1);
            }
            else{
                $facture->setNbRelance(2);
            }
            $dm->flush();
            $relance = new Relance();
            $relance->setDateRelance(new \DateTime());
            $relance->setNumeroRelance($facture->getNbRelance());
            $facture->addRelance($relance);

            $commentaire = $facture->getRelanceCommentaire();
            $dm->flush();
        }
        catch(Exception $e) {
            var_dump('NO mailer config');
            $request->getSession()->getFlashBag()->add('notice', 'success');
            $referer = $request->headers->get('referer');
            return $this->redirect($referer);
        }

        $request->getSession()->getFlashBag()->add('notice', 'success');
        $referer = $request->headers->get('referer');

        $response->headers->set('Content-Type', 'text/plain');
        $response->setContent($commentaire);
        return $response;

      }

        /**
        * @Route("/passage/email_facture/{id}", name="email_facture")
        * @ParamConverter("Facture", class="AppBundle:Facture")
        */
        public function factureEmailAction(Request $request, Facture $facture){
          $fm = $this->get('facture.manager');
          $parameters = $fm->getParameters();

          $fromEmail = $parameters['coordonnees']['email'];
          $fromName = $parameters['coordonnees']['nom'];
          $replyEmail = $parameters['coordonnees']['replyEmail'];
          $prefix_subject =  $parameters['coordonnees']['prefix_objet'];

          $email_footer = $this->container->getParameter('email_footer');

          $commercial_SEINE_ET_MARNE = ($this->container->getParameter("commercial_seine_et_marne")) ? $this->container->getParameter("commercial_seine_et_marne") : null;
          if(($facture->getCommercial() && $facture->getCommercial()->getNom() == $commercial_SEINE_ET_MARNE) or ($facture->getContrat() && $facture->getContrat()->getCommercial()->getNom() == $commercial_SEINE_ET_MARNE )or ($facture->getContrat() && $facture->getContrat()->getZone() == ContratManager::ZONE_SEINE_ET_MARNE)){
              $email_footer = $this->container->getParameter('email_footer_SEINE_ET_MARNE');
          }

          $subject = $prefix_subject." Facture n°".$facture->getNumeroFacture();
          if($facture->isAvoir()){
            $body = $this->render('facture/mailAvoir.html.twig', ['facture' => $facture,'email_footer' => $email_footer])->getContent();
          }
          else{
            $body = $this->render('facture/mailFacture.html.twig', ['facture' => $facture,'email_footer' => $email_footer])->getContent();
          }

          $emailFacturationEtablissement = array();

          foreach($facture->getContrat()->getEtablissements() as $etablissement) {
              if($etablissement->getContactCoordonnee()->getEmailFacturation()){
                  foreach(explode(";", $etablissement->getContactCoordonnee()->getEmailFacturation()) as $email){
                      $emailFacturationEtablissement[] = $email;
                  }
              }
          }

          $emailFacturationSociete = $facture->getSociete()->getContactCoordonnee()->getEmailFacturation()
            ? explode(";", $facture->getSociete()->getContactCoordonnee()->getEmailFacturation())
            : $facture->getSociete()->getContactCoordonnee()->getEmail();

          $toEmail = $emailFacturationSociete;

          if(empty($emailFacturationEtablissement) === false){
              $toEmail = array_unique(array_merge($emailFacturationSociete, $emailFacturationEtablissement));
          }
          else{
            var_dump('NO mailer config');
            $request->getSession()->getFlashBag()->add('notice', 'success');
            $referer = $request->headers->get('referer');
            return $this->redirect($referer);
          }

          $message = \Swift_Message::newInstance()
              ->setSubject($subject)
              ->setFrom(array($fromEmail => $fromName))
              ->setTo($toEmail)
              ->setReplyTo($replyEmail)
              ->setBody($body,'text/plain')
              ->setReadReceiptTo($fromEmail);

          $pdf = $this->createPdfFacture($request,$facture->getId());
          $namePdf = "FACTURE-".$facture->getNumeroFacture().".pdf";
          $attachment = \Swift_Attachment::newInstance($pdf,$namePdf,'application/pdf');
          $message->attach($attachment);

          try {
              $this->get('mailer')->send($message);
              $dm = $this->get('doctrine_mongodb')->getManager();
              $facture->setDateEnvoiMail(new \DateTime());
              $dm->flush();
          }
          catch(Exception $e) {
              var_dump('NO mailer config');
          }
          $request->getSession()->getFlashBag()->add('notice', 'success');
          $referer = $request->headers->get('referer');

          return $this->redirect($referer);
        }


        /**
        * @Route("/mouvementsPouvantEtreFactures", name="mouvementsPouvantEtreFactures")
        */
        public function ListMouvementsPouvantEtreFacturesAction(Request $request){
            $secteur = $this->getParameter('secteurs');
            $dm = $this->get('doctrine_mongodb')->getManager();
            $contratManager = $this->get('contrat.manager');
            $contratsFactureAEditer = $contratManager->getRepository()->findAllContratWithFactureAFacturer();
            $mouvements = array();
            foreach ($contratsFactureAEditer as $c) {
                foreach($c->getMouvements() as $m){
                    $mouvements[$m->getOrigineDocumentGeneration()->getDateDebut()->format('Y-m-d H:i:s')] = $m;
                }
            }
            ksort($mouvements);
            return $this->render('facture/listMouvementsPouvantEtreFacturesAction.html.twig',array('mouvements'=>$mouvements, 'secteur'=>$secteur));
        }
}
