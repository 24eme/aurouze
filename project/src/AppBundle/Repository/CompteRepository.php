<?php

namespace AppBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use AppBundle\Document\Compte;
use AppBundle\Manager\CompteManager;

class CompteRepository extends BaseRepository {

    public function findAllUtilisateursTechnicien() {
        return $this->findAllUtilisateursHasTag(CompteManager::TYPE_TECHNICIEN);
    }

    public function findAllUtilisateursCommercial() {
        return $this->findAllUtilisateursHasTag(CompteManager::TYPE_COMMERCIAL);
    }

    public function findAllUtilisateursCalendrier($sort = []) {
        return $this->findAllUtilisateursHasTag(CompteManager::TAG_CALENDRIER, $sort);
    }

    public function findAllUtilisateursHasTag($tag, $sort = []) {

        return $this->findBy(
            array('tags.identifiant' => $tag),
            $sort
        );
    }

    public function findByQuery($q, $inactif = false)
    {
        $q = str_replace(",", "", $q);
        $q = "\"".str_replace(" ", "\" \"", $q)."\"";
    	$resultSet = array();
    	$itemResultSet = $this->command([
    			'find' => 'Compte',
    			'filter' => ['$text' => ['$search' => $q]],
    			'projection' => ['score' => [ '$meta' => "textScore" ]],
    			'sort' => ['score' => [ '$meta' => "textScore" ]],
    			'limit' => 50

    	]);
    	
    		foreach ($itemResultSet as $itemResult) {
    			if (!$inactif && !$itemResult['actif']) {
    				continue;
    			}
    			$resultSet[] = array("doc" => $this->uow->getOrCreateDocument('\AppBundle\Document\Compte', $itemResult), "score" => $itemResult['score'], "instance" => "Compte");
    		}
    	return $resultSet;
    }

    public function findAllUtilisateursTechnicienActif() {
        $query = $this->createQueryBuilder('Compte');
        $query->field('tags.identifiant')->equals(CompteManager::TYPE_TECHNICIEN);
        $query->field('actif')->equals(true);
        return $query->getQuery()->execute();
    }
}
