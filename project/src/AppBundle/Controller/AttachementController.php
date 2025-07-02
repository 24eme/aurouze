<?php

namespace AppBundle\Controller;

use AppBundle\Type\AttachementModificationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\Type\EtablissementChoiceType;
use AppBundle\Type\SocieteType;
use AppBundle\Type\AttachementType;
use AppBundle\Document\Societe;
use AppBundle\Document\Etablissement;
use AppBundle\Manager\EtablissementManager;
use AppBundle\Document\Attachement;

class AttachementController extends Controller {

    /**
     * @Route("/derniers-documents", name="attachements_last")
     */
    public function indexAction(Request $request) {

    	$dm = $this->get('doctrine_mongodb')->getManager();
        $lastAttachements = $this->get('attachement.manager')->getRepository()->findLast();
    	return $this->render('attachement/index.html.twig',array('lastAttachements' => $lastAttachements));
    }

    /**
     * @Route("/attachement/download/{id}", name="attachement_download")
     */
    public function downloadAction(Request $request, $id) {

    	$dm = $this->get('doctrine_mongodb')->getManager();
        $attachement = $this->get('attachement.manager')->getRepository()->find($id);

        $tmpfilename = tempnam(sys_get_temp_dir(), 'download');
        file_put_contents($tmpfilename, base64_decode($attachement->getBase64()));

        $response = new BinaryFileResponse($tmpfilename);
        $response->deleteFileAfterSend(true);

        if(!$request->get('noattachment')) {
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $attachement->getOriginalName()
            );
        }

