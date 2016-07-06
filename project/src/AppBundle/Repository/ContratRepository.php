<?php

namespace AppBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use MongoDate as MongoDate;
use AppBundle\Manager\ContratManager;

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

    public function findAllSortedByNumeroArchive() {
       return $this->findBy(array(), array('numeroArchive' => 'ASC'));
    }

    public function countContratByCommercial($compte) {

        return $this->createQueryBuilder()
             ->field('commercial')->equals($compte->getId())
             ->getQuery()->execute()->count();
    }

    public function findAllFrequences() {
    	return ContratManager::$frequences;
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

}
