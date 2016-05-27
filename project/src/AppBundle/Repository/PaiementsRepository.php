<?php

namespace AppBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use AppBundle\Manager\PaiementsManager;

class PaiementsRepository extends DocumentRepository {

 
    
    public function findPaiementsByFacture($facture){   
        return $this->createQueryBuilder()
             ->select('paiement')
             ->field('paiement.facture')
             ->equals($facture->getId())
             ->getQuery()
             ->getIterator();
    }
    
    
    public function getLastPaiements($nbLimit) {
       return $this->createQueryBuilder()
             ->sort('dateCreation','desc')
             ->limit($nbLimit)
             ->getQuery()
             ->execute();
        
        
    }

}
