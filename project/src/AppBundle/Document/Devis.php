<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\RendezVous;
use AppBundle\Document\Compte;
use AppBundle\Document\EtablissementInfos;
use AppBundle\Model\DocumentSocieteInterface;
use AppBundle\Model\DocumentPlanifiableInterface;
use AppBundle\Model\DocumentPlanifiableTrait;
use AppBundle\Model\FacturableInterface;
use AppBundle\Manager\DevisManager;
use AppBundle\Manager\ContratManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\DevisRepository") @HasLifecycleCallbacks
 */
class Devis implements DocumentSocieteInterface, DocumentPlanifiableInterface, FacturableInterface
{
    use DocumentPlanifiableTrait;

    const DOCUMENT_TYPE = 'Devis';

    /**
     * @MongoDB\Id(strategy="CUSTOM", type="string", options={"class"="AppBundle\Document\Id\DevisGenerator"})
     */
    protected $id;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Societe", inversedBy="devis", simple=true)
     */
    protected $societe;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Compte", inversedBy="devis")
     */
    protected $commercial;

    /**
     * @MongoDB\EmbedOne(targetDocument="Soussigne")
     */
    protected $emetteur;

    /**
     * @MongoDB\EmbedOne(targetDocument="Soussigne")
     */
    protected $destinataire;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateEmission;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateSignature;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $montantHT;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $montantTTC;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $montantTaxe;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $numeroDevis;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $commentaireTechnicien;

    /**
     * @MongoDB\EmbedMany(targetDocument="LigneFacturable")
     */
    protected $lignes;

    public function __construct() {
        $this->etablissementInfos = new EtablissementInfos();
        $this->techniciens = new ArrayCollection();
        $this->emetteur = new Soussigne();
        $this->destinataire = new Soussigne();
        $this->dateEmission = new \DateTime();
        $this->datePrevision = new \DateTime();
        if(!$this->getLignes() || !count($this->getLignes())){
          $l = new LigneFacturable();
          $this->addLigne($l);
        }
    }

    /** @MongoDB\PreUpdate */
    public function preUpdate() {
        $this->updateStatut();
    }

