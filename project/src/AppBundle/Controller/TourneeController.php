<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Document\Passage as Passage;
use AppBundle\Document\Devis as Devis;
use AppBundle\Document\Attachement;
use AppBundle\Type\PassageMobileType;
use AppBundle\Type\DevisMobileType;
use AppBundle\Type\AttachementTourneeType;

class TourneeController extends Controller {

    /**
     * @Route("/tournees/{date}", name="tournees", defaults={"date" = "0"})
     */
    public function indexAction(Request $request, $date) {

        $dm = $this->get('doctrine_mongodb')->getManager();
        if($date == "0"){
          $date = new \DateTime();
        }else{
          $date = \DateTime::createFromFormat('Y-m-d',$date);
        }

        $passageManager = $this->get('passage.manager');
        $passagesForAllTechniciens = $passageManager->getRepository()->findAllPassagesForTechnicien($date);

        $passageManager = $this->get('devis.manager');
        $devisForAllTechniciens = $passageManager->getRepository()->findAllDevisForTechnicien($date);

        $planifiablesByTechniciens = $this->sortPlanifiablesByTechnicien(array_merge($passagesForAllTechniciens, $devisForAllTechniciens));
        return $this->render('tournee/index.html.twig', array('planifiablesByTechniciens' => $planifiablesByTechniciens, "date" => $date));
    }

