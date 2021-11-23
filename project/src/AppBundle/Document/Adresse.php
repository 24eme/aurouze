<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Adresse
 *
 * @author mathurin
 */

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\Coordonnees;

/**
 * @MongoDB\EmbeddedDocument
 * @MongoDB\Index(keys={"coordonnees"="2d"})
*/
class Adresse {

    /**
     * @MongoDB\Field(type="string")
     */
    protected $adresse;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $codePostal;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $commune;

     /**
     * @MongoDB\EmbedOne(targetDocument="Coordonnees")
     */
    protected $coordonnees;

    /**
     * @MongoDB\Distance
     */
    protected $distance;

    public function isSameThan(Adresse $adresse) {
        if
        (
            ($this->getAdresse() == $adresse->getAdresse() || !$this->getAdresse()) &&
            ($this->getCommune() == $adresse->getCommune() || !$this->getCommune()) &&
            ($this->getCodePostal() == $adresse->getCodePostal() || !$this->getCodePostal())
        )
        {

            return true;
        }

        return false;
    }

    public function isEmpty() {

        return (!$this->getAdresse() && !$this->getCommune() && !$this->getCodePostal());
    }

    public function copyFrom(Adresse $adresse) {
        $this->setAdresse($adresse->getAdresse());
        $this->setCommune($adresse->getCommune());
        $this->setCodePostal($adresse->getCodePostal());
    }

    public function __construct() {
        $this->coordonnees = new Coordonnees();
    }

    /**
     * Set adresse
     *
     * @param string $adresse
     * @return self
     */
    public function setAdresse($adresse)
    {
        $this->adresse = $adresse;
        return $this;
    }

    /**
     * Get adresse
     *
     * @return string $adresse
     */
    public function getAdresse()
    {
        return $this->adresse;
    }

    public function getAdresseFormatee()
    {
        return str_replace(", ", "\n", $this->getAdresse());
    }

    /**
     * Set codePostal
     *
     * @param string $codePostal
     * @return self
     */
    public function setCodePostal($codePostal)
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    /**
     * Get codePostal
     *
     * @return string $codePostal
     */
    public function getCodePostal()
    {
        return $this->codePostal;
    }

    /**
     * Set commune
     *
     * @param string $commune
     * @return self
     */
    public function setCommune($commune)
    {
        $this->commune = $commune;
        return $this;
    }

    /**
     * Get commune
     *
     * @return string $commune
     */
    public function getCommune()
    {
        return $this->commune;
    }

    /**
     * Set coordonnees
     *
     * @param Coordonnees $coordonnees
     * @return self
     */
    public function setCoordonnees($coordonnees)
    {
        $this->coordonnees = $coordonnees;
        return $this;
    }

    /**
     * Get coordonnees
     *
     * @return Coordonnees $coordonnees
     */
    public function getCoordonnees()
    {
        return $this->coordonnees;
    }

    /**
     * Set distance
     *
     * @param string $distance
     * @return self
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;
        return $this;
    }

    /**
     * Get distance
     *
     * @return string $distance
     */
    public function getDistance()
    {
        return $this->distance;
    }

    public function getIntitule() {

        return sprintf("%s %s %s", $this->getAdresse(), $this->getCodePostal(), $this->getCommune());
    }

    public function getLibelleComplet() {

        return sprintf("%s, %s %s", $this->getAdresse(), $this->getCodePostal(), $this->getCommune());
    }

    public function getLat(){
      return $this->getCoordonnees()->getLat();
    }

    public function setLat($lat){
      $this->getCoordonnees()->setLat($lat);
      return $this;
    }

    public function getLon(){
      return $this->getCoordonnees()->getLon();
    }

    public function setLon($lon){
      $this->getCoordonnees()->setLon($lon);
      return $this;
    }
}
