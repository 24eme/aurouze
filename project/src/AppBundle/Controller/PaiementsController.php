<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Manager\PaiementsManager;
use AppBundle\Type\PaiementsType;
use AppBundle\Type\SocieteCommentaireType;
use AppBundle\Type\RelanceType;
use AppBundle\Type\FacturesEnRetardFiltresType;
use AppBundle\Document\Paiements;
use AppBundle\Document\Paiement;
use AppBundle\Document\Societe;
use AppBundle\Tool\PrelevementXml;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;

class PaiementsController extends Controller {

    /**
     * @Route("/paiements/liste", name="paiements_liste")
     */
    public function indexAction(Request $request) {
    	$periode = ($request->get('periode'))? $request->get('periode') : date('m/Y');

        $paiementsDocs = $this->get('paiements.manager')->getRepository()->findByPeriode($periode);
        $paiementsDocsPrelevement = $this->get('paiements.manager')->getRepository()->findByPeriode($periode,true);
        $dm = $this->get('doctrine_mongodb')->getManager();

        return $this->render('paiements/index.html.twig', array('paiementsDocs' => $paiementsDocs, 'paiementsDocsPrelevement' => $paiementsDocsPrelevement, 'periode' => $periode));
    }

    /**
     * @Route("/paiements/societe/{id}", name="paiements_societe")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function societeAction(Request $request, Societe $societe) {
        ini_set('memory_limit', '-1');
        $paiementsDocs = $this->get('paiements.manager')->getRepository()->getBySociete($societe);


        return $this->render('paiements/societe.html.twig', array('paiementsDocs' => $paiementsDocs, 'societe' => $societe));
    }

    /**
     * @Route("/paiements/{id}/modification", name="paiements_modification")
     * @ParamConverter("paiements", class="AppBundle:Paiements")
     */
    public function modificationAction(Request $request, $paiements) {

        $dm = $this->get('doctrine_mongodb')->getManager();

        $facturesIds = $paiements->getFacturesArrayIds();
        $facturesArray = $paiements->getFacturesArray();
        $form = $this->createForm(new PaiementsType($this->container, $dm), $paiements, array(
            'action' => $this->generateUrl('paiements_modification', array('id' => $paiements->getId())),
            'method' => 'POST',
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $paiements = $form->getData();

            $dm->persist($paiements);
            $dm->flush();

            $facturesIds = array_merge($facturesIds,$paiements->getFacturesArrayIds());

            array_unique($facturesIds);

            foreach ($facturesIds as $factureId) {
               $facture = $dm->getRepository('AppBundle:Facture')->findOneById($factureId);
               $facture->updateMontantPaye();
               $dm->persist($facture);
               $dm->flush();
            }

            return $this->redirectToRoute('paiements_modification', array('id' => $paiements->getId()));
        }

        return $this->render('paiements/modification.html.twig', array('paiements' => $paiements, 'form' => $form->createView(), 'facturesArray' => $facturesArray));
    }

    /**
     * @Route("/paiements-ligne/{id}/modification", name="paiements_modification_ligne")
     * @ParamConverter("paiements", class="AppBundle:Paiements")
     */
    public function paiementsModificationLigneAction(Request $request, $paiements) {

      $dm = $this->get('doctrine_mongodb')->getManager();

      if ($request->isXmlHttpRequest()) {
        $cpt = 0;
        $idLigne = $request->request->get('idLigne');
        foreach ($paiements->getPaiement() as $paiement) {
          if($cpt == $idLigne){
            $f = $dm->getRepository('AppBundle:Facture')->findOneById($request->request->get('facture'));
            $paiement->setTypeReglement($request->request->get('type_reglement'));
            $paiement->setMoyenPaiement($request->request->get('moyen_paiement'));
            $paiement->setLibelle($request->request->get('libelle'));
            $paiement->setFacture($f);
            $paiement->setDatePaiement(\DateTime::createFromFormat('d/m/Y',$request->request->get('date_paiement')));
            $paiement->setMontant($request->request->get('montant'));
            $dm->persist($paiements);
            $f->updateMontantPaye();
            $dm->flush();
            return new Response(json_encode(array("success" => true)));
          }
          $cpt++;
        }
        $paiement = new Paiement();
        $f = $dm->getRepository('AppBundle:Facture')->findOneById($request->request->get('facture'));
        $paiement->setTypeReglement($request->request->get('type_reglement'));
        $paiement->setMoyenPaiement($request->request->get('moyen_paiement'));
        $paiement->setLibelle($request->request->get('libelle'));
        $paiement->setFacture($f);
        $paiement->setVersementComptable(false);
        $paiement->setDatePaiement(\DateTime::createFromFormat('d/m/Y',$request->request->get('date_paiement')));
        $paiement->setMontant($request->request->get('montant'));
        $paiements->addPaiement($paiement);
        $dm->persist($paiements);
        $f->updateMontantPaye();
        $dm->flush();
        return new Response(json_encode(array("success" => true)));
      }

      return new Response(json_encode(array("success" => false)));
    }

    /**
     * @Route("/paiements/nouveau", name="paiements_nouveau")
     */
    public function nouveauAction(Request $request) {

        $dm = $this->get('doctrine_mongodb')->getManager();
        $paiements = new Paiements($this->get('doctrine_mongodb')->getManager());

        $paiements->setPrelevement(false);

        $form = $this->createForm(new PaiementsType($this->container, $dm), $paiements, array(
            'action' => $this->generateUrl('paiements_nouveau'),
            'method' => 'POST',
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $paiements = $form->getData();

            $dm->persist($paiements);
            $dm->flush();
            return $this->redirectToRoute('paiements_modification', array('id' => $paiements->getId()));
        }

        return $this->render('paiements/nouveau.html.twig', array('form' => $form->createView()));
    }

    /**
     * @Route("/paiements/export", name="paiements_export")
     */
    public function exportComptableAction(Request $request) {

      // $response = new StreamedResponse();
        $formRequest = $request->request->get('form');

        $dateDebutString = $formRequest['dateDebut']." 00:00:00";
        $dateFinString = $formRequest['dateFin']." 23:59:59";

        $dateDebut = \DateTime::createFromFormat('d/m/Y H:i:s',$dateDebutString);
        $dateFin = \DateTime::createFromFormat('d/m/Y H:i:s',$dateFinString);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $pm = $this->get('paiements.manager');
        $paiementsForCsv = $pm->getPaiementsForCsv($dateDebut,$dateFin);

        $filename = sprintf("export_paiements_du_%s_au_%s.csv", $dateDebut->format("Y-m-d"),$dateFin->format("Y-m-d"));
        $handle = fopen('php://memory', 'r+');

        foreach ($paiementsForCsv as $paiement) {
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
     * @Route("/paiements/{id}/banque", name="paiements_export_banque")
     * @ParamConverter("paiements", class="AppBundle:Paiements")
     */
    public function pdfBanqueAction(Request $request, Paiements $paiements) {


        $dm = $this->get('doctrine_mongodb')->getManager();
        $pm = $this->get('paiements.manager');
        $paiementsLists = array();
        $page = 0;
        $cpt = 0;
        foreach ($paiements->getPaiementUniqueParLibelle() as $paiement) {
           if ($paiements->isRemiseEspece() && $paiement->getMoyenPaiement() == 'CHEQUE') {
               continue;
           }
           if (!$paiements->isRemiseEspece() && $paiement->getMoyenPaiement() != 'CHEQUE') {
               continue;
           }
          if($cpt % 30 == 0){
            $page++;
            $paiementsLists[$page] = array();
          }
          $paiementsLists[$page][] = $paiement;
          $cpt++;
        }


        $html = $this->renderView('paiements/pdfBanque.html.twig', array(
            'paiements' => $paiements,
            'paiementsLists' => $paiementsLists,
            'parameters' => $pm->getParameters(),
        ));


        $filename = sprintf("banque_paiements_%s.pdf", $paiements->getDateCreation()->format("Y-m-d"));

        if ($request->get('output') == 'html') {

            return new Response($html, 200);
        }

        $paiements->setImprime(true);
        $dm->persist($paiements);
        $dm->flush();

        return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($html, $this->getPdfGenerationOptions()), 200, array(
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                )
        );
    }

    public function getPdfGenerationOptions() {
        return array('disable-smart-shrinking' => null, 'encoding' => 'utf-8', 'margin-left' => 3, 'margin-right' => 3, 'margin-top' => 4, 'margin-bottom' => 4, 'zoom' => 0.7);
    }

    /**
     * @Route("/paiements/previsualisation-remise-bancaire", name="paiements_prelevement_previsualisation")
     */
    public function paiementPrelevementPrevisualisationFichierAction(Request $request)
    {
        $fm = $this->get('facture.manager');
        $facturesForCsv = $fm->getFacturesPrelevementsForCsv();

        $prelevements = [];
        foreach ($facturesForCsv as $facture) {
            $datefacturation = $facture->getPrelevementDate();
            $prelevements[$datefacturation->format('d M Y')][] = $facture;
        }

        return $this->render('paiements/listePrelevements.html.twig', compact('prelevements'));
    }

    /**
     * @Route("/paiements/prelevement", name="paiements_prelevement")
     */
    public function paiementPrelevementAction(Request $request) {

        ini_set('memory_limit', '-1');
        $banqueParameters = $this->getParameter('banque');

    	$dm = $this->get('doctrine_mongodb')->getManager();
    	$fm = $this->get('facture.manager');

    	$facturesForCsv = $fm->getFacturesPrelevementsForCsv();

        if(count($facturesForCsv)){
            $prelevement = new PrelevementXml($facturesForCsv,$banqueParameters);
            $prelevement->createPrelevement();
            $this->createPaiementsPrelevement($facturesForCsv,$prelevement);
        }

        return $this->redirectToRoute('paiements_liste');


    }

    /**
     * @Route("/paiements/prelevement-remise-bancaire/{id}", name="paiements_prelevement_remise_fichier")
     * @ParamConverter("paiements", class="AppBundle:Paiements")
     */
    public function paiementPrelevementRemiseFichierAction(Request $request, Paiements $paiements) {

        $filename = "prelevement_banque_".$paiements->getIdentifiant().".xml";
        return new Response(
                $paiements->getXmlbase64(), 200, array(
                'Content-Type' => 'xml',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                )
        );
    }

    private function createPaiementsPrelevement($facturesForCsv,$prelevement){
        $date = new \DateTime('now');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $paiements = new Paiements($this->get('doctrine_mongodb')->getManager());
        $paiements->setDateCreation($date);
        $paiements->setPrelevement(true);
        $paiements->setImprime(false);

        $societesInFirstPrev = array();

        foreach ($facturesForCsv as $key => $facture) {
            $paiement = new Paiement();
            $paiement->setFacture($facture);
            $paiement->setMoyenPaiement(PaiementsManager::MOYEN_PAIEMENT_PRELEVEMENT_BANQUAIRE);
            $paiement->setTypeReglement(PaiementsManager::TYPE_REGLEMENT_FACTURE);
            $paiement->setDatePaiement($date);
            $paiement->setMontant(0.0);
            $paiement->setLibelle('FACT '.$facture->getNumeroFacture().' du '.$facture->getDateEmission()->format("d m Y").' '. str_ireplace(array(".",","),"EUR",sprintf("%0.2f",$facture->getMontantAPayer())));
            $paiement->setVersementComptable(false);
            $paiements->addPaiement($paiement);
            if($facture->getSociete()->getSepa()->isFirst()){
                $societesInFirstPrev[$facture->getSociete()->getId()] = $facture->getSociete();
            }
        }


        $paiements->setXmlbase64($prelevement->getXml());


        foreach ($societesInFirstPrev as $key => $societe) {
            $societe->getSepa()->setFirst(false);
        }

        $dm->persist($paiements);
        $dm->flush();
        return $paiements;
    }

}
