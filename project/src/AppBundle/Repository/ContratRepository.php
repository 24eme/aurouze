<?php

namespace AppBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use MongoDate as MongoDate;
use AppBundle\Manager\ContratManager;
use AppBundle\Repository\SocieteRepository;

class ContratRepository extends DocumentRepository {

    public function findBySociete($societe) {
        return $this->findBy(
                        array('societe' => $societe->getId()),
                        array('dateFin' => 'DESC'));
    }

    public function findByEtablissement($etablissement) {
        return $this->findBy(
                        array('societe' => $etablissement->getSociete()->getId()),
                        array('dateFin' => 'DESC'));
    }

    public function findContratMouvements($societe, $isFacturable, $isFacture) {
        return $this->createQueryBuilder()
             ->field('mouvements.societe')->equals($societe->getId())
             ->field('mouvements.facturable')->equals($isFacturable)
             ->field('mouvements.facture')->equals($isFacture)
             ->getQuery()
             ->execute();
    }

    public function findLast() {
    	return $this->findBy(array(), array('dateCreation' => 'DESC', 'identifiant' => 'DESC'), 100);
    }

    public function findAllSortedByNumeroArchive() {
       return $this->findBy(array(), array('numeroArchive' => 'ASC'));
    }

    public function findAllTaciteSortedByNumeroArchive($dateMin) {
      return $this->createQueryBuilder()
           ->field('typeContrat')->equals(ContratManager::TYPE_CONTRAT_RECONDUCTION_TACITE)
           ->field('dateDebut')->gte($dateMin)
           ->sort('numeroArchive', 'asc')
           ->getQuery()
           ->execute();
    }

    public function countContratByCommercial($compte) {

        return $this->createQueryBuilder()
             ->field('commercial')->equals($compte->getId())
             ->getQuery()->execute()->count();
    }

    public function findAllFrequences() {
    	return ContratManager::$frequences;
    }

    public function findAllErreursEnCours() {
        $dateMin = (new \DateTime())->modify("-1 year -6 month");
        $contratsTacites = $this->findAllTaciteSortedByNumeroArchive($dateMin);
        $contratsErreurs = array();
        foreach ($contratsTacites as $key => $contrat) {
          if(!$contrat->getDateDebut()){
            continue;
          }
          if($contrat->getDateDebut()->format('Ymd') < "20150101"){
            continue;
          }
            foreach ($contrat->getContratPassages() as $etb => $contratPassages) {
              foreach ($contratPassages->getPassages() as $key => $p){
                if($p->getDateRealise()){
                  if(($contrat->getDateDebut()->format('Ym') > $p->getDateRealise()->format('Ym')) || ($p->getDateRealise()->format('Ym') > $contrat->getDateFin()->format('Ym'))){
                    $contratsErreurs[$contrat->getId()] = new \stdClass();
                    $contratsErreurs[$contrat->getId()]->contrat = $contrat;
                    break;
                  }
                }
              }
            }
         }
        return $contratsErreurs;
    }

    public function findAllDecalage() {
        $contratsTacites = array();
        $num_arch = null;
        $dateFin = null;
        $contratsErreurs = array();
        $dateMin = (new \DateTime())->modify("-1 year -6 month");
        foreach ($this->findAllTaciteSortedByNumeroArchive($dateMin) as $contrat) {

            if(is_null($num_arch) || $contrat->getNumeroArchive()!= $num_arch){
              $num_arch = $contrat->getNumeroArchive();
              $lastContrat = $contrat;
              continue;
            }else{
              $flag = false;
              $datesPrevContrat = array_keys($contrat->getArrayDatePrevision());
              $datesPrevLastContrat = array_keys($lastContrat->getArrayDatePrevision());
              for ($i=0; $i < count($datesPrevLastContrat); $i++) {
                $datePrevContrat = \DateTime::createFromFormat('Y-m-d',$datesPrevLastContrat[$i]);
                if(!isset($datesPrevContrat[$i])){
                  $flag = true;
                  $contratsErreurs[$contrat->getId()] = new \stdClass();
                  $contratsErreurs[$contrat->getId()]->contrat = $contrat;
                  $contratsErreurs[$contrat->getId()]->datesPrevContrat = $datesPrevContrat;
                  $contratsErreurs[$contrat->getId()]->datesPrevLastContrat = $datesPrevLastContrat;
                  break;
                }
                $dateContrat = \DateTime::createFromFormat('Y-m-d',$datesPrevContrat[$i]);
                if($datePrevContrat->format("m") != $dateContrat->format("m")){
                  $flag = true;
                  $contratsErreurs[$contrat->getId()] = new \stdClass();
                  $contratsErreurs[$contrat->getId()]->contrat = $contrat;
                  $contratsErreurs[$contrat->getId()]->datesPrevContrat = $datesPrevContrat;
                  $contratsErreurs[$contrat->getId()]->datesPrevLastContrat = $datesPrevLastContrat;
                  break;
                }
              }
              $lastContrat = $contrat;
            }

        }
        return $contratsErreurs;
    }


