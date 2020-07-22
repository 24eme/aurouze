<?php

namespace AppBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use AppBundle\Document\Devis;
use AppBundle\Document\Societe;
use AppBundle\Document\Adresse;

class DevisManager {

    protected $dm;
    protected $parameters;


    function __construct(DocumentManager $dm, $parameters) {
        $this->dm = $dm;
        $this->parameters = $parameters;
    }

    public function getParameters() {
        return $this->parameters;
    }

    public function getRepository() {

        return $this->dm->getRepository('AppBundle:Devis');
    }

    public function findBySociete(Societe $societe) {
        return $this->getRepository()->findBySociete($societe);
    }


    public function createVierge(Societe $societe) {
        $devis = new Devis();
        $devis->setSociete($societe);
        $devis->setDateEmission(new \DateTime());
        $parameters = $this->getParameters();
        $devis->getEmetteur()->setNom($parameters['emetteur']['nom']);
        $devis->getEmetteur()->setAdresse($parameters['emetteur']['adresse']);
        $devis->getEmetteur()->setCodePostal($parameters['emetteur']['code_postal']);
        $devis->getEmetteur()->setCommune($parameters['emetteur']['commune']);
        $devis->getEmetteur()->setTelephone($parameters['emetteur']['telephone']);
        $devis->getEmetteur()->setFax($parameters['emetteur']['fax']);
        $devis->getEmetteur()->setEmail($parameters['emetteur']['email']);

        return $devis;
    }

}