    /**
     * @Route("/tournee-technicien/tournee/{technicien}/{date}", name="tournee_technicien", defaults={"date" = "0"})
     */
    public function tourneeTechnicienAction(Request $request,$technicien, $date) {

        $dm = $this->get('doctrine_mongodb')->getManager();
        if($date == "0"){
          $date = new \DateTime();
        }else{
          $date = \DateTime::createFromFormat('Y-m-d',$date);
        }

        $technicien = $request->get('technicien');
        $technicienObj = null;
        if ($technicien) {
            $technicienObj = $dm->getRepository('AppBundle:Compte')->findOneById($technicien);
        }
        $pm = $this->get('passage.manager');
        $parameters = $pm->getParameters();
        if(!$parameters['coordonnees'] || !$parameters['coordonnees']['numero']){
          throw new Exception("Le paramétrage du numéro de téléphone n'est pas correct.");
        }
        $telephoneSecretariat = $parameters['coordonnees']['numero'];

        $rendezVousByTechnicien = $this->get('rendezvous.manager')->getRepository()->findByDateDebutAndParticipant($date->format('Y-m-d'),$technicienObj);

        $historiqueAllPassages = array();
        $historiquePassagesEtablissement = [];
        $planifiableForms = array();
        $attachementsForms = array();
        $attachements = [];

        $etablissements = array_map(function ($r) {
            if ($r->getPlanifiable()) return $r->getPlanifiable()->getEtablissement()->getId();
        }, $rendezVousByTechnicien->toArray());
        $etablissements = $dm->getRepository('AppBundle:Etablissement')->createQueryBuilder()
                                                     ->field('id')->in(array_filter($etablissements, function ($v) { return !is_null($v); }))
                                                     ->getQuery()
                                                     ->execute()
                                                     ->toArray();

        $attachementsResult = $dm->getRepository('AppBundle:Attachement')->createQueryBuilder()->exclude('base64')
                                                     ->field('etablissement')->in(array_filter(array_keys($etablissements), function ($v) { return !is_null($v); }))
                                                     ->getQuery()
                                                     ->execute()
                                                     ->toArray();

        foreach($attachementsResult as $attachement) {
            $attachements[$attachement->getEtablissement()->getId()][] = $attachement;
        }

        $version = $this->getVersionManifest($technicienObj->getId(),$date->format('Y-m-d'));

        foreach ($rendezVousByTechnicien as $rendezVous) {
            $planifiable = $rendezVous->getPlanifiable();
            $previousPlanifiable = null;
            if($planifiable){
                $etbId = $planifiable->getEtablissement()->getId();
                if($planifiable->getTypePlanifiable() == Passage::DOCUMENT_TYPE){
                    $historiqueAllPassages[$planifiable->getId()] = $this->get('contrat.manager')->getHistoriquePassagesByNumeroArchive($planifiable, 2);
                    $historiquePassagesEtablissement[$planifiable->getId()] = $this->get('passage.manager')->getRepository('AppBundle:Passage')->findPassageForEtablissementBeforeCurrentPassageDate($etbId, $planifiable->getDureeDate());
                    $previousPlanifiable = null;
                    foreach ($historiqueAllPassages[$planifiable->getId()] as $hPassage) {
                        $this->get('passage.manager')->synchroniseProduitsWithConfiguration($hPassage);

                        if(!$previousPlanifiable || !$previousPlanifiable->getDateDebut() || ($previousPlanifiable->getDateDebut() < $hPassage->getDateDebut())){
                            $previousPlanifiable = $hPassage;
                        }
                    }
                    foreach ($historiquePassagesEtablissement[$planifiable->getId()] as $hPassage) {
                        $this->get('passage.manager')->synchroniseProduitsWithConfiguration($hPassage);
                    }
                }
                $planifiableTypeName = "AppBundle\\Type\\".$planifiable->getTypePlanifiable()."MobileType";
                $formPlanifiable = new $planifiableTypeName($dm, $planifiable->getId(), $previousPlanifiable);

                $planifiableForms[$planifiable->getId()] = $this->createForm($formPlanifiable, $planifiable, array(
                    'action' => $this->generateUrl('tournee_planifiable_rapport', array('planifiable' => $planifiable->getId(),'technicien' => $technicienObj->getId())),
                    'method' => 'POST',
                ))->createView();


                $attachementsForms[$etbId] = array('form' => $this->createForm(new AttachementTourneeType($dm), new Attachement(), array(
                    'action' => $this->generateUrl('tournee_attachement_upload', array('technicien' => $technicien, 'date' => $date->format('Y-m-d'),'idetablissement' => $etbId,'retour' => 'visualisation_'.$planifiable->getId())),
                    'method' => 'POST',
                ))->createView(),
                'action' => $this->generateUrl('tournee_attachement_upload', array('technicien' => $technicien, 'date' => $date->format('Y-m-d'),'idetablissement' => $etbId, 'retour' => 'visualisation_'.$planifiable->getId())));
            }
        }

        $jourFerieJsonContent = json_decode(file_get_contents($this->get('kernel')->getRootDir().'/../web/joursFeries.json'), true);
        $isJourFerie = false;
        foreach($jourFerieJsonContent as $c){
            if( substr($c['start'], 0, 10) == $date->format('Y-m-d')){
                $isJourFerie = true;
                break;
            }
        }
        return $this->render('tournee/tourneeTechnicien.html.twig', array('rendezVousByTechnicien' => $rendezVousByTechnicien,
                                                                          "technicien" => $technicienObj,
                                                                          "isAdmin" => array_key_exists('PHP_AUTH_USER', $_SERVER) && preg_match('/^[a-zA-Z]+$/', $_SERVER['PHP_AUTH_USER']) === 1,
                                                                          "date" => $date,
                                                                          "version" => $version,
                                                                          "historiqueAllPassages" => $historiqueAllPassages,
                                                                          "historiquePassagesEtablissement" => $historiquePassagesEtablissement,
                                                                          'telephoneSecretariat' => $telephoneSecretariat,
                                                                          "planifiableForms" => $planifiableForms,
                                                                          "attachementsForms" => $attachementsForms,
                                                                          'attachements' => $attachements,
                                                                          "isJourFerie" => $isJourFerie));
    }

    /**
     * @Route("/tournee-technicien/version/{technicien}", name="tournee_version")
     */
     public function tourneeVersionAction(Request $request,$technicien) {

         $dm = $this->get('doctrine_mongodb')->getManager();
         $date = $request->get('date',null);
         if(!$date){
           $dateTime = new \DateTime();
           $date = $dateTime->format('Y-m-d');
         }
         $technicien = $request->get('technicien');
         $technicienObj = null;
         if ($technicien) {
             $technicienObj = $dm->getRepository('AppBundle:Compte')->findOneById($technicien);
         }

         $version = $this->getVersionManifest($technicienObj->getId(),$date);

         return new Response(json_encode(array("success" => true,"version" => $version)));
     }

