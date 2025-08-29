<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Manager\PaiementsManager;

/**
 * @MongoDB\EmbeddedDocument
 */
class Paiement {

    /**
     * @MongoDB\ReferenceOne(targetDocument="Facture", inversedBy="paiements", simple=true)
     */
    protected $facture;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Societe", inversedBy="paiements", simple=true)
     */
    protected $societe;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $moyenPaiement;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $typeReglement;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $libelle;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $montant;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $datePaiement;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $versementComptable;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiantReprise;


    protected $factureMontantTTC;

    protected $montantTemporaire;

    protected $factureTemporaire = array();

    public function __construct() {
        $this->versementComptable = false;
    }

    /**
     * Set facture
     *
     * @param AppBundle\Document\Facture $facture
     * @return self
     */
    public function setFacture(\AppBundle\Document\Facture $facture) {
        $this->facture = $facture;
        $this->setSociete($facture->getSociete());
        return $this;
    }

    /**
     * Get facture
     *
     * @return AppBundle\Document\Facture $facture
     */
    public function getFacture() {
        return $this->facture;
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
     * Set moyenPaiement
     *
     * @param string $moyenPaiement
     * @return self
     */
    public function setMoyenPaiement($moyenPaiement) {
        $this->moyenPaiement = $moyenPaiement;
        return $this;
    }

    /**
     * Get moyenPaiement
     *
     * @return string $moyenPaiement
     */
    public function getMoyenPaiement() {
        return $this->moyenPaiement;
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     * @return self
     */
    public function setLibelle($libelle) {
        $this->libelle = $libelle;
        return $this;
    }

    /**
     * Get libelle
     *
     * @return string $libelle
     */
    public function getLibelle() {
        return $this->libelle;
    }

    /**
     * Set montant
     *
     * @param float $montant
     * @return self
     */
    public function setMontant($montant) {
        $this->montant = $montant;
        return $this;
    }

    /**
     * Get montant
     *
     * @return float $montant
     */
    public function getMontant() {
        return $this->montant;
    }

    /**
     * Set datePaiement
     *
     * @param date $datePaiement
     * @return self
     */
    public function setDatePaiement($datePaiement) {
        $this->datePaiement = $datePaiement;
        return $this;
    }

    /**
     * Get datePaiement
     *
     * @return date $datePaiement
     */
    public function getDatePaiement() {
        return $this->datePaiement;
    }

    /**
     * Set versementComptable
     *
     * @param boolean $versementComptable
     * @return self
     */
    public function setVersementComptable($versementComptable) {
        $this->versementComptable = $versementComptable;
        return $this;
    }

    /**
     * Get versementComptable
     *
     * @return boolean $versementComptable
     */
    public function getVersementComptable() {
        return $this->versementComptable;
    }

    /**
     * Set typeReglement
     *
     * @param string $typeReglement
     * @return self
     */
    public function setTypeReglement($typeReglement) {
        $this->typeReglement = $typeReglement;
        return $this;
    }

    /**
     * Get typeReglement
     *
     * @return string $typeReglement
     */
    public function getTypeReglement() {
        return $this->typeReglement;
    }


    /**
     * Set identifiantReprise
     *
     * @param string $identifiantReprise
     * @return self
     */
    public function setIdentifiantReprise($identifiantReprise)
    {
        $this->identifiantReprise = $identifiantReprise;
        return $this;
    }

    /**
     * Get identifiantReprise
     *
     * @return string $identifiantReprise
     */
    public function getIdentifiantReprise()
    {
        return $this->identifiantReprise;
    }


    public function setMontantTemporaire($montantTemporaire) {
        $this->montantTemporaire = $montantTemporaire;
        return $this;
    }

    public function getMontantTemporaire() {
       return  $this->montantTemporaire;
    }

    public function addMontantTemporaire($montantTemporaire) {
      $this->setMontantTemporaire($this->getMontantTemporaire() + $montantTemporaire);
      return $this;
    }

    public function getFactureTemporaire() {
       return  $this->factureTemporaire;
    }

    public function addFactureTemporaire($facture) {
           return  $this->factureTemporaire = array_merge($this->factureTemporaire,array($facture->getId() => $facture));
    }


    public function getMoyenPaiementLibelle() {
        if(!$this->getMoyenPaiement()){
            return $this->getMoyenPaiement();
        }
        return PaiementsManager::$moyens_paiement_libelles[$this->getMoyenPaiement()];
    }

    public function getTypeReglementLibelle() {
        if(!$this->getTypeReglement()){
            return $this->getTypeReglement();
        }
        return PaiementsManager::$types_reglements_libelles[$this->getTypeReglement()];
    }

    public function isCheque() {
        return $this->getMoyenPaiement() == PaiementsManager::MOYEN_PAIEMENT_CHEQUE;
    }

    public function setFactureMontantTTC($factureMontantTTC) {
        $this->factureMontantTTC = $factureMontantTTC;
        return $this;
    }
    public function getFactureMontantTTC() {
        return $this->factureMontantTTC ;
    }

    public function getMontantTaxe($tauxTaxe, $ligneFacture) {
            return round($ligneFacture->getMontantHT() * $tauxTaxe, 2);
    }

    public function getMontantByMoyenPaiement($moyen_paiement) {
        $montantMoyenPaiement = 0;
            if($moyen_paiement == $this->getMoyenPaiement()) {
                $montantMoyenPaiement = $this->getMontant();
        }
        return $montantMoyenPaiement;
    }

}
