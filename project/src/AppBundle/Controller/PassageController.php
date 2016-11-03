<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Type\EtablissementChoiceType;
use AppBundle\Document\Etablissement;
use AppBundle\Document\Contrat;
use AppBundle\Document\Passage;
use AppBundle\Type\PassageType;
use AppBundle\Type\PassageCreationType;
use AppBundle\Type\PassageModificationType;
use AppBundle\Type\PassageCommentaireType;
use AppBundle\Type\PassageAnnulationType;
use AppBundle\Manager\PassageManager;
use Behat\Transliterator\Transliterator;
use AppBundle\Type\InterventionRapideCreationType;
use AppBundle\Manager\ContratManager;
use AppBundle\Document\Prestation;
use AppBundle\Manager\EtablissementManager;

class PassageController extends Controller {

    /**
     * @Route("/passage/{secteur}/visualisation/{mois}", name="passage" , defaults={"secteur" = "PARIS", "mois" = "courant"})
     */
    public function indexAction(Request $request, $secteur) {
        ini_set('memory_limit', '64M');

        $formEtablissement = $this->createForm(EtablissementChoiceType::class, null, array(
            'action' => $this->generateUrl('passage_etablissement_choice'),
            'method' => 'GET',
        ));
        $passageManager = $this->get('passage.manager');

        $moisCourrant = ($request->get('mois') == "courant");
        $dateFin = new \DateTime();
        $dateFinCourant = clone $dateFin;
        $dateFinCourant->modify("+1 month");

        $dateDebut = new \DateTime();
        $passages = null;
        $moisPassagesArray = $passageManager->getNbPassagesToPlanPerMonth($secteur);
        $anneeMois = null;

        if($moisCourrant){
          $dateFin = $dateFinCourant;
          $passages = $passageManager->getRepository()->findToPlan($secteur, null, $dateFin);
          $anneeMois = 'courant';
        }else{
          $anneeMois = $request->get('mois');
          $dateDebut = \DateTime::createFromFormat('Ymd',$anneeMois.'01');
          $dateFin = clone $dateDebut;
          $dateFin->modify("last day of this month");
          $passages = $passageManager->getRepository()->findToPlan($secteur, $dateDebut, $dateFin);
        }

        $geojson = $this->buildGeoJson($passages);

        return $this->render('passage/index.html.twig', array('passages' => $passages,
                    'anneeMois' => $anneeMois,
                    'dateFinCourant' => $dateFinCourant,
                    'dateFin' => $dateFin,
                    'formEtablissement' => $formEtablissement->createView(),
                    'geojson' => $geojson,
                    'moisPassagesArray' => $moisPassagesArray,
                    'passageManager' => $passageManager,
                    'etablissementManager' => $this->get('etablissement.manager'),
                    'secteur' => $secteur));
    }

    /**
     * @Route("/passage/{id_etablissement}/{id_contrat}/creer", name="passage_creation")
     * @ParamConverter("etablissement", class="AppBundle:Etablissement", options={"id" = "id_etablissement"})
     * @ParamConverter("contrat", class="AppBundle:Contrat", options={"id" = "id_contrat"})
     */
    public function creationAction(Request $request, Etablissement $etablissement, Contrat $contrat) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $rvm = $this->get('rendezvous.manager');

        $passage = $this->get('passage.manager')->create($etablissement, $contrat);
        $passage->setDatePrevision(new \DateTime());

        $form = $this->createForm(new PassageCreationType($dm), $passage, array(
            'action' => $this->generateUrl('passage_creation', array('id_etablissement' => $etablissement->getId(), 'id_contrat' => $contrat->getId())),
            'method' => 'POST',
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $passage->setDateDebut($passage->getDatePrevision());
            $dm->persist($passage);
            $contrat->addPassage($etablissement, $passage);

            $dm->flush();

            return $this->redirectToRoute('passage_planifier', array('passage' => $passage->getId()));
        }

        return $this->render('passage/creation.html.twig', array('passage' => $passage, 'form' => $form->createView()));
    }

