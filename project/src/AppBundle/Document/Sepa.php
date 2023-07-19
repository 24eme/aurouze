<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Sepa
 *
 */

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
*/
class Sepa {

    /**
     * @MongoDB\Field(type="string")
     */
    protected $nomBancaire;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $iban;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $bic;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $rum;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $date;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $actif;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $first;

    public function __construct() {
        $this->setActif(false);
        $this->setFirst(true);
    }

    /**
     * Set iban
     *
     * @param string $iban
     * @return self
     */
    public function setIban($iban)
    {
        $this->setFirst(true);
        $this->iban = $iban;
        return $this;
    }

    /**
     * Get iban
     *
     * @return string $iban
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * Set bic
     *
     * @param string $bic
     * @return self
     */
    public function setBic($bic)
    {
        $this->setFirst(true);
        $this->bic = $bic;
        return $this;
    }

    /**
     * Get iban
     *
     * @return string $iban
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * Set rum
     *
     * @param string $rum
     * @return self
     */
    public function setRum($rum)
    {
        $this->setFirst(true);
        $this->rum = $rum;
        return $this;
    }

    /**
     * Get iban
     *
     * @return string $iban
     */
    public function getRum()
    {
        return $this->rum;
    }

    /**
     * Set date
     *
     * @param date $date
     * @return self
     */
    public function setDate($date) {
        $this->setFirst(true);
        $this->date = $date;
        return $this;
    }

    /**
     * Get date
     *
     * @return date $date
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Set actif
     *
     * @param boolean $actif
     * @return self
     */
    public function setActif($actif)
    {
        $this->actif = $actif;
        return $this;
    }

    /**
     * Get actif
     *
     * @return boolean $actif
     */
    public function getActif()
    {
        return $this->actif;
    }



    /**
     * Set first
     *
     * @param boolean $first
     * @return $this
     */
    public function setFirst($first)
    {
        $this->first = $first;
        return $this;
    }

    /**
     * Get first
     *
     * @return boolean $first
     */
    public function getFirst()
    {
        return $this->first;
    }

    /**
     * Is first
     *
     * @return boolean $first
     */
    public function isFirst()
    {
        return $this->first;
    }

    /**
     * Set nomBancaire
     *
     * @param string $nomBancaire
     * @return $this
     */
    public function setNomBancaire($nomBancaire)
    {
        $this->setFirst(true);
        $this->nomBancaire = $nomBancaire;
        return $this;
    }

    /**
     * Get nomBancaire
     *
     * @return string $nomBancaire
     */
    public function getNomBancaire()
    {
        return $this->nomBancaire;
    }


    public function isFullAndActif(){
        if(!$this->getNomBancaire() || !$this->getIban() || !$this->getBic() || !$this->getRum() || !$this->getDate() || !$this->getActif()){
            return false;
        }
        return true;
    }
}