    /**
     * Set societe
     *
     * @param AppBundle\Document\Societe $societe
     * @return self
     */
    public function setSociete(\AppBundle\Document\Societe $societe, $store = true) {
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
     * Get id
     *
     * @return string $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set identifiant
     *
     * @param string $identifiant
     * @return self
     */
    public function setIdentifiant($identifiant) {
        $this->identifiant = $identifiant;
        return $this;
    }

    /**
     * Get identifiant
     *
     * @return string $identifiant
     */
    public function getIdentifiant() {
        return $this->identifiant;
    }

    /**
     * Set commercial
     *
     * @param AppBundle\Document\Compte $commercial
     * @return $this
     */
    public function setCommercial(\AppBundle\Document\Compte $commercial)
    {
        $this->commercial = $commercial;
        return $this;
    }

    /**
     * Get commercial
     *
     * @return AppBundle\Document\Compte $commercial
     */
    public function getCommercial()
    {
        return $this->commercial;
    }

    /**
     * Set emetteur
     *
     * @param AppBundle\Document\Soussigne $emetteur
     * @return self
     */
    public function setEmetteur(\AppBundle\Document\Soussigne $emetteur) {
        $this->emetteur = $emetteur;
        return $this;
    }

    /**
     * Get emetteur
     *
     * @return AppBundle\Document\Soussigne $emetteur
     */
    public function getEmetteur() {
        return $this->emetteur;
    }

    /**
     * Set destinataire
     *
     * @param AppBundle\Document\Soussigne $destinataire
     * @return $this
     */
    public function setDestinataire(\AppBundle\Document\Soussigne $destinataire)
    {
        $this->destinataire = $destinataire;
        return $this;
    }

    /**
     * Get destinataire
     *
     * @return AppBundle\Document\Soussigne $destinataire
     */
    public function getDestinataire()
    {
        return $this->destinataire;
    }

    /**
     * Set dateEmission
     *
     * @param date $dateEmission
     * @return $this
     */
    public function setDateEmission($dateEmission)
    {
        $this->dateEmission = $dateEmission;
        return $this;
    }

    /**
     * Get dateEmission
     *
     * @return date $dateEmission
     */
    public function getDateEmission()
    {
        return $this->dateEmission;
    }


    /**
     * Set dateSignature
     *
     * @param date $dateSignature
     * @return $this
     */
    public function setDateSignature($dateSignature)
    {
        $this->dateSignature = $dateSignature;
        return $this;
    }

    /**
     * Get dateSignature
     *
     * @return date $dateSignature
     */
    public function getDateSignature()
    {
        return $this->dateSignature;
    }

    /**
     * Set montantHT
     *
     * @param float $montantHT
     * @return $this
     */
    public function setMontantHT($montantHT)
    {
        $this->montantHT = $montantHT;
        return $this;
    }

    /**
     * Get montantHT
     *
     * @return float $montantHT
     */
    public function getMontantHT()
    {
        return $this->montantHT;
    }

    /**
     * Set montantTTC
     *
     * @param float $montantTTC
     * @return $this
     */
    public function setMontantTTC($montantTTC)
    {
        $this->montantTTC = $montantTTC;
        return $this;
    }

    /**
     * Get montantTTC
     *
     * @return float $montantTTC
     */
    public function getMontantTTC()
    {
        return $this->montantTTC;
    }

    /**
     * Set montantTaxe
     *
     * @param float $montantTaxe
     * @return $this
     */
    public function setMontantTaxe($montantTaxe)
    {
        $this->montantTaxe = $montantTaxe;
        return $this;
    }

    /**
     * Get montantTaxe
     *
     * @return float $montantTaxe
     */
    public function getMontantTaxe()
    {
        return $this->montantTaxe;
    }


    /**
     * Set numeroDevis
     *
     * @param string $numeroDevis
     * @return $this
     */
    public function setNumeroDevis($numeroDevis)
    {
        $this->numeroDevis = $numeroDevis;
        return $this;
    }

    /**
     * Get numeroDevis
     *
     * @return string $numeroDevis
     */
    public function getNumeroDevis()
    {
        return $this->numeroDevis;
    }


    /**
     * Add ligne
     *
     * @param AppBundle\Document\LigneFacturable $ligne
     */
    public function addLigne(\AppBundle\Document\LigneFacturable $ligne)
    {
        $this->lignes[] = $ligne;
    }

    /**
     * Remove ligne
     *
     * @param AppBundle\Document\LigneFacturable $ligne
     */
    public function removeLigne(\AppBundle\Document\LigneFacturable $ligne)
    {
        $this->lignes->removeElement($ligne);
    }

    /**
     * Get lignes
     *
     * @return \Doctrine\Common\Collections\Collection $lignes
     */
    public function getLignes()
    {
        return $this->lignes;
    }

    public function getNumero()
    {
        return $this->getNumeroDevis();
    }

    public function updateCalcul() {
        $montant = 0;
        $montantTaxe = 0;
        if($this->getLignes()){
          foreach ($this->getLignes() as $ligne) {
              $ligne->update();
              $montant = $montant + $ligne->getMontantHT();
              $montantTaxe = $montantTaxe + $ligne->getMontantTaxe();
          }
        }
        $this->setMontantHT(round($montant, 2));
        $this->setMontantTaxe(round($montantTaxe, 2));
        $this->setMontantTTC(round($montant + $montantTaxe, 2));
    }

    public function update() {
        $this->updateCalcul();
        $this->storeDestinataire();

    }

    public function storeDestinataire() {
        $societe = $this->getSociete();
        $destinataire = $this->getDestinataire();

        $nom = $societe->getRaisonSociale();

        $destinataire->setRaisonSociale($societe->getRaisonSociale());
        $destinataire->setNom($nom);
        $destinataire->setAdresse($societe->getAdresse()->getAdresseFormatee());
        $destinataire->setCodePostal($societe->getAdresse()->getCodePostal());
        $destinataire->setCommune($societe->getAdresse()->getCommune());
        $destinataire->setCodeComptable($societe->getCodeComptable());
    }


    public function getDureePrevisionnelle(){
      return '01:00';
    }

    public function setDureePrevisionnelle($dureePrevisionnelle)
    {
    }

    public function isTransmis(){
      return boolval($this->signatureBase64) || boolval($this->emailTransmission);
    }

    public function getEtablissementInfos() {
        return $this->etablissementInfos;
    }



    public function getTechniciensWithout($technicien) {
        $techniciens = array();

        foreach ($this->getTechniciens() as $t) {
            if($t->getId() == $technicien->getId()) {
                continue;
            }
            $techniciens[$t->getId()] = $t->getIdentite();
        }

        return $techniciens;
    }

    /**
     * {@inheritDoc}
     */
    public function plannifie(){}

    /**
     * {@inheritDoc}
     */
    public function termine(){}

    /**
     * {@inheritDoc}
     */
    public function annule(){}

    public function isValideTechnicien()
    {
        return $this->getSignatureBase64() || $this->getNomTransmission() || $this->getEmailTransmission();
    }

    public function getDocumentType() {
        return self::DOCUMENT_TYPE;
    }

    public function getTypePlanifiable() {
        return self::DOCUMENT_TYPE;
    }

    /**
     * Set saisieTechnicien
     *
     * @param bool $saisieTechnicien
     * @return $this
     */
    public function setSaisieTechnicien($saisieTechnicien)
    {
        $this->saisieTechnicien = $saisieTechnicien;
        return $this;
    }

    /**
     * Get saisieTechnicien
     *
     * @return bool $saisieTechnicien
     */
    public function getSaisieTechnicien()
    {
        return $this->saisieTechnicien;
    }

    /**
     * Get commentaireTechnicien
     * @return string $commentaireTechnicien
     */
    public function getCommentaireTechnicien() {
        return $this->commentaireTechnicien;
    }

    /**
     * Set commentaireTechnicien
     *
     * @param string $commentaire
     */
    public function setCommentaireTechnicien($commentaire) {
        $this->commentaireTechnicien = $commentaire;
    }

    public function getDatePrecedente() {
        return null;
    }

    public function getDureePrecedente() {
        return null;
    }

    public function getPrestationsNom() {
        return [];
    }

    public function getWordingsArrFacturant() {
        return ['facturant'];
    }

    public function getEtablissementIdentifiant() {
        return $this->etablissement->getId();
    }

    public function getPrestations() {
        return [];
    }

    public function getLibelle() {
        return 'Devis';
    }

    public function isGarantie() {
        return false;
    }

    public function isControle() {
        return false;
    }

    public function isMouvementDeclenchable() {
        return false;
    }

    public function getNumeroPassage() {
        return null;
    }

    public function isAudit() {
        return false;
    }

    public function getMultiTechnicien () {
        return count($this->techniciens);
    }
}
