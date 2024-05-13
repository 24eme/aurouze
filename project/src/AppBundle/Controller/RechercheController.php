<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RechercheController extends Controller {

	/**
	 * @Route("/recherche", name="recherche")
	 */
	public function indexAction(Request $request)
	{
		$dm = $this->get('doctrine_mongodb')->getManager();
        $query = $request->get('q');

		$pmr = $this->get('paiements.manager')->getRepository();
		if(!$query) {
			return $this->render('recherche/index.html.twig', array('query' => $query));
		}

		$searchable = array(
							"Societe" => "Societe",
							"Etablissement" => "Etablissement",
							"Compte" => "Compte",
							"Contrat" => "Contrat",
							"Passage" => "Passage",
							"Facture" => "Facture",
							"Devis" => "Devis",
							"Paiements" => "Paiements",
						);

		$resultats = array();
		$paiements = array();
		foreach($searchable as $collection => $libelle) {
			if ($collection == 'Paiements') {
				$items = $dm->getRepository('AppBundle:'.$collection)->findPaiementByQuery($query);
			}elseif ($collection == 'Facture') {
				$items = $dm->getRepository('AppBundle:'.$collection)->findByQuery($query);
				foreach ($items as $item) {
					$d = $item["doc"];
					$paiements[$d->getId()] = $pmr->findPaiementsByFacture($d);
				}
			}	else {
				$items = $dm->getRepository('AppBundle:'.$collection)->findByQuery($query);
			}
			if(!count($items)) {
				continue;
			}

			$resultats[$libelle] = $items;
		}



        //usort($result, array("AppBundle\Controller\RechercheController", "cmpContacts"));

		return $this->render('recherche/index.html.twig', array('query' => $query, 'resultats' => $resultats, 'searchable' => $searchable, 'paiements' => $paiements));
	}

	/**
	 * @Route("/recherche/societe", name="recherche_societe")
	 */
	public function societeAction(Request $request)
	{
		$dm = $this->get('doctrine_mongodb')->getManager();
        $query = $request->get('q');
		if(!$query) {
			$query = $request->get('term');
		}

        $inactif = $request->get('inactif', false);
        $inactif = ($inactif)? true : false;
				$serviceName = 'fos_elastica.finder.'.$this->container->getParameter('database_elasticsearch_name');
				$result = $dm->getRepository('AppBundle:Societe')->findByElasticQuery($this->container->get($serviceName), $query, $inactif, 10);

        $result = $this->contructSearchResultSociete($result);

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($result));
        return $response;
	}

	/**
	 * @Route("/recherche/contrat", name="recherche_contrat")
	 */
	public function contratAction(Request $request)
	{
		$dm = $this->get('doctrine_mongodb')->getManager();
        $query = $request->get('q');

        $result = $dm->getRepository('AppBundle:Contrat')->findByQuery($query);
        usort($result, array("AppBundle\Controller\RechercheController", "cmpContacts"));


        $result = $this->contructSearchResultContrat($result);

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($result));
        return $response;
	}

    /**
     * @Route("/recherche/tag", name="recherche_tag", defaults={"q": ""})
     */
    public function tagAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $query = $request->get('q');

        $results = $dm->getRepository('AppBundle:Societe')->findByTag($query);

        return $this->render('recherche/parTag.html.twig', ['resultats' => $results, 'query' => $query]);
    }

	public static function cmpContacts($a, $b)
	{
		return ($a['score'] > $b['score']) ? false : true;
	}

    public function contructSearchResultSociete($items)
    {
		$result = array();
        foreach ($items as $item) {
        	$object = $item['doc'];
            $newResult = new \stdClass();
            $newResult->id = ($item['instance'] == 'Societe')? $object->getId() : $object->getSociete()->getId();
            $newResult->object = $object->getId();
            $newResult->identifiant = $object->getIdentifiant();
            $newResult->icon = $object->getIcon();
            $newResult->libelle = $object->getLibelleComplet();
            $newResult->text = $object->getLibelleComplet();
            $newResult->instance = $item['instance'];
            $newResult->actif = ($object->getActif())? 1 : 0;
            $result[] = $newResult;
        }
        return $result;
    }

    public function contructSearchResultContrat($items)
    {
		$result = array();
        foreach ($items as $item) {
        	$object = $item['doc'];
            $newResult = new \stdClass();
            $newResult->id = $object->getId();
            $newResult->libelle = $object->getLibelle();
            $newResult->statut = $object->getStatutLibelle();
            $newResult->color = $object->getStatutCouleur();
            $newResult->identifiant = $object->getNumeroArchive();
            $newResult->type = $object->getTypeContratLibelle();
			$newResult->periode = "";
			if($object->getDateDebut()) {
				$newResult->periode = $object->getDateDebut()->format('M Y');
			}
			if($object->getDateFin()) {
				$newResult->periode .= '&nbsp;-&nbsp;'.$object->getDateFin()->format('M Y');
			}
            $newResult->prix = $object->getPrixHt();
            $newResult->garantie = ($object->getDureeGarantie())? 'Garantie&nbsp;'.$object->getDureeGarantie().'&nbsp;mois' : 'Aucune garantie';
            $result[] = $newResult;
        }
        return $result;
    }

}
