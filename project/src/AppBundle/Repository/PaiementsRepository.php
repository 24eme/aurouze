<?php

namespace AppBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use AppBundle\Document\Societe;
use AppBundle\Tool\RechercheTool;
use Behat\Transliterator\Transliterator;

class PaiementsRepository extends BaseRepository {

    public function findPaiementsByFacture($facture) {
        return $this->createQueryBuilder()
                        ->select('paiement')
                        ->field('paiement.facture')
                        ->equals($facture->getId())
                        ->getQuery()
                        ->getIterator();
    }

    public function getLastPaiements($nbLimit) {
        return $this->createQueryBuilder()
                        ->sort('dateCreation', 'desc')
                        ->limit($nbLimit)
                        ->getQuery()
                        ->execute();
    }

    public function getMontantPaye($societe, $factureIds = null) {
      $command = array();
      $command['aggregate'] = "Paiements";
      $command['pipeline'] = array(
          array('$unwind' => '$paiement'),
          array('$match' => array('paiement.societe' => $societe->getId(), 'paiement.typeReglement' => ['$ne' => 'REGULARISATION_AVOIR'])),
          array('$group' => array('_id' => 'somme_montant_paye', 'montant' => array('$sum' => '$paiement.montant')))
      );
      if(!is_null($factureIds)) {
          $command['pipeline'][1]['$match']['paiement.facture'] = array('$in' => $factureIds);
      }
      $db = $this->getDocumentManager()->getDocumentDatabase(\AppBundle\Document\Paiements::class);
      $resultat = $db->command($command);
      return (isset($resultat['result'][0]))? $resultat['result'][0]['montant'] : 0;
    }

    public function getBySociete(Societe $societe) {
        return $this->createQueryBuilder()
                        ->field('paiement.societe')->equals($societe->getId())
                        ->sort('dateCreation', 'desc')
                        ->getQuery()
                        ->execute();
    }

    public function findByDate(\DateTime $dateFrom,\DateTime $dateTo) {

        $q = $this->createQueryBuilder();

        $q->field('paiement.datePaiement')->gte($dateFrom);
        $q->field('paiement.datePaiement')->lte($dateTo);

        $query = $q->getQuery();

        return $query->execute();
    }

    public function findByDatePaiementsDebutFin(\DateTime $dateDebut,\DateTime $dateFin) {

        $q = $this->createQueryBuilder();
        $q->field('paiement.datePaiement')->gte($dateDebut);
        $q->field('paiement.datePaiement')->lte($dateFin);
        $query = $q->getQuery();

        return $query->execute();
    }



    public function findByPeriode($periode,$prelevement = false) {
    	if (!preg_match('/^([0-9]{2})\/([0-9]{4})$/', $periode, $items)) {
            return array();
        }
        $dateDebut = new \DateTime($items[2].'-'.$items[1].'-01');
        $dateFin = new \DateTime($items[2].'-'.$items[1].'-'.$dateDebut->format('t'));
    	$q = $this->createQueryBuilder();
    	$q->field('dateCreation')->gte($dateDebut);
    	$q->field('dateCreation')->lte($dateFin);
        $q->field('paiement.facture')->prime(true);
        if($prelevement){
    	       $q->field('prelevement')->equals($prelevement);
        }else{
            $q->addOr($q->expr()->field('prelevement')->equals($prelevement))
               ->addOr($q->expr()->field('prelevement')->equals(null));
        }
    	$q->sort('dateCreation', 'desc');
    	$query = $q->getQuery();
        return $query->execute();
    }



    public function findByQuery($q)
    {
    	$qSearch = "\"".str_replace(" ", "\" \"", $q)."\"";
    	$resultSet = array();
    	$itemResultSet = $this->command([
    			'find' => 'Paiements',
    			'filter' => ['$text' => ['$search' => $qSearch]],
    			'projection' => ['score' => [ '$meta' => "textScore" ]],
    			'sort' => ['score' => [ '$meta' => "textScore" ]],
    			'limit' => 100

    	]);
    		foreach ($itemResultSet as $itemResult) {
    			$resultSet[] = array("doc" => $this->uow->getOrCreateDocument('\AppBundle\Document\Paiements', $itemResult), "score" => $itemResult['score']);
    		}
        if(!count($itemResultSet)){
          $itemResultSet = $this->createQueryBuilder()
                          ->field('paiement.libelle')->equals(new \MongoRegex('/' . $q . '.*/i'))
                          ->getQuery()
                          ->execute();
          foreach ($itemResultSet as $paiements) {
              $resultSet[] = array("doc" => $paiements, "score" => "1");
          }
        }
    	
    	return $resultSet;
    }

    public function findPaiementByQuery($q)
    {
    	$terms = explode(" ", trim(preg_replace("/[ ]+/", " ", $q)));
    	$items = $this->findByQuery($q);
    	$resultSet = array();
    	foreach ($items as $item) {
    		$paiements = $item['doc'];
    		foreach ($paiements->getPaiement() as $paiement) {
    			foreach($terms as $term) {
    				if(strlen($term) < 2) {
    					continue;
    				}
		    		if (preg_match('/.*'.RechercheTool::getCorrespondances($term).'.*/i', $paiement->getLibelle()) || preg_match('/.*'.RechercheTool::getCorrespondances($term).'.*/i', $paiement->getMontant())) {
		    			$key = ($paiement->getLibelle())? $paiement->getFacture()->getId().'-'.Transliterator::urlize($paiement->getLibelle()) : $paiement->getFacture()->getId().'-'.md5(microtime().rand());
		    			$resultSet[$key] = array("doc" => $item['doc'], "paiement" => $paiement, "doc" => $item['doc']);
		    		}
    			}
    		}
    	}
    	return $resultSet;
    }
}
