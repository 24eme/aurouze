<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Type\EtablissementChoiceType;
use AppBundle\Document\Etablissement;
use AppBundle\Document\Societe;
use AppBundle\Document\Contrat;
use AppBundle\Document\Passage;
use AppBundle\Document\Coordonnees;
use AppBundle\Type\PassageType;
use AppBundle\Type\PassageCreationType;
use AppBundle\Type\PassageModificationType;
use AppBundle\Type\PassageAnnulationType;
use AppBundle\Manager\PassageManager;
use Behat\Transliterator\Transliterator;
use AppBundle\Type\InterventionRapideCreationType;
use AppBundle\Manager\ContratManager;
use AppBundle\Document\Prestation;
use AppBundle\Manager\EtablissementManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Doctrine\ODM\MongoDB\LockMode;

class PassageController extends Controller
{
    /**
     * Retourne des informations supplémentaires sur l'établissement,
     * la société, le passage,... pour accélérer le chargement de la
     * page d'index
     *
     * @Route("/ajax/passage/{passage}/infos", name="ajax_more_infos_passage")
     */
    public function showInformationsAction(Passage $passage)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $etablissement = $passage->getEtablissement();
        $contrat = $passage->getContrat();
        $societe = $contrat->getSociete();
        $facture = $this->get('facture.manager');
        $lastPassages = $dm->getRepository('AppBundle:Passage')->findLastPassages($etablissement, $passage);
        $solde = $facture->getSolde($societe);

