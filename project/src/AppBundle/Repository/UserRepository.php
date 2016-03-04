<?php

namespace AppBundle\Repository;
use Doctrine\ODM\MongoDB\DocumentRepository;
use AppBundle\Document\User;

class EUserRepository extends DocumentRepository {

    public function findAllByType($type) {
        return $this->findBy(array('type_user' => $type));
    }
    
    public function findByIdentifiant($identifiant) {
    	return $this->find(User::PREFIX.'-'.$identifiant);
    }

}