    /**
     * @Route("/passage/commentaire/{id}", name="passage_commentaire")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function commentaireAction(Request $request, Passage $passage) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $form = $this->createForm(new PassageCommentaireType($dm), $passage, array(
            'action' => $this->generateUrl('passage_commentaire', array('id' => $passage->getId())),
            'method' => 'POST',
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $passage = $form->getData();
            $dm->flush();
            return $this->redirectToRoute('passage');
        }

        return $this->render('passage/commentaire.html.twig', array('passage' => $passage, 'form' => $form->createView()));
    }

    /**
     * @Route("/passage/{id}/modifier", name="passage_modification")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function modificationAction(Request $request, Passage $passage) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        if($passage->isRealise()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(new PassageModificationType($dm), $passage, array(
            'action' => $this->generateUrl('passage_modification', array('id' => $passage->getId())),
            'method' => 'POST',
        ))->add('modifier', 'submit', array('label' => "Modifier", "attr" => array("class" => "btn btn-primary pull-right")));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $passage = $form->getData();
            if(!$passage->getRendezVous()) {
                $passage->setDateDebut($passage->getDatePrevision());
            }
            $dm->persist($passage);
            $dm->flush();
            return $this->redirectToRoute('passage_etablissement', array('id' => $passage->getEtablissement()->getId()));
        }


        return $this->render('passage/modification.html.twig', array('passage' => $passage, 'form' => $form->createView()));
    }

    /**
     * @Route("/passage/{passage}/planifier", name="passage_planifier")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function planifierAction(Request $request, Passage $passage) {
        if(!count($passage->getTechniciens())) {

            return $this->redirectToRoute('calendarManuel', array('passage' => $passage->getId()));
        }

        return $this->redirectToRoute('calendar', array('passage' => $passage->getId(),'id' => $passage->getEtablissement()->getId(), 'technicien' => $passage->getTechniciens()->first()->getId()));
    }

    /**
     * @Route("/passage/etablissement-choix", name="passage_etablissement_choice")
     */
    public function etablissementChoiceAction(Request $request) {
        $formData = $request->get('etablissement_choice');

        return $this->redirectToRoute('passage_etablissement', array('id' => $formData['etablissements']));
    }

    /**
     * @Route("/passages/{id}/annulation", name="passage_annulation")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function annulationAction(Request $request, Passage $passage) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $pm = $this->get('passage.manager');

        $form = $this->createForm(new PassageAnnulationType($dm, $passage), $passage, array(
            'action' => $this->generateUrl('passage_annulation', array('id' => $passage->getId())),
            'method' => 'POST',
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
          $statut = $passage->getStatut();
          $passage->setStatut(PassageManager::STATUT_ANNULE);
          $dm->persist($passage);
          // if ($statut == PassageManager::STATUT_A_PLANIFIER) {
          //   $pm->updateNextPassageAPlannifier($passage);
          // }
          $dm->flush();
          $contrat = $dm->getRepository('AppBundle:Contrat')->findOneById($passage->getContrat()->getId());
          $contrat->verifyAndClose();

          $dm->flush();

          return $this->redirectToRoute('passage_etablissement', array('id' => $passage->getEtablissement()->getId()));
        }

        return $this->render('passage/annulation.html.twig', array('form' => $form->createView(), 'passage' => $passage));
    }

    /**
     * @Route("/passages/{id}/etablissement", name="passage_etablissement")
     * @ParamConverter("etablissement", class="AppBundle:Etablissement")
     */
    public function etablissementAction(Request $request, Etablissement $etablissement) {

        $contratManager = $this->get('contrat.manager');
        $contrats = $contratManager->sortedContratsByEtablissement($etablissement);

        $geojson = $this->buildGeoJson(array($etablissement));
        $formEtablissement = $this->createForm(EtablissementChoiceType::class, array('etablissements' => $etablissement->getIdentifiant(), 'etablissement' => $etablissement), array(
            'action' => $this->generateUrl('passage_etablissement_choice'),
            'method' => 'POST',
        ));

        return $this->render('passage/etablissement.html.twig', array('etablissement' => $etablissement, 'contrats' => $contrats, 'formEtablissement' => $formEtablissement->createView(), 'geojson' => $geojson,'contratManager' => $contratManager));
    }