        return $this->render('passage/infossupplementaires.html.twig',
            [
                'passage' => $passage,
                'etablissement' => $etablissement,
                'contrat' => $contrat,
                'societe' => $societe,
                'lastPassages' => $lastPassages,
                'solde' => $solde
            ]
        );
    }

    /**
     * @Route("/passage", name="passage")
     */
    public function indexAction(Request $request) {

        $passageManager = $this->get('passage.manager');
        $secteur = $this->getParameter('secteurs');

        $secteur = "0";

        if($this->getParameter('secteurs')) {
            $secteur = 'PARIS';
        }
        $passagesFiltreExportForm = $this->getPassagesFiltreExportForm();
        return $this->redirectToRoute('passage_secteur', array('secteur' => $secteur,'passagesFiltreExportForm' => $passagesFiltreExportForm->createView()));
    }

    /**
     * @Route("/passage/{secteur}/visualisation/{periode}", name="passage_secteur")
     */
    public function secteurAction(Request $request, $secteur, $periode = null) {

        $formEtablissement = $this->createForm(EtablissementChoiceType::class, null, array(
            'action' => $this->generateUrl('passage_etablissement_choice'),
            'method' => 'GET',
        ));
        $passageManager = $this->get('passage.manager');
        $devisManager = $this->get('devis.manager');

        $dateFin = new \DateTime();
        $dateFinCourant = clone $dateFin;
        $dateFinCourant->modify("+1 month");
        $dateFinMax = clone $dateFinCourant;

        $anneeMois = null;
        $dateDebut = null;
        $dateFin = $dateFinCourant;
        $anneeMois = "courant";
        $dateFinAll = $dateFin;

        if($request->get('periode') == '6lastmonths') {
            $anneeMois = $request->get('periode');
            $dateDebut = new \DateTime();
            $dateDebut->modify("last day of previous month");
            $dateDebut->modify("-6 month");
            $dateFin = new \DateTime();
            $dateFin->modify("last day of previous month");
        } elseif(strlen($request->get('periode')) == 4) {
            $anneeMois = $request->get('periode');
            $dateDebut = \DateTime::createFromFormat('Ymd',$anneeMois.'0101');
            $dateFin = clone $dateDebut;
            $dateFin->modify("+1 year -1 day");
            $dateFin->setTime(23,59,59);
        } else {
            $anneeMois = ($request->get('periode',null))? $request->get('periode') : date('Ym', strtotime(date('Y-m-d')));
            $dateDebut = \DateTime::createFromFormat('Ymd',$anneeMois.'01');
            $dateFin = clone $dateDebut;
            $dateFin->modify("last day of this month");
            $dateFin->setTime(23,59,59);
        }

        $dateFinAll = new \DateTime();
        $dateFinAll->modify("last day of +2 month");
        $dateFinAll->setTime(23,59,59);

        $passages = null;
        $moisPassagesArray = $passageManager->getNbPassagesToPlanPerMonth($secteur, clone $dateFinAll);
        $morePassages = [];
        foreach ($moisPassagesArray as $key => $values) {
            if (strlen($key) === 4) {
                $morePassages[$key] = \DateTimeImmutable::createFromFormat('Y', $key)->format('Y-m-d');
            }
        }

        $now = new \DateTimeImmutable();
        for ($i = 0; $i < 12; $i++) {
            $date = $now->modify("-".$i." month");
            $morePassages[$date->format('Ym')] = $date->format('Y-m-d');
        }
        for ($i = 3; $i < (3+6); $i++) {
            $date = $now->modify("+".$i." month");
            $morePassages[$date->format('Ym')] = $date->format('Y-m-d');
        }

        $frequences = array(2,3,4,6,12);
        $frequence = $request->get('frequence');

        $passages = $passageManager->getRepository()->findToPlan($secteur, $dateDebut, clone $dateFin, $frequence);

        if(!$frequence){
            $passages = $passages->toArray();
        }

        usort($passages, array("AppBundle\Document\Passage", "triPerHourPrecedente"));
        $lat = $request->get('lat', 48.8593829);
        $lon = $request->get('lon', 2.347227);
        $zoom = $request->get('zoom', 0);

        $coordinatesCenter = new Coordonnees();
        $coordinatesCenter->setLat($lat);
        $coordinatesCenter->setLon($lon);
        $coordinatesCenter->setZoom($zoom);

        $geojson = $this->buildGeoJson($passages);
        $passagesFiltreExportForm = $this->getPassagesFiltreExportForm();

        return $this->render('passage/index.html.twig', array(
            'passages' => $passages,
            'anneeMois' => $anneeMois,
            'dateFinCourant' => $dateFinCourant,
            'dateFin' => $dateFin,
            'formEtablissement' => $formEtablissement->createView(),
            'geojson' => $geojson,
            'moisPassagesArray' => $moisPassagesArray,
            'morePassages' => $morePassages,
            'passageManager' => $passageManager,
            'etablissementManager' => $this->get('etablissement.manager'),
            'secteur' => $secteur,
            'coordinatesCenter' => $coordinatesCenter,
            'passagesFiltreExportForm' => $passagesFiltreExportForm->createView(),
            'frequence' => $frequence,
            'frequences' => $frequences
        ));
    }


    public function getPassagesFiltreExportForm(){
      $formBuilder = $this->createFormBuilder();
      $formBuilder->add('filtre', HiddenType::class, array());
      $formBuilder->setAction($this->generateUrl('passages_filtre_export'));
      return $formBuilder->getForm();
    }

    /**
     * @Route("/passages-filtre-export", name="passages_filtre_export")
     */
    public function passagesFiltreExportAction(Request $request) {
        $passagesFiltreExportForm = $this->getPassagesFiltreExportForm();
        $passagesFiltreExportForm->handleRequest($request);
        if ($passagesFiltreExportForm->isSubmitted() && $passagesFiltreExportForm->isValid()) {
          $datas = $passagesFiltreExportForm->getData();
          $passagesIds = json_decode($datas['filtre']);
          $dm = $this->get('doctrine_mongodb')->getManager();
          $pm = $this->get('passage.manager');
          $passagesForCsv = $pm->getPassagesForCsv($passagesIds);

          $handle = fopen('php://memory', 'r+');

          foreach ($passagesForCsv as $passage) {
              fputcsv($handle, $passage,';');
          }

          rewind($handle);
          $content = stream_get_contents($handle);
          fclose($handle);

          $response = new Response(utf8_decode($content), 200, array(
              'Content-Type' => 'text/csv',
              'Content-Disposition' => 'attachment; filename="export_passages.csv"',
          ));
          $response->setCharset('UTF-8');

          return $response;
        }

        return $this->redirectToRoute('passage_secteur', array('secteur' => $secteur));
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
        $passage->setZone($contrat->getZone());
        $form = $this->createForm(new PassageCreationType($dm), $passage, array(
            'action' => $this->generateUrl('passage_creation', array('id_etablissement' => $etablissement->getId(), 'id_contrat' => $contrat->getId())),
            'method' => 'POST',
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $passage->setDateDebut($passage->getDatePrevision());
            $dm->persist($passage);
            $contrat->addPassage($etablissement, $passage);
            $contrat->preUpdate();
            $dm->flush();

            if($request->get('action') == "creer_planifier") {

                return $this->redirectToRoute('calendar_planifier', array('planifiable' => $passage->getId()));
            }

            return $this->redirectToRoute('contrat_visualisation', array('id' => $contrat->getId()));
        }

        return $this->render('passage/creation.html.twig', array('passage' => $passage, 'form' => $form->createView()));
    }


    /**
     * @Route("/passage/{id}/suppression", name="passage_suppression")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function suppressionAction(Request $request, Passage $passage) {
        $contrat = $passage->getContrat();
        $contrat->removePassage($passage);
        $pm = $this->get('passage.manager');
        $pm->delete($passage);
        return $this->redirectToRoute('contrat_visualisation', array('id' => $contrat->getId()));
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
            'action' => $this->generateUrl('passage_modification', array('id' => $passage->getId(), 'service' => $request->get('service'))),
            'method' => 'POST',
        ))->add('modifier', 'submit', array('label' => "Modifier", "attr" => array("class" => "btn btn-primary pull-right")));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $passage = $form->getData();
            $contrat = $passage->getContrat();
            if(!$passage->getRendezVous()) {
                $passage->setDateDebut($passage->getDatePrevision());
            }
            $contrat->preUpdate();
            $dm->flush();

            if($request->get('service')) {

                return $this->redirect($request->get('service'));
            }

            return $this->redirectToRoute('passage_etablissement', array('id' => $passage->getEtablissement()->getId()));
        }


        return $this->render('passage/modification.html.twig', array('passage' => $passage, 'form' => $form->createView()));
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
            'action' => $this->generateUrl('passage_annulation', array('id' => $passage->getId(), 'service' => $request->get('service'))),
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

            if($request->get('service')) {

                return $this->redirect($request->get('service'));
            }

            return $this->redirectToRoute('passage_etablissement', array('id' => $passage->getEtablissement()->getId()));
        }

        return $this->render('passage/annulation.html.twig', array('form' => $form->createView(), 'passage' => $passage, 'service' => $request->get('service')));
    }

    /**
     * @Route("/passages/{id}/societe", name="passage_societe")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function societeAction(Request $request, Societe $societe) {

        $object = $request->get('object');
        if ($object && preg_match('/^ETABLISSEMENT-*/', $object)) {
            return $this->redirectToRoute('passage_etablissement', array('id' => $object));
        }


        $object = $request->get('object');
        if ($object && preg_match('/^ETABLISSEMENT-*/', $object)) {
            return $this->redirectToRoute('passage_etablissement', array('id' => $object));
        }

        $etablissements = $societe->getEtablissementsByStatut(true);

        if(count($etablissements) == 1) {
            foreach($etablissements as $etablissement) {

                return $this->redirectToRoute('passage_etablissement', array('id' => $etablissement->getId()));
            }
        }

        return $this->render('passage/societe.html.twig', array('societe' => $societe, "etablissements" => $etablissements));
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
            return $this->redirectToRoute('calendarRead', array('id' => $passage->getRendezVous()->getId(), 'service' => $request->get('service')));
        }

        return $this->forward('AppBundle:Calendar:calendarRead', array('planifiable' => $passage->getId(), 'service' => $request->get('service')));
    }

    /**
     * @Route("/passage/edition/{id}", name="passage_edition")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function editionAction(Request $request, Passage $passage) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('contrat.manager');

        $form = $this->createForm(new PassageType($dm), $passage, array(
            'action' => $this->generateUrl('passage_edition', array('id' => $passage->getId(), 'service' => $request->get('service'))),
            'method' => 'POST',
        ));

        $form->handleRequest($request);

        $contrat = $dm->getRepository('AppBundle:Contrat')->findOneById($passage->getContrat()->getId());

        $lastPassageRealise = null;
        foreach ($cm->getPassagesByNumeroArchiveContrat($contrat, true) as $etab => $ps) {
            foreach ($ps as $p) {
                if ($p->isRealise()) { $lastPassageRealise = $p; break; }
            }
        }

        if(!$passage->getEmailTransmission()){
            if($lastPassageRealise){
                $passage->setEmailTransmission($lastPassageRealise->getEmailTransmission());
            }
            elseif($passage->getEtablissement()->getEmail()){
                $passage->setEmailTransmission($passage->getEtablissement()->getEmail());
            }
            elseif($contrat->getSociete()->getContactCoordonnee()->getEmail()){
                $passage->setEmailTransmission($contrat->getSociete()->getContactCoordonnee()->getEmail());
            }
            $dm->persist($passage);
            $dm->flush();
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            if($this->container->getParameter("commercial_seine_et_marne")){
                $contrat->setZone($this->container->getParameter("commercial_seine_et_marne"));
                $passage->setZone($contrat->getZone());
                $dm->persist($passage);
                $dm->persist($contrat);
                $dm->flush();
            }
            return $this->render('passage/edition.html.twig', array('passage' => $passage, 'form' => $form->createView(), 'service' => $request->get('service')));
        }


        $passageManager = $this->get('passage.manager');

        if ($passage->getMouvementDeclenchable() && !$passage->getMouvementDeclenche()) {
            if ($contrat->generateMouvement($passage)) {
                $passage->setMouvementDeclenche(true);
            }
        }
        $passage->setDateRealise($passage->getDateDebut());
        $passage->setSaisieTechnicien(($passage->getEmailTransmission() || $passage->getNomTransmission() || $passage->getSignatureBase64()) && $passage->getDescription());

        $dm->persist($passage);
        $dm->persist($contrat);
        $dm->flush();

        $contrat = $passage->getContrat();
        $this->get('contrat.manager')->updateInfosPassagePrecedent($contrat, $passage->getEtablissement());
        $contrat->verifyAndClose();

        $dm->flush();
        if ($passage->getMouvementDeclenchable()) {

            return $this->redirectToRoute('facture_societe', array('id' => $passage->getSociete()->getId()));
        } elseif($request->get('service')) {

            return  $this->redirect($request->get('service'));
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
        $fm = $this->get('facture.manager');

        $html = $this->renderView('passage/pdfBons.html.twig', array(
            'passage' => $passage,
            'parameters' => $fm->getParameters(),
        ));

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
     * @Route("/passage/pdf-rapport-print/{id}", name="passage_pdf_rapport_print")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function pdfRapportPrintAction(Request $request, Passage $passage) {
        $rapportVisitePdf = $this->createRapportVisitePdf($passage);

        if ($request->get('output') == 'html') {

            return new Response($rapportVisitePdf->html, 200);
        }

        if(!$passage->getEmailTransmission()){
            $dm = $this->get('doctrine_mongodb')->getManager();
            $passage->setPdfNonEnvoye(false);
            $passage->setPdfRapportDateEnvoi(new \DateTime());
            $dm->flush();
        }

        if( !$rapportVisitePdf->pdfs){
            return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($rapportVisitePdf->html, $this->getPdfGenerationOptions()), 200, array(
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $rapportVisitePdf->filename . '"'
                )
            );
        }

        $tmpfile = $this->container->getParameter('kernel.cache_dir').'/PDFRAPPORT_'.$passage->getId().uniqid();
        $this->get('knp_snappy.pdf')->generateFromHtml($rapportVisitePdf->html,$tmpfile,$this->getPdfGenerationOptions());

        foreach($rapportVisitePdf->pdfs as $pdf){
            $filename  = '/tmp/output-pdf-'.$passage->getId().rand().'.pdf';
            $concat = "/tmp/output-pdf-concact-".$passage->getId().rand();
            file_put_contents($filename,base64_decode($pdf->getBase64()));
            exec(escapeshellcmd('pdftk '.$tmpfile.' '.$filename.' cat output '.$concat), $output, $exitcode);
            $tmpfile = $concat;
            unlink($filename);
        }

        if ($exitcode !== 0) {
            throw new \Exception('pdftk failed with error: '.implode(', ', $output));
        }

        return new Response(file_get_contents($tmpfile), 200, array(
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $rapportVisitePdf->filename . '"'
            )
        );

        if($request->get('service')) {

            return $this->redirect($request->get('service'));
        }

    }

    /**
     * @Route("/passage/pdf-rapport-download/{id}", name="passage_pdf_rapport_download")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function pdfRapportDownloadAction(Request $request, Passage $passage) {
        $rapportVisitePdf = $this->createRapportVisitePdf($passage);
        if ($request->get('output') == 'html') {

            return new Response($rapportVisitePdf->html, 200);
        }

        if (! shell_exec(sprintf("which %s", escapeshellarg('pdftk')))) {
            throw new \LogicException('missing pdftk binary');
        }

        if( !$rapportVisitePdf->pdfs){
            return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($rapportVisitePdf->html, $this->getPdfGenerationOptions()), 200, array(
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $rapportVisitePdf->filename . '"'
                )
            );
        }

        $tmpfile = $this->container->getParameter('kernel.cache_dir').'/PDFRAPPORT_'.$passage->getId().uniqid();
        $this->get('knp_snappy.pdf')->generateFromHtml($rapportVisitePdf->html,$tmpfile,$this->getPdfGenerationOptions());

        foreach($rapportVisitePdf->pdfs as $pdf){
            $filename  = '/tmp/output-pdf-'.$passage->getId().rand().'.pdf';
            $concat = "/tmp/output-pdf-concact-".$passage->getId().rand();
            file_put_contents($filename,base64_decode($pdf->getBase64()));
            exec(escapeshellcmd('pdftk '.$tmpfile.' '.$filename.' cat output '.$concat), $output, $exitcode);
            $tmpfile = $concat;
            unlink($filename);
        }

        if ($exitcode !== 0) {
            throw new \Exception('pdftk failed with error: '.implode(', ', $output));
        }

        return new Response(file_get_contents($tmpfile), 200, array(
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $rapportVisitePdf->filename . '"'
            )
        );

        if($request->get('service')) {

            return $this->redirect($request->get('service'));
        }

    }

    /**
     * @Route("/passage/passage-transmission-email/{id}", name="passage_transmission_email")
     * @ParamConverter("passage", class="AppBundle:Passage")
     */
    public function transmissionEmailAction(Request $request, Passage $passage) {

        $rapportVisitePdf = $this->createRapportVisitePdf($passage);
        if ($request->get('output') == 'html') {

            return new Response($rapportVisitePdf->html, 200);
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $pm = $this->get('passage.manager');
        $parameters = $pm->getParameters();
        $appname = $this->container->getParameter('instanceapp');

        if(!$parameters['coordonnees'] || !$parameters['coordonnees']['email'] || !$parameters['coordonnees']['nom']){
            throw new Exception("Le paramétrage pour le mail d'envoie n'est pas correct.");
        }

        $fromEmail = $parameters['coordonnees']['email'];
        $fromName = $parameters['coordonnees']['nom'];

        $replyEmail = $parameters['coordonnees']['replyEmail'];

        $suject = "[".ucfirst($appname)."] - Rapport de visite du ".$passage->getDateDebut()->format("d/m/Y")." à ".$passage->getDateDebut()->format("H\hi");
        $body = $this->renderView(
            'passage/rapportEmail.html.twig',
            array('passage' => $passage)
        );

        $message = \Swift_Message::newInstance()
            ->setSubject($suject)
            ->setFrom(array($fromEmail => $fromName))
            ->setTo(explode(";",$passage->getEmailTransmission()))
            ->setReplyTo($replyEmail)
            ->setBody($body,'text/plain');

        if ($passage->getSecondEmailTransmission()) {
            $emailsTransmissions = explode(";",$passage->getEmailTransmission());
            $secondEmailsTransmission = explode(";",$passage->getSecondEmailTransmission());
            $to = array_merge($emailsTransmissions,$secondEmailsTransmission);
            $message->setTo($to);
        }

        if( !$rapportVisitePdf->pdfs){
            $attachment = \Swift_Attachment::newInstance($this->get('knp_snappy.pdf')->getOutputFromHtml($rapportVisitePdf->html, $this->getPdfGenerationOptions()), $rapportVisitePdf->filename, 'application/pdf');
        }else{
            $tmpfile = $this->container->getParameter('kernel.cache_dir').'/PDFRAPPORT_'.$passage->getId().uniqid();
            $this->get('knp_snappy.pdf')->generateFromHtml($rapportVisitePdf->html,$tmpfile,$this->getPdfGenerationOptions());

            foreach($rapportVisitePdf->pdfs as $pdf){
                $filename  = '/tmp/output-pdf-'.$passage->getId().rand().'.pdf';
                $concat = "/tmp/output-pdf-concact-".$passage->getId().rand().'.pdf';
                file_put_contents($filename,base64_decode($pdf->getBase64()));
                exec(escapeshellcmd('pdftk '.$tmpfile.' '.$filename.' cat output '.$concat), $output, $exitcode);
                $tmpfile = $concat;
            }
            if ($exitcode !== 0) {
                throw new \Exception('pdftk failed with error: '.implode(', ', $output));
            }

            $attachment = \Swift_Attachment::newInstance(file_get_contents($tmpfile), $rapportVisitePdf->filename, 'application/pdf');
        }


        $message->attach($attachment);

        try {
            $this->get('mailer')->send($message);
            $passage->setPdfNonEnvoye(false);
            $passage->setPdfRapportDateEnvoi(new \DateTime());
            $dm->flush();

        }
        catch(Exception $e) {
            var_dump('NO mailer config'); exit;
        }

        if($request->get('service')) {

            return $this->redirect($request->get('service'));
        }

        return $this->redirectToRoute('passage_etablissement', array('id' => $passage->getEtablissement()->getId()));
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
        $dm = $this->get('doctrine_mongodb')->getManager();

        $passagesHistory = $pm->getRepository()->findHistoriqueByEtablissementAndPrestationsAndNumeroContrat($passage->getContrat(), $passage->getEtablissement(), $passage->getPrestations());

        $passage->setImprime(true);
        $dm->flush();

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
            $passages = $pm->getRepository()->findAllPlanifieByPeriodeAndIdentifiantTechnicien($request->get('dateDebut'), $request->get('dateFin'), $technicien, false);
            $filename = sprintf("suivis_client_%s_%s_%s.pdf", $request->get('dateDebut'), $request->get('dateFin'), strtoupper(Transliterator::urlize($technicien->getIdentite())));
        } else {
            $passages = $pm->getRepository()->findAllPlanifieByPeriode($request->get('dateDebut'), $request->get('dateFin'));
            $filename = sprintf("suivis_client_%s_%s.pdf", $request->get('dateDebut'), $request->get('dateFin'), true);
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

    private function createRapportVisitePdf(Passage $passage){
        $createRapportVisitePdf = new \stdClass();
        $dm = $this->get('doctrine_mongodb')->getManager();
        $fm = $this->get('facture.manager');
        $pm = $this->get('passage.manager');
        $prestationArray = $dm->getRepository('AppBundle:Configuration')->findConfiguration()->getPrestationsArray();

        $documents = $this->get('attachement.manager')->getRepository()
                         ->findByPassageAndVisibleClient($passage);
        $images = [];
        $pdfs = [];

        foreach($documents as $document){
            $attachement = $this->get('attachement.manager')->getRepository()->findForAttachements($document->getId(),LockMode::PESSIMISTIC_READ);
            if($attachement->isPdf()){
                $pdfs[] = $attachement;
            }
            else{
                $images[] = $attachement->getBase64Src();
            }
        }
        $createRapportVisitePdf->html = $this->renderView('passage/pdfRapport.html.twig', array(
            'passage' => $passage,
            'parameters' => $fm->getParameters(),
            'pm' => $pm,
            'prestationArray' => $prestationArray,
            'images' => $images
        ));

        $createRapportVisitePdf->pdfs = $pdfs;

        $createRapportVisitePdf->filename = sprintf("passage_rapport_%s_%s.pdf", $passage->getDateDebut()->format("Y-m-d_H:i"), strtoupper(Transliterator::urlize($passage->getEtablissement()->getIntitule())));

        return $createRapportVisitePdf;

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
