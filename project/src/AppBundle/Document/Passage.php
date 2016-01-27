<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Passages
 *
 * @author mathurin
 */

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\PassageRepository")
 */
class Passage {

    const PREFIX = "PASSAGE";

    /**
     * @MongoDB\Id(strategy="NONE", type="string")
     */
    protected $id;

    /**
     * @MongoDB\String
     */
    protected $etablissementIdentifiant;

    /**
     * @MongoDB\String
     */
    protected $prestationIdentifiant;

    /**
     * @MongoDB\String
     */
    protected $numeroPassageIdentifiant;

    /**
     * @MongoDB\String
     */
    protected $societeIdentifiant;

    /**
     * @MongoDB\Date
     */
    protected $createAt;

    /**
     * @MongoDB\EmbedOne(targetDocument="PassageEtablissement")
     */
    protected $passageEtablissement;

    /**
     * @MongoDB\Boolean
     */
    protected $planifie;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set id
     *
     * @return id $id
     */
    public function setId($fromImport = false) {
        $this->id = $this->generateId($fromImport);
    }

    public function generateId($fromImport = false) {
        $date = new \DateTime('today');
        if (!$fromImport) {
            $this->setCreateAt($date);
        }
        return self::PREFIX . '-' . $this->etablissementIdentifiant . '-' . $this->createAt->format('Ymd') . '-' . $this->numeroPassageIdentifiant;
    }

    /**
     * Set etablissementIdentifiant
     *
     * @param string $etablissementIdentifiant
     * @return self
     */
    public function setEtablissementIdentifiant($etablissementIdentifiant) {
        $this->etablissementIdentifiant = $etablissementIdentifiant;
        return $this;
    }

    /**
     * Get etablissementIdentifiant
     *
     * @return string $etablissementIdentifiant
     */
    public function getEtablissementIdentifiant() {
        return $this->etablissementIdentifiant;
    }

    /**
     * Set societeIdentifiant
     *
     * @param string $societeIdentifiant
     * @return self
     */
    public function setSocieteIdentifiant($societeIdentifiant) {
        $this->societeIdentifiant = $societeIdentifiant;
        return $this;
    }

    /**
     * Get societeIdentifiant
     *
     * @return string $societeIdentifiant
     */
    public function getSocieteIdentifiant() {
        return $this->societeIdentifiant;
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     * @return self
     */
    public function setTelephone($telephone) {
        $this->telephone = $telephone;
        return $this;
    }

    /**
     * Get telephone
     *
     * @return string $telephone
     */
    public function getTelephone() {
        return $this->telephone;
    }

    /**
     * Set prestationIdentifiant
     *
     * @param string $prestationIdentifiant
     * @return self
     */
    public function setPrestationIdentifiant($prestationIdentifiant) {
        $this->prestationIdentifiant = $prestationIdentifiant;
        return $this;
    }

    /**
     * Get prestationIdentifiant
     *
     * @return string $prestationIdentifiant
     */
    public function getPrestationIdentifiant() {
        return $this->prestationIdentifiant;
    }

    /**
     * Set numeroPassageIdentifiant
     *
     * @param string $numeroPassageIdentifiant
     * @return self
     */
    public function setNumeroPassageIdentifiant($numeroPassageIdentifiant) {
        $this->numeroPassageIdentifiant = $numeroPassageIdentifiant;
        return $this;
    }

    /**
     * Get numeroPassageIdentifiant
     *
     * @return string $numeroPassageIdentifiant
     */
    public function getNumeroPassageIdentifiant() {
        return $this->numeroPassageIdentifiant;
    }

    public function updateEtablissementInfos(Etablissement $etb) {
        $this->passageEtablissement = new PassageEtablissement();
        $this->passageEtablissement->setRaisonSociale($etb->getRaisonSociale());
        $this->passageEtablissement->setNomContact($etb->getNomContact());
        $this->passageEtablissement->setAdresse($etb->getAdresse());
        $this->passageEtablissement->setCodePostal($etb->getCodePostal());
        $this->passageEtablissement->setCommune($etb->getCommune());
        $this->passageEtablissement->setTelephonePortable($etb->getTelephonePortable());
        $this->passageEtablissement->setTypeEtablissement($etb->getTypeEtablissement());
    }

    /**
     * Set etablissement
     *
     * @param AppBundle\Document\PassageEtablissement $etablissement
     * @return self
     */
    public function setEtablissement(\AppBundle\Document\PassageEtablissement $etablissement) {
        $this->etablissement = $etablissement;
        return $this;
    }

    /**
     * Get etablissement
     *
     * @return AppBundle\Document\PassageEtablissement $etablissement
     */
    public function getEtablissement() {
        return $this->etablissement;
    }

    /**
     * Set passageEtablissement
     *
     * @param AppBundle\Document\PassageEtablissement $passageEtablissement
     * @return self
     */
    public function setPassageEtablissement(\AppBundle\Document\PassageEtablissement $passageEtablissement) {
        $this->passageEtablissement = $passageEtablissement;
        return $this;
    }

    /**
     * Get passageEtablissement
     *
     * @return AppBundle\Document\PassageEtablissement $passageEtablissement
     */
    public function getPassageEtablissement() {
        return $this->passageEtablissement;
    }

    /**
     * Set planifie
     *
     * @param boolean $planifie
     * @return self
     */
    public function setPlanifie($planifie) {
        $this->planifie = $planifie;
        return $this;
    }

    /**
     * Get planifie
     *
     * @return boolean $planifie
     */
    public function getPlanifie() {
        return $this->planifie;
    }

    /**
     * Set createAt
     *
     * @param date $createAt
     * @return self
     */
    public function setCreateAt($createAt) {
        $this->createAt = $createAt;
        return $this;
    }

    /**
     * Get createAt
     *
     * @return date $createAt
     */
    public function getCreateAt() {
        return $this->createAt;
    }

}
