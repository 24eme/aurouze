<?php

namespace AppBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Model\DocumentPlanifiableInterface;
use AppBundle\Document\RendezVous;
use AppBundle\Document\Devis as Devis;
use AppBundle\Document\Passage as Passage;

class RendezVousManager {
    protected $dm;

    public function __construct(DocumentManager $dm) {
        $this->dm = $dm;
    }

    public function createFromPlanifiable(DocumentPlanifiableInterface $planifiable) {
        $rdv = $planifiable->getRendezVous();

        if($rdv) {

            throw new \Exception('Le rendez vous est déjà créé');
        }

        $rdv = new RendezVous();
        switch (get_class($planifiable)) {
            case Devis::class:
                $rdv->setDevis($planifiable);
                break;
            case Passage::class:
                $rdv->setPassage($planifiable);
                break;
        }

        $rdv->setTitre(sprintf("%s (%s %s)",
                $planifiable->getEtablissementInfos()->getNom(),
                $planifiable->getEtablissementInfos()->getAdresse()->getCodePostal(), $planifiable->getEtablissementInfos()->getAdresse()->getCommune()
        ));


        $rdv->setLieu(sprintf("%s %s %s",
                $planifiable->getEtablissementInfos()->getAdresse()->getAdresse(),
                $planifiable->getEtablissementInfos()->getAdresse()->getCodePostal(), $planifiable->getEtablissementInfos()->getAdresse()->getCommune()
        ));

        $rdv->removeAllParticipants();
        foreach($planifiable->getTechniciens() as $technicien) {
            $rdv->addParticipant($technicien);
        }

        $planifiable->setRendezVous($rdv);

        /* if($passage->getDateDebut() && $passage->getDateFin()) { */
        /*     $rdv->setDateDebut($passage->getDateDebut()); */
        /*     $rdv->setDateFin($passage->getDateFin()); */
        /* } */

        $rdv->setDescription($planifiable->getEtablissement()->getCommentaire());

        return $rdv;
    }

    public function getRepository() {

        return $this->dm->getRepository('AppBundle:RendezVous');
    }

}