        return $response;
    }

    /**
     * @Route("/attachement/societe/{id}/documents", name="attachements_societe")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function visualisationSocieteAction(Request $request, $id) {
        return $this->visualisationAction($request, $id);
    }

    /**
     * @Route("/attachement/etablissement/{id}/documents", name="attachements_etablissement")
     * @ParamConverter("etablissement", class="AppBundle:Etablissement")
     */
    public function visualisationEtablissementAction(Request $request, $id) {
        return $this->visualisationAction($request, $id);
    }

    /**
     * @Route("/attachement/{id}/documents", name="attachements_entite")
     */
    public function visualisationAction(Request $request, $id) {
      $dm = $this->get('doctrine_mongodb')->getManager();
      $attachement = new Attachement();
      if($request->get('numerocontrat')) {
          $attachement->setNumeroContrat($request->get('numerocontrat'));
      }
      $attachementRepository = $this->get('attachement.manager')->getRepository();
      $etablissement = null;
      $societe = null;
      $all = boolval($request->get('all'));
      $attachements = [];

      $societe = $this->get('societe.manager')->getRepository()->find($id);
      if(!$societe) {
        $etablissement = $this->get('etablissement.manager')->getRepository()->find($id);
        $societe = $etablissement->getSociete();
      }

      $urlForm = $this->generateUrl('societe_upload_attachement', array('id' => $societe->getId()));

      $actif = $societe;
      $facets = $attachementRepository->getSocieteAndEtablissements($societe, true);

      if(!$all && !$etablissement) { // visu société
          $attachements = $attachementRepository->findBySociete($societe);
      } elseif($etablissement) { // visu établissement
          $actif = $etablissement;
          $urlForm = $this->generateUrl('etablissement_upload_attachement', array('id' => $etablissement->getId()));
          $attachements = $attachementRepository->findByEtablissement($etablissement);


      } else { // visu tous les documents
          $attachements = $attachementRepository->getSocieteAndEtablissements($societe);
      }
      uasort($attachements, array("AppBundle\Document\Attachement", "cmpUpdateAt"));

      $attachementsGroupedByDate = [];
      foreach($attachements as $attachement) {
              $updatedDate = $attachement->getUpdatedAt()->format('Y-m-d');

              if (!isset($attachementsGroupedByDate[$updatedDate])) {
                  $attachementsGroupedByDate[$updatedDate] = [];
              }

              $attachementsGroupedByDate[$updatedDate][] = $attachement;
      }

      $form = $this->createForm(new AttachementType($dm), $attachement, array(
              'action' => $urlForm,
              'method' => 'POST',
      ));

      return $this->render('attachement/listing.html.twig', array('attachements'  => $attachements, 'societe' => $societe, 'etablissement' => $etablissement, 'actif' => $actif, 'urlForm' => $urlForm, 'form' => $form->createView(), 'attachementsGroupedByDate' => $attachementsGroupedByDate, 'all' => $all, 'facets' => $facets, 'add' => boolval($request->get('add'))));
    }

    /**
    * @Route("/attachement/{id}/supprimer", name="attachement_delete")
    */
    public function attachementDeleteAction(Request $request, $id) {
       $attachement = $this->get('attachement.manager')->getRepository()->find($id);

       $noremove = $request->get('noremove',false);
       $entite = $attachement->getSociete();
       if(!$entite){
         $entite = $attachement->getEtablissement();
       }
       if(!$entite){
         throw new \Exception('Une erreur s\'est produite : le document '.$attachement->getId().' ne semble être relié à rien!');

       }
       $dm = $this->get('doctrine_mongodb')->getManager();

       try {
           if($noremove){
               $attachement->removeFile();
           }
       } catch (\Symfony\Component\Debug\Exception\ContextErrorException $e) {
         //do nothing
       }

       $dm->remove($attachement);
       $dm->flush();

       return $this->redirectToRoute('attachements_entite', array('id' => $entite->getId()));
   }

   /**
    * @Route("/attachement/{id}/visibility/toggle/{type}", name="attachement_update_visibility", requirements={
    *       "type"="client|technicien"
    * })
   */
   public function attachementUpdateVisibleAction(Request $request) {
      $dm = $this->get('doctrine_mongodb')->getManager();
      $attachement = $dm->getRepository('AppBundle:Attachement')->findOneById($request->get('id'));

      $type = ucfirst($request->get('type'));
      $getVisibilite = 'getVisible'.$type;
      $setVisibilite = 'setVisible'.$type;

      $entite = $attachement->getSociete();

      if(!$entite){
          $entite = $attachement->getEtablissement();
      }

      if(!$entite){
          throw new \Exception('Une erreur s\'est produite : le document '.$attachement->getId().' ne semble être relié à rien!');
      }

      if ($attachement->$getVisibilite() === null) {
          $attachement->$setVisibilite(false);
      }

      $attachement->$setVisibilite(! $attachement->$getVisibilite());

      $dm->persist($attachement);
      $dm->flush();

      return $this->redirectToRoute('attachements_entite', array('id' => $entite->getId()));
  }

   /**
   * @Route("/societe/attachement/{id}/ajout", name="societe_upload_attachement", methods={"POST", "PUT"})
   */
   public function attachementSocieteUploadAction(Request $request, $id) {
      ini_set ('gd.jpeg_ignore_warning', 1);
      $attachement = new Attachement();
      $dm = $this->get('doctrine_mongodb')->getManager();
      $societe = $this->get('societe.manager')->getRepository()->find($id);
      $uploadAttachementForm = $this->createForm(new AttachementType($dm), $attachement, array(
              'action' => $this->generateUrl('societe_upload_attachement', array('id' => $id)),
              'method' => 'POST',
      ));

      if ($request->isMethod('POST')) {
          $uploadAttachementForm->handleRequest($request);
          if($uploadAttachementForm->isValid()){
            $attachement->setSociete($societe);
            $dm->persist($attachement);
            $societe->addAttachement($attachement);
            $dm->flush();

            $attachement->convertBase64AndRemove();
            $dm->flush();
            }
        return $this->redirectToRoute('attachements_societe', array('id' => $societe->getId()));
      }
  }

   /**
   * @Route("/etablissement/attachement/{id}/ajout", name="etablissement_upload_attachement")
   */
   public function attachementEtablissementUploadAction(Request $request, $id) {
      ini_set ('gd.jpeg_ignore_warning', 1);
      $attachement = new Attachement();
      $dm = $this->get('doctrine_mongodb')->getManager();
      $etablissement = $this->get('etablissement.manager')->getRepository()->find($id);
      $uploadAttachementForm = $this->createForm(new AttachementType($dm), $attachement, array(
          'action' => $this->generateUrl('etablissement_upload_attachement', array('id' => $id)),
          'method' => 'POST',
      ));

      if ($request->isMethod('POST')) {
          $uploadAttachementForm->handleRequest($request);
          if($uploadAttachementForm->isValid()){

            $attachement->setEtablissement($etablissement);
            $dm->persist($attachement);
            $etablissement->addAttachement($attachement);

            $attachement->convertBase64AndRemove();
            if (!$attachement->verifyBase64Bytes()) {
                    $request->getSession()->getFlashBag()->add('upload_error_mongo', "Le fichier est trop gros, veuillez le compresser s'il s'agit d'un PDF ");
                    return $this->redirectToRoute('attachements_etablissement', array('id' => $etablissement->getId()));
            }
            $dm->flush();

          } else {
              foreach ($uploadAttachementForm->getErrors(true, true) as $formError) {
                  $request->getSession()->getFlashBag()->add('upload_error', $formError->getMessage());
              }

          }

          return $this->redirectToRoute('attachements_etablissement', array('id' => $etablissement->getId()));
      }
  }

    /**
   * @Route("/etablissement/attachement/{id}/modification", name="attachement_modification")
   */
    public function attachementModificationAction(Request $request, $id) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $attachement = $dm->getRepository('AppBundle:Attachement')->findForAttachements($request->get('id'));
        $societe = $attachement->getSociete();
        $etablissement = $attachement->getEtablissement();
        $form = $this->createForm(new AttachementModificationType($dm), $attachement);

        if($request->isMethod('POST')) {
            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()) {
                $attachement = $form->getData();
                $dm->persist($attachement);
                $dm->flush();

                if(!is_null($societe)) {
                return $this->redirectToRoute('attachements_societe', array('id' => $societe->getId()));

                }elseif (!is_null($etablissement)) {
                    return $this->redirectToRoute('attachements_etablissement', array('id' => $etablissement->getId()));

                }else{
                    return $this->redirectToRoute('attachements_last');
                }
            }
        }
        return $this->render('attachement/modification.html.twig', array('form' => $form->createView()));
    }

}