    /**
     * @Route("/passage/visualisation/{id}", name="passage_visualisation")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function visualisationAction(Request $request, Passage $passage) {

        if ($passage->getRendezVous()) {
            return $this->redirectToRoute('calendarRead', array('id' => $passage->getRendezVous()->getId()));
        }

        return $this->forward('AppBundle:Calendar:calendarRead', array('passage' => $passage->getId()));
    }

    /**
     * @Route("/passage/edition/{id}", name="passage_edition")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function editionAction(Request $request, Passage $passage) {
        $dm = $this->get('doctrine_mongodb')->getManager();


        $form = $this->createForm(new PassageType($dm), $passage, array(
            'action' => $this->generateUrl('passage_edition', array('id' => $passage->getId())),
            'method' => 'POST',
        ));

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {

            return $this->render('passage/edition.html.twig', array('passage' => $passage, 'form' => $form->createView()));
        }
        $passageManager = $this->get('passage.manager');

        $contrat = $dm->getRepository('AppBundle:Contrat')->findOneById($passage->getContrat()->getId());

        if ($passage->getMouvementDeclenchable() && !$passage->getMouvementDeclenche()) {
            if ($contrat->generateMouvement($passage)) {
                $passage->setMouvementDeclenche(true);
            }
        }

        $passage->setDateRealise($passage->getDateDebut());
        $dm->persist($passage);
        $dm->persist($contrat);
        $dm->flush();

        $contrat = $dm->getRepository('AppBundle:Contrat')->findOneById($passage->getContrat()->getId());
        $contrat->verifyAndClose();

        $dm->flush();
        if ($passage->getMouvementDeclenchable()) {
            return $this->redirectToRoute('facture_societe', array('id' => $passage->getSociete()->getId()));
        } else {
            return $this->redirectToRoute('passage_etablissement', array('id' => $passage->getEtablissement()->getId()));
        }
    }

    public function getPdfGenerationOptions() {
        return array('disable-smart-shrinking' => null, 'encoding' => 'utf-8', 'margin-left' => 3, 'margin-right' => 3, 'margin-top' => 4, 'margin-bottom' => 4, 'zoom' => 0.8);
    }

    /**
     * @Route("/passage/pdf-bon/{id}", name="passage_pdf_bon")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function pdfBonAction(Request $request, Passage $passage) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $fm = $this->get('facture.manager');

        $html = $this->renderView('passage/pdfBons.html.twig', array(
            'passage' => $passage,
            'parameters' => $fm->getParameters(),
        ));
        $passage->setImprime(true);

        $dm->flush();
        $filename = sprintf("bon_passage_%s_%s.pdf", $passage->getDateDebut()->format("Y-m-d_H:i"), strtoupper(Transliterator::urlize($passage->getTechniciens()->first()->getIdentite())));

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
     * @Route("/passage/pdf-bons-massif", name="passage_pdf_bons_massif")
     */
    public function pdfBonsMassifAction(Request $request) {
        $fm = $this->get('facture.manager');
        $pm = $this->get('passage.manager');
        $dm = $this->get('doctrine_mongodb')->getManager();

        if ($request->get('technicien')) {
            $technicien = $dm->getRepository('AppBundle:Compte')->findOneById($request->get('technicien'));
            $passages = $pm->getRepository()->findAllPlanifieByPeriodeAndIdentifiantTechnicien($request->get('dateDebut'), $request->get('dateFin'), $technicien, false);
            $filename = sprintf("bons_passage_%s_%s_%s.pdf", $request->get('dateDebut'), $request->get('dateFin'), strtoupper(Transliterator::urlize($technicien->getIdentite())));
        } else {
            $passages = $pm->getRepository()->findAllPlanifieByPeriode($request->get('dateDebut'), $request->get('dateFin'), true);
            $filename = sprintf("bons_passage_%s_%s.pdf", $request->get('dateDebut'), $request->get('dateFin'));
        }

        $html = $this->renderView('passage/pdfBonsMassif.html.twig', array(
            'passages' => $passages,
            'parameters' => $fm->getParameters(),
        ));

        if ($request->get('output') == 'html') {

            return new Response($html, 200);
        }

        foreach ($passages as $passage) {
            $passage->setImprime(true);
        }
        $dm->flush();

        return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($html, $this->getPdfGenerationOptions()), 200, array(
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                )
        );
    }

    /**
     * @Route("/passage/pdf-mission/{id}", name="passage_pdf_mission")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function pdfMissionAction(Request $request, Passage $passage) {
        $pm = $this->get('passage.manager');

        $passagesHistory = $pm->getRepository()->findHistoriqueByEtablissementAndPrestationsAndNumeroContrat($passage->getContrat(), $passage->getEtablissement(), $passage->getPrestations());

        $filename = sprintf("suivi_client_%s_%s.pdf", $passage->getDateDebut()->format("Y-m-d_H:i"), strtoupper(Transliterator::urlize($passage->getTechniciens()->first()->getIdentite())));

        $html = $this->renderView('passage/pdfMission.html.twig', array(
            'passage' => $passage,
            'passagesHistory' => $passagesHistory,
        ));

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
     * @Route("/passage/erreurs", name="passage_erreurs")
     */
    public function PassageErreursAction(Request $request) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $passages = $dm->getRepository('AppBundle:Passage')->findAllErreurs();

        return $this->render('passage/erreurs.html.twig', array('passages' => $passages));
    }

    /**
     * @Route("/passage/pdf-missions-massif", name="passage_pdf_missions_massif")
     */
    public function pdfMissionsMassifAction(Request $request) {
        $fm = $this->get('facture.manager');
        $pm = $this->get('passage.manager');
        $dm = $this->get('doctrine_mongodb')->getManager();

        if ($request->get('technicien')) {
            $technicien = $dm->getRepository('AppBundle:Compte')->findOneById($request->get('technicien'));
            $passages = $pm->getRepository()->findAllPlanifieByPeriodeAndIdentifiantTechnicien($request->get('dateDebut'), $request->get('dateFin'), $technicien);
            $filename = sprintf("suivis_client_%s_%s_%s.pdf", $request->get('dateDebut'), $request->get('dateFin'), strtoupper(Transliterator::urlize($technicien->getIdentite())));
        } else {
            $passages = $pm->getRepository()->findAllPlanifieByPeriode($request->get('dateDebut'), $request->get('dateFin'));
            $filename = sprintf("suivis_client_%s_%s.pdf", $request->get('dateDebut'), $request->get('dateFin'));
        }

        $passagesHistories = array();

        foreach ($passages as $passage) {
            $passagesHistories[$passage->getId()] = $pm->getRepository()->findHistoriqueByEtablissementAndPrestationsAndNumeroContrat($passage->getContrat(), $passage->getEtablissement(), $passage->getPrestations());
        }

        $html = $this->renderView('passage/pdfMissionsMassif.html.twig', array(
            'passages' => $passages,
            'passagesHistories' => $passagesHistories,
        ));

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
     * @Route("/etablissement-all", name="etablissement_all")
     */
    public function allAction(Request $request) {
        $etablissementsResult = $this->get('etablissement.manager')->getRepository()->findBy(array(), null, 3000);
        $geojson = $this->buildGeoJson($etablissementsResult);
        return $this->render('etablissement/all.html.twig', array('geojson' => $geojson));
    }

    private function buildGeoJson($listDocuments) {
        $geojson = new \stdClass();
        $geojson->type = "FeatureCollection";
        $geojson->features = array();
        foreach ($listDocuments as $document) {
            $feature = new \stdClass();
            $feature->type = "Feature";
            $feature->properties = new \stdClass();
            $feature->properties->_id = $document->getId();
            $etbInfos = $document;
            $coordinates = null;
            if (!($document instanceof Etablissement)) {
                $allTechniciens = $document->getTechniciens();
                $firstTechnicien = null;
                foreach ($allTechniciens as $technicien) {
                    $firstTechnicien = $technicien;
                    break;
                }
                $etbInfos = $document->getEtablissementInfos();
                $coordinates = $document->getEtablissementInfos()->getAdresse()->getCoordonnees();
                $feature->properties->color = 'black';
                $feature->properties->colorText = 'white';
                if (!is_null($firstTechnicien)) {

                    $feature->properties->color = $firstTechnicien->getCouleur();
                    $feature->properties->colorText = $firstTechnicien->getCouleurText();
                }
            } else {
                $coordinates = $document->getAdresse()->getCoordonnees();
                $feature->properties->color = "#fff";
                $feature->properties->colorText = "#000";
            }
            if (!$coordinates->getLon() || !$coordinates->getLat()) {
                continue;
            }
            $feature->properties->nom = $etbInfos->getNom();
            $feature->properties->icon = 'mdi-' . $etbInfos->getIcon();
            $feature->geometry = new \stdClass();
            $feature->geometry->type = "Point";
            $feature->geometry->coordinates = array($coordinates->getLon(), $coordinates->getLat());
            $geojson->features[] = $feature;
        }
        return $geojson;
    }

    /**
     * @Route("/passages/{id}/creation-rapide", name="passage_creation_rapide")
     * @ParamConverter("etablissement", class="AppBundle:Etablissement")
     */
    public function creationRapideAction(Request $request, Etablissement $etablissement) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('contrat.manager');
        $contrat = $cm->createInterventionRapide($etablissement);

        $configurationPrestationArray = $dm->getRepository('AppBundle:Configuration')->findConfiguration()->getPrestationsArray();

        $form = $this->createForm(new InterventionRapideCreationType($dm), $contrat, array(
            'action' => $this->generateUrl('passage_creation_rapide', array('id' => $etablissement->getId())),
            'method' => 'POST',
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contrat->setDateAcceptation($contrat->getDateDebut());
            $contrat->updateObject();
            $contrat->updatePrestations($dm);
            $dateFin = clone $contrat->getDateDebut();
            $dateFin->modify("+" . $contrat->getDuree() . " month");
            $contrat->setDateFin($dateFin);
            $dm->persist($contrat);
            $cm->generateAllPassagesForContrat($contrat);
            $contrat->setStatut(ContratManager::STATUT_EN_COURS);
            $passage = $contrat->getUniquePassage();
            $passage->setDateDebut($contrat->getDateDebut());
            $passage->setDateFin(clone $passage->getDateDebut());
            $dm->flush();

            return $this->redirectToRoute('passage_edition', array('id' => $contrat->getUniquePassage()->getId()));
        }

        return $this->render('passage/creationRapide.html.twig', array('etablissement' => $etablissement, 'contrat' => $contrat, 'form' => $form->createView()));
    }

}