     /**
      * @Route("/tournee-technicien/planifiable-rapport/{planifiable}/{technicien}", name="tournee_planifiable_rapport")
      */
     public function tourneePlanifiableRapportAction(Request $request, $planifiable) {

         $dm = $this->get('doctrine_mongodb')->getManager();
         $technicien = $request->get('technicien');
         $technicienObj = null;
         if ($technicien) {
             $technicienObj = $dm->getRepository('AppBundle:Compte')->findOneById($technicien);
         }

         $planifiable = $request->get('planifiable');
         if(is_string($planifiable)){
           $planifiableFound = $dm->getRepository('AppBundle:Passage')->findOneById($planifiable);
           if(!$planifiableFound){
             $planifiableFound = $dm->getRepository('AppBundle:Devis')->findOneById($planifiable);
           }
           if($planifiableFound){
             $planifiable = $planifiableFound;
           }
         }
         switch (get_class($planifiable)) {
             case Devis::class:
               return $this->postTourneeDevisRapport($request, $planifiable, $technicienObj);

             case Passage::class:
                 return $this->postTourneePassageRapport($request, $planifiable, $technicienObj);
         }
         return null;
     }


    protected function postTourneePassageRapport($request,$passage, $technicienObj) {

        $dm = $this->get('doctrine_mongodb')->getManager();
        $historiqueAllPassages[$passage->getId()] = $this->get('contrat.manager')->getHistoriquePassagesByNumeroArchive($passage, 2);
        $previousPassage = null;
        foreach ($historiqueAllPassages[$passage->getId()] as $hPassage) {
          $this->get('passage.manager')->synchroniseProduitsWithConfiguration($hPassage);
          if(!$previousPassage || !$previousPassage->getDateDebut() || ($previousPassage->getDateDebut() < $hPassage->getDateDebut())){
              $previousPassage = $hPassage;
          }
        }

        $form = $this->createForm(new PassageMobileType($dm, $passage->getId(), $previousPassage), $passage, array(
            'action' => $this->generateUrl('tournee_planifiable_rapport', array('planifiable' => $passage->getId(),'technicien' => $technicienObj->getId())),
            'method' => 'POST',
        ));

        $form->handleRequest($request);

        $contrat = $dm->getRepository('AppBundle:Contrat')->findOneById($passage->getContrat()->getId());

        if ($passage->getMouvementDeclenchable() && !$passage->getMouvementDeclenche()) {
            if ($contrat->generateMouvement($passage)) {
                $passage->setMouvementDeclenche(true);
            }
        }

        $passage->setDateRealise($passage->getDateDebut());

        $passage->setSaisieTechnicien(($passage->getEmailTransmission() || $passage->getNomTransmission() || $passage->getSignatureBase64()) && $passage->getDescription() && $passage->getDuree());

        if($passage->isPdfEnvoye() === false){
            $passage->setPdfNonEnvoye(true);
        }

        $dm->persist($passage);
        $dm->flush();

        $contrat = $dm->getRepository('AppBundle:Contrat')->findOneById($passage->getContrat()->getId());
        $contrat->verifyAndClose();

        $dm->flush();

        return $this->redirectToRoute('tournee_technicien', array("technicien" => $technicienObj->getId(), "date" => $passage->getDateDebut()->format("Y-m-d")));
    }

    protected function postTourneeDevisRapport($request, $devis, $technicienObj) {

        $dm = $this->get('doctrine_mongodb')->getManager();

        $form = $this->createForm(new DevisMobileType($dm, $devis->getId()), $devis, array(
            'action' => $this->generateUrl('tournee_planifiable_rapport', array('planifiable' => $devis->getId(),'technicien' => $technicienObj->getId())),
            'method' => 'POST',
        ));

        $form->handleRequest($request);



        // if ($passage->getMouvementDeclenchable() && !$passage->getMouvementDeclenche()) {
        //     if ($contrat->generateMouvement($passage)) {
        //         $passage->setMouvementDeclenche(true);
        //     }
        // }

         $devis->setDateRealise($devis->getDateDebut());
         $devis->setSaisieTechnicien($devis->getSignatureBase64() || $devis->getDescription());

        // if(!$devis->getPdfNonEnvoye()){
           $devis->setPdfNonEnvoye(true);
        // }
        $dm->persist($devis);
        $dm->flush();


        return $this->redirectToRoute('tournee_technicien', array("technicien" => $technicienObj->getId(), "date" => $devis->getDateDebut()->format("Y-m-d")));
    }

