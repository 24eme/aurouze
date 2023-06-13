<?php

namespace AppBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use AppBundle\Document\RendezVous;

class RendezVousRepository extends DocumentRepository
{
    public function findByDateAndParticipant($startDate, $endDate, $participant) {
        $mongoStartDate = new \MongoDate(strtotime($startDate." 00:00:00"));
        $mongoEndDate = new \MongoDate(strtotime($endDate." 00:00:00"));

        $query = $this->createQueryBuilder('RendezVous');
        $query->addOr($query->expr()->field('dateDebut')->gte($mongoStartDate)->field('dateDebut')->lte($mongoEndDate));
        $query->addOr($query->expr()->field('dateFin')->gte($mongoStartDate));
        $query->field('participants')->equals($participant->getId())
                ->sort('dateDebut', 'asc');

        return $query->getQuery()->execute();
    }

    public function findByDateDebutAndParticipant($startDate, $participant) {
        $mongoStartDate = new \MongoDate(strtotime($startDate." midnight"));
        $startDateTime = \DateTime::createFromFormat('Y-m-d',$startDate);
        $endDateTime = $startDateTime->modify("+1 day");
        $mongoEndDate = new \MongoDate(strtotime($endDateTime->format('Y-m-d')." midnight")-1);

        $query = $this->createQueryBuilder('RendezVous');
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

        return $query->getQuery()->execute();
    }

    public function findByDate($startDate, $endDate) {
        $mongoStartDate = new \MongoDate(strtotime($startDate." 00:00:00"));
        $mongoEndDate = new \MongoDate(strtotime($endDate." 00:00:00"));

        $query = $this->createQueryBuilder('RendezVous');
        $query->addOr($query->expr()->field('dateDebut')->gte($mongoStartDate)->field('dateDebut')->lte($mongoEndDate));
        $query->addOr($query->expr()->field('dateFin')->gte($mongoStartDate));
        $query->sort('dateDebut', 'asc');

        return $query->getQuery()->execute();
    }
}
