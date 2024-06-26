<?php

namespace AppBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use AppBundle\Document\RendezVous;

class RendezVousRepository extends BaseRepository
{
    public function findByDateAndParticipant($startDate, $endDate, $participant, $libre = null) {

	$startDateLimit = new \DateTime($startDate);
	$startDateLimit->modify('-3 month');
	$mongoStartDateLimit = new \MongoDate(strtotime($startDateLimit->format('Y-m-d')." midnight")); 
        $mongoStartDate = new \MongoDate(strtotime($startDate." 00:00:00"));
        $mongoEndDate = new \MongoDate(strtotime($endDate." 00:00:00"));

        $query = $this->createQueryBuilder('RendezVous');
        $query->field('dateDebut')->gte($mongoStartDateLimit);

        $query->addOr(
                $query->expr()
                              ->addAnd($query->expr()->field('dateDebut')->gte($mongoStartDate))
                              ->addAnd($query->expr()->field('dateDebut')->lte($mongoEndDate))
                              ->addAnd($query->expr()->field('dateFin')->gte($mongoStartDate))
        );
        $query->field('participants')->equals($participant->getId());

        if($libre){
            $query->field('passage')->equals(null);
            $query->sort('dateDebut', 'desc');
        }else{
            $query->sort('dateDebut', 'asc');
            $query->field('passage')->prime(true);
            $query->field('devis')->prime(true);
        }

        return $query->getQuery()->execute();
    }

    public function findByDateDebutAndParticipant($startDate, $participant, $confirme = true) {
	$startDateLimit = new \DateTime($startDate);
	$startDateLimit->modify('-3 month');
	$mongoStartDateLimit = new \MongoDate(strtotime($startDateLimit->format('Y-m-d')." midnight"));

        $mongoStartDate = new \MongoDate(strtotime($startDate." midnight"));
        $startDateTime = \DateTime::createFromFormat('Y-m-d',$startDate);
        $endDateTime = $startDateTime->modify("+1 day");
        $mongoEndDate = new \MongoDate(strtotime($endDateTime->format('Y-m-d')." midnight")-1);


        $query = $this->createQueryBuilder('RendezVous');
	$query->field('dateDebut')->gte($mongoStartDateLimit);

        $query->addOr(
                $query->expr()->addAnd($query->expr()->field('dateDebut')->gte($mongoStartDate))
                              ->addAnd($query->expr()->field('dateDebut')->lte($mongoEndDate))
                      )
              ->addOr(
                $query->expr()->addAnd($query->expr()->field('dateFin')->gte($mongoStartDate))
                              ->addAnd($query->expr()->field('dateFin')->lte($mongoEndDate)))
              ->addOr(
                $query->expr()->addAnd($query->expr()->field('dateDebut')->lte($mongoStartDate))
                              ->addAnd($query->expr()->field('dateFin')->gte($mongoEndDate)));

        $query->field('participants')->equals($participant->getId())
                ->sort('dateDebut', 'asc');

        $query->field('rendezVousConfirme')->equals($confirme);

        $query->field('passage')->prime(true);
        $query->field('devis')->prime(true);

        return $query->getQuery()->execute();
    }

    public function findByDate($startDate, $endDate, $libre = null) {
        $mongoStartDate = new \MongoDate(strtotime($startDate." 00:00:00"));
        $mongoEndDate = new \MongoDate(strtotime($endDate." 00:00:00"));

        $query = $this->createQueryBuilder('RendezVous');
        $query->addOr(
            $query->expr()->addAnd($query->expr()->field('dateDebut')->gte($mongoStartDate))
                          ->addAnd($query->expr()->field('dateDebut')->lte($mongoEndDate))
                          ->addAnd($query->expr()->field('dateFin')->gte($mongoStartDate))
        );

        if($libre){
            $query->field('passage')->equals(null);
            $query->sort('dateDebut', 'desc');
        }else{
            $query->sort('dateDebut', 'asc');
        }
        return $query->getQuery()->execute();
    }
}