    public function findByQuery($q)
    {
        $q = str_replace(",", "", $q);
        $q = "\"".str_replace(" ", "\" \"", $q)."\"";
    	$resultSet = array();
    	$itemResultSet = $this->getDocumentManager()->getDocumentDatabase('AppBundle:Contrat')->command([
    			'find' => 'Contrat',
    			'filter' => ['$text' => ['$search' => $q]],
    			'projection' => ['score' => [ '$meta' => "textScore" ]],
    			'sort' => ['score' => [ '$meta' => "textScore" ]],
    			'limit' => 100

    	]);
    	if (isset($itemResultSet['cursor']) && isset($itemResultSet['cursor']['firstBatch'])) {
    		foreach ($itemResultSet['cursor']['firstBatch'] as $itemResult) {
    			$resultSet[] = array("doc" => $this->uow->getOrCreateDocument('\AppBundle\Document\Contrat', $itemResult), "score" => $itemResult['score']);
    		}
    	}
    	return $resultSet;
    }

    public function findByDateEntreDebutFin(\DateTime $date) {
        $q = $this->createQueryBuilder();
          $q->field('dateDebut')->lte($date);
          $q->field('dateFin')->gte($date);
        /**
        * Attention cette requete devrait probablement renvoyer aussi les contrats a date de fin null?
        */
        // $q->addOr($q->expr()->field('dateFin')->gte($date))
        //   ->addOr($q->expr()->field('dateFin')->equals(null));

        $query = $q->getQuery();

        return $query->execute();
    }

    public function findLastContratByNumero($numeroArchive) {
        $q = $this->createQueryBuilder();
        $q->field('numeroArchive')->equals($numeroArchive);
        $q->sort('dateFin', 'desc');
        $q->limit(1);
        $query = $q->getQuery();

        foreach($query->execute() as $contrat) {

            return $contrat;
        }

        return null;
    }

    public function findContratsAReconduire($typeContrat = null, \DateTime $date, $societe = null, $commercial = null) {
          $date = new \DateTime($date->format('Y-m-d')." 23:59:59");
          $q = $this->createQueryBuilder();
          if ($societe) {
			$societeRepo = $this->getDocumentManager()->getRepository('AppBundle:Societe');
			$q->field('societe')->in($societeRepo->getIdsByQuery($societe));
          }
          if ($typeContrat) {
          	$q->field('typeContrat')->equals($typeContrat);
          } else {
          	$q->field('typeContrat')->in(array_keys(ContratManager::$types_contrats_reconductibles));
	  }

	  if($commercial) {
      $commerciauxIds=array();
      foreach($commercial->getValues() as $commercial){
        $commerciauxIds[] = $commercial->getId();
      }
      $q->field('commercial')->in($commerciauxIds);
   }

          $q->field('dateFin')->lte($date);
          $q->addOr($q->expr()->field('reconduit')->equals(false))
            ->addOr($q->expr()->field('reconduit')->exists(false));
	  $q->field('statut')->notEqual(ContratManager::STATUT_EN_ATTENTE_ACCEPTATION);
          $q->sort('dateFin', 'desc');
          $query = $q->getQuery();

        return $query->execute();
    }

    public function findContratWithFactureAFacturer($limit = 50){
      $q = $this->createQueryBuilder();
      $q->field('mouvements.facture')->equals(false);
      $q->limit($limit);
      $query = $q->getQuery();
      return $query->execute();
    }

    public function findAllContratWithFactureAFacturer(){
      $q = $this->createQueryBuilder();
      $q->field('mouvements.facture')->equals(false);
      $q->field('mouvements.facturable')->equals(true);
      $query = $q->getQuery();
      return $query->execute();
    }

    public function findAllContratWithDateReconduction(){
      $dateLimite = new \DateTime();
      $dateLimite->modify('-6 months');

      $q = $this->createQueryBuilder();
      $q->field('dateReconduction')->notEqual(null);
      $q->field('dateReconduction')->gte($dateLimite);
      $query = $q->getQuery();

        return $query->execute();
    }

    public function exportOneMonthByDate(\DateTime $dateDebut,\DateTime $dateFin) {

        $q = $this->createQueryBuilder();

        $q->field('dateCreation')->gte($dateDebut)->lte($dateFin)->sort('dateCreation', 'asc');
        $query = $q->getQuery();

        return $query->execute();
    }

    public function exportAccepteOrResilieByDates(\DateTime $dateDebut,\DateTime $dateFin) {

        $q = $this->createQueryBuilder();

        $q->addOr($q->expr()->field('dateAcceptation')->gte($dateDebut)->lte($dateFin))
          ->addOr($q->expr()->field('dateResiliation')->gte($dateDebut)->lte($dateFin));

        $q->sort('dateAcceptation', 'ASC');
        $query = $q->getQuery();

        return $query->execute();

    }

}
