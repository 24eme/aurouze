<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\Societe;
use AppBundle\Document\Passage;

/**
 * @MongoDB\EmbeddedDocument
 * @MongoDB\Index()
*/
class Mouvement {

    /**
     * @MongoDB\Field(type="float")
     */
    protected $prixUnitaire;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $quantite;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $tauxTaxe;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $facturable;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $facture;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $libelle;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Societe", simple=true)
     */
    protected $societe;

    /**
    * @MongoDB\Field(type="string")
    */
    protected $identifiant;

    /**
    * @MongoDB\ReferenceOne()
    */
    protected $document;

    /**
    * @MongoDB\ReferenceOne()
    */
    protected $origineDocumentGeneration;

    public function facturer() {
        if(!$this->getFacturable()) {
            return false;
        }

        $this->setFacture(true);

        return true;
    }

    public function isFacturable() {

        return $this->getFacturable() && !$this->getFacture();
    }

    public function isFacture() {

        return !$this->getFacturable() || $this->getFacture();
    }

    /**
     * Set facturable
     *
     * @param boolean $facturable
     * @return self
     */
    public function setFacturable($facturable)
    {
        $this->facturable = $facturable;
        return $this;
    }

    /**
     * Get facturable
     *
     * @return boolean $facturable
     */
    public function getFacturable()
    {
        return $this->facturable;
    }

    /**
     * Set facture
     *
     * @param boolean $facture
     * @return self
     */
    public function setFacture($facture)
    {
        $this->facture = $facture;
        return $this;
    }

    /**
     * Get facture
     *
     * @return boolean $facture
     */
    public function getFacture()
    {
        return $this->facture;
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     * @return self
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;
        return $this;
    }

    /**
     * Get libelle
     *
     * @return string $libelle
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

    /**
     * Set quantite
     *
     * @param float $quantite
     * @return self
     */
    public function setQuantite($quantite)
    {
        $this->quantite = $quantite;
        return $this;
    }

    /**
     * Get quantite
     *
     * @return float $quantite
     */
    public function getQuantite()
    {
        return $this->quantite;
    }

    /**
     * Set societe
     *
     * @param AppBundle\Document\Societe $societe
     * @return self
     */
    public function setSociete(\AppBundle\Document\Societe $societe) {
        $this->societe = $societe;
        return $this;
    }

    /**
     * Get societe
     *
     * @return AppBundle\Document\Societe $societe
     */
    public function getSociete() {
        return $this->societe;
    }

    /**
     * Set identifiant
     *
     * @param string $identifiant
     * @return self
     */
    public function setIdentifiant($identifiant)
    {
        $this->identifiant = $identifiant;
        return $this;
    }

    /**
     * Get identifiant
     *
     * @return string $identifiant
     */
    public function getIdentifiant()
    {
        return $this->identifiant;
    }


    /**
     * Set document
     *
     * @param $document
     * @return self
     */
    public function setDocument($document)
    {
        $this->document = $document;
        return $this;
    }

    /**
     * Get document
     *
     * @return $document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Set prixUnitaire
     *
     * @param float $prixUnitaire
     * @return self
     */
    public function setPrixUnitaire($prixUnitaire)
    {
        $this->prixUnitaire = $prixUnitaire;
        return $this;
    }

    /**
     * Get prixUnitaire
     *
     * @return float $prixUnitaire
     */
    public function getPrixUnitaire()
    {
        return $this->prixUnitaire;
    }

    /**
     * Set tauxTaxe
     *
     * @param float $tauxTaxe
     * @return self
     */
    public function setTauxTaxe($tauxTaxe)
    {
        $this->tauxTaxe = $tauxTaxe;
        return $this;
    }

    /**
     * Get tauxTaxe
     *
     * @return float $tauxTaxe
     */
    public function getTauxTaxe()
    {
        return $this->tauxTaxe;
    }


    /**
     * Set origineDocumentGeneration
     *
     * @param $origineDocumentGeneration
     * @return self
     */
    public function setOrigineDocumentGeneration($origineDocumentGeneration)
    {
        $this->origineDocumentGeneration = $origineDocumentGeneration;
        return $this;
    }

    /**
     * Get origineDocumentGeneration
     *
     * @return $origineDocumentGeneration
     */
    public function getOrigineDocumentGeneration()
    {
        return $this->origineDocumentGeneration;
    }

    public function isPassageHorsContrat() {

        return $this->getOrigineDocumentGeneration() &&  $this->getOrigineDocumentGeneration() instanceof Passage && $this->getOrigineDocumentGeneration()->isHorsContrat();
    }
}