    /**
     * @Route("/tournee-technicien/attachement/{technicien}/{date}/{idetablissement}/ajout/{retour}", name="tournee_attachement_upload", defaults={"date" = "0"})
     */
    public function attachementUploadAction(Request $request, $technicien,$date,$idetablissement,$retour) {
       $technicien = $request->get('technicien');
       $date = $request->get('date');
       $retour = $request->get('retour');
       $attachement = new Attachement();
       $dm = $this->get('doctrine_mongodb')->getManager();
       $etablissement = $this->get('etablissement.manager')->getRepository()->find($idetablissement);
       $uploadAttachementForm = $this->createForm(new AttachementTourneeType($dm), $attachement, array(
           'action' => $this->generateUrl('tournee_attachement_upload', array('technicien' => $technicien, 'date' => $date,'idetablissement' => $idetablissement,'retour' => $retour)),
           'method' => 'POST',
       ));
       if ($request->isMethod('POST')) {
           $uploadAttachementForm->handleRequest($request);
           if($uploadAttachementForm->isValid()){
             $files = $uploadAttachementForm->getData()->getImageFile();
             if($files){
                 foreach ($files as $file) {
                     $file->setVisibleTechnicien(true);
                     $file->setVisibleClient(true);
                     $file->setEtablissement($etablissement);
                     $dm->persist($file);
                     $etablissement->addAttachement($file);
                     $dm->flush();
                     $file->convertBase64AndRemove();
                     $dm->flush();
                 }
             }
           }
           $urlRetour = $this->generateUrl('tournee_technicien', array('technicien' => $technicien, 'date' => $date))."#".$retour;
           return $this->redirect($urlRetour);
       }
   }


   public function sortPlanifiablesByTechnicien($planifiablesForAllTechniciens){
       $planifiablesByTechniciens = array();
       foreach ($planifiablesForAllTechniciens as $planifiable) {
         foreach ($planifiable->getTechniciens() as $technicien) {
           if(!array_key_exists($technicien->getId(),$planifiablesByTechniciens)){
             $planifiablesByTechniciens[$technicien->getId()] = new \stdClass();
             $planifiablesByTechniciens[$technicien->getId()]->technicien = $technicien;
             $planifiablesByTechniciens[$technicien->getId()]->planifiables = array();

           }
           $planifiablesByTechniciens[$technicien->getId()]->planifiables[$planifiable->getId()] = $planifiable;
         }
       }
       return $planifiablesByTechniciens;
   }


    /**
     * @Route("/tournee-technicien/manifest/{technicien}", name="manifest")
     */
    public function manifestAction(Request $request) {
      $dm = $this->get('doctrine_mongodb')->getManager();
      $version = $request->get('version', null);
      $date = $request->get('date', null);
      if(!$date){
        $dateTime = new \DateTime();
        $date = $dateTime->format('Y-m-d');
      }

      $technicien = $request->get('technicien');
      $technicienObj = null;
      if ($technicien) {
          $technicienObj = $dm->getRepository('AppBundle:Compte')->findOneById($technicien);
      }
      $versionManifest = ($version)? $version : $this->getVersionManifest($technicien,$date);

      $response = new Response();
      $response->headers->set('Content-Type', 'text/cache-manifest');

      return $this->render('tournee/manifest.twig', array('version' => $versionManifest),$response);
    }



    private function getVersionManifest($technicien,$date){
      return $this->get('passage.manager')->getRepository()->findLastDateModificationPassagesForTechnicien($technicien,$date);
    }

}
