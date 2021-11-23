<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Type\SocieteChoiceType;
use AppBundle\Type\SocieteType;
use AppBundle\Type\AttachementType;
use AppBundle\Document\Societe;
use AppBundle\Document\Etablissement;
use AppBundle\Manager\EtablissementManager;
use AppBundle\Document\Attachement;

class SocieteController extends Controller {

    /**
     * @Route("/societe", name="societe")
     */
    public function indexAction(Request $request) {

    	$dm = $this->get('doctrine_mongodb')->getManager();

    	return $this->render('societe/index.html.twig');
    }
    protected static function cmpContacts($a, $b)
    {
    	return ($a['score'] > $b['score']) ? -1 : +1;
    }

    /**
     * @Route("/societe/selection", name="societe_choice")
     */
    public function societeChoiceAction(Request $request) {
    	$formData = $request->get('societe_choice');

    	return $this->redirectToRoute('societe_visualisation', array('id' => $formData['societes']));
    }

    /**
     * @Route("/societe/{id}/visualisation", name="societe_visualisation")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function visualisationAction(Request $request, $societe) {

      $dm = $this->get('doctrine_mongodb')->getManager();

      $nbContratsSociete = count($this->get('contrat.manager')->getRepository()->findBySociete($societe));

    	return $this->render('societe/visualisation.html.twig', array('societe' => $societe, 'nbContratsSociete' => $nbContratsSociete));
    }

    /**
     * @Route("/societe/modification/{id}", defaults={"id" = null}, name="societe_modification")
     */
    public function modificationAction(Request $request, $id) {

    	$dm = $this->get('doctrine_mongodb')->getManager();

    	$isNew = ($id)? false : true;
    	$societe = (!$isNew)? $this->get('societe.manager')->getRepository()->find($id) : new Societe();
        if(!$isNew){
            $societe->preInitRum($this->container->getParameter('instanceapp'));
        }

    	$form = $this->createForm(new SocieteType($this->container, $dm, $isNew), $societe, array(
    			'action' => $this->generateUrl('societe_modification', array('id' => $id)),
    			'method' => 'POST',
    	));
    	$form->handleRequest($request);
    	if ($form->isSubmitted() && $form->isValid()) {
    		$societe = $form->getData();
    		$dm->persist($societe);
            if ($isNew) {
                $societe->preInitRum($this->container->getParameter('instanceapp'));
            }
    		$dm->flush();
    		if ($isNew && $form->get("generer")->getData()) {
    			 $etablissement = new Etablissement();
    			 $etablissement->setSociete($societe);
    			 $etablissement->setNom($societe->getRaisonSociale());
    			 $etablissement->setType($societe->getType());
    			 $etablissement->setCommentaire($societe->getCommentaire());
    			 $dm->persist($etablissement);
    			 $dm->flush();
    		} elseif (!$societe->getActif()) {
    			foreach ($societe->getEtablissements() as $etablissement) {
    				if ($etablissement->getActif()) {
    					$etablissement->setActif(false);
    				}
    			}
    			$dm->flush();
    		}
    		return $this->redirectToRoute('societe_visualisation', array('id' => $societe->getId()));
    	}

    	return $this->render('societe/modification.html.twig', array('form' => $form->createView(), 'societe' => $societe,  'isNew' => $isNew));
    }

    /**
     * @Route("/societe/rechercher", name="societe_search")
     */
     public function societeSearchAction(Request $request) {
         $dm = $this->get('doctrine_mongodb')->getManager();
         $response = new Response();
         $societesResult = array();
         $withNonActif = (!$request->get('nonactif'))? false : $request->get('nonactif');
         $this->contructSearchResult($dm->getRepository('AppBundle:Societe')->findByTerms($request->get('term'), $withNonActif),  $societesResult);
         $data = json_encode($societesResult);
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


}
