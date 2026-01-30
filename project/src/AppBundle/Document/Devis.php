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
use AppBundle\Manager\FactureManager;
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

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiantFacture;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $numeroCommande;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $zone;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $pdfTelecharge;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateLimitePaiement;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $frequencePaiement;



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
     * Set identifiantFacture
     *
     * @param string $identifiantFacture
     * @return $this
     */
    public function setIdentifiantFacture($identifiantFacture)
    {
        $this->identifiantFacture = $identifiantFacture;
        return $this;
    }

    /**
     * Get identifiantFacture
     *
     * @return string $identifiantFacture
     */
    public function getIdentifiantFacture()
    {
        return $this->identifiantFacture;
    }

    /**
     * Set numeroCommande
     *
     * @param string $numeroCommande
     * @return $this
     */
    public function setNumeroCommande($numeroCommande)
    {
        $this->numeroCommande = $numeroCommande;
        return $this;
    }

    /**
     * Get numeroCommande
     *
     * @return string $numeroCommande
     */
    public function getNumeroCommande()
    {
        return $this->numeroCommande;
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
        $this->calculDateLimitePaiement();
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
     * Get commentaire
     * @return null
     */
    public function getCommentaire()
    {
        return null;
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

    public function getSecretKey(){
      $id=$this->getId();
      $secretKey = getenv('SECRETKEY');
      return hash ('sha256' , $id.$secretKey);
    }

    /**
     * Get zone
     *
     * @return string $zone
     */
    public function getZone(){
      return $this->zone;
    }

    /**
     * Set zone
     *
     * @param string $zone
     * @return self
     */

    public function setZone($commercialSeineEtMarne){
        if($commercialSeineEtMarne && preg_match("/".$commercialSeineEtMarne."/", $this->getCommercial()->getNom())) {
        $this->zone = ContratManager::ZONE_SEINE_ET_MARNE;
      } else {
        $this->zone = ContratManager::ZONE_PARIS;
      }
      return $this;
    }

    /**
     * Set pdfTelecharge
     *
     * @param date $pdfTelecharge
     * @return self
     */
    public function setPdfTelecharge($pdfTelecharge) {
        $this->pdfTelecharge = $pdfTelecharge;
        return $this;
    }

    /**
     * Get pdfTelecharge
     *
     * @return date $pdfTelecharge
     */
    public function getPdfTelecharge() {
        return $this->pdfTelecharge;
    }

    /**
     * Set frequencePaiement
     *
     * @param string $frequencePaiement
     * @return self
     */
    public function setFrequencePaiement($frequencePaiement) {
        $this->frequencePaiement = $this->getSociete()->getFrequencePaiement();
        return $this;
    }

    /**
     * Get frequencePaiement
     *
     * @return string $frequencePaiement
     */
    public function getFrequencePaiement() {
        $frequencePaiement = $this->getSociete()->getFrequencePaiement();

        if (! $this->frequencePaiement) {
            $this->setFrequencePaiement($frequencePaiement);
        }

        return $this->frequencePaiement;
    }

    public function getFrequencePaiementLibelle() {

        return ContratManager::$frequences[$this->getFrequencePaiement()];
    }

    /**
     * Set dateLimitePaiement
     *
     * @param date $dateLimitePaiement
     * @return self
     */
    public function setDateLimitePaiement($dateLimitePaiement) {
        $this->dateLimitePaiement = $dateLimitePaiement;
        return $this;
    }

    /**
     * Get dateLimitePaiement
     *
     * @return date $dateLimitePaiement
     */
    public function getDateLimitePaiement() {
        if (is_null($this->dateLimitePaiement)) {

            return clone $this->calculDateLimitePaiement();
        }

        return $this->dateLimitePaiement;
    }

    public function calculDateLimitePaiement() {
            $frequence = $this->getSociete()->getFrequencePaiement();
            $date = null;
            if($this->getDateAcceptation()) {
                $date = clone $this->getDateAcceptation();
            }
            $date = ($date) ? $date : clone $this->getDateEmission();
            $date = ($date) ? $date : new \DateTime();
            switch ($frequence) {
                case ContratManager::FREQUENCE_PRELEVEMENT :
                    $date->modify('+2 month');
                    $date->modify('first day of')->modify('+19 day');
                    break;
                case ContratManager::FREQUENCE_30J :
                    $date->modify('+30 day');
                    break;
                case ContratManager::FREQUENCE_30JMOIS :
                    $date->modify('+30 day')->modify('last day of');
                    break;
                case ContratManager::FREQUENCE_45JMOIS :
                    $date->modify('+45 day')->modify('last day of');
                    break;
                case ContratManager::FREQUENCE_60J :
                    $date->modify('+60 day');
                    break;
                default:
                    $date->modify('+' . FactureManager::DEFAUT_FREQUENCE_JOURS . ' day');
            }

        return $date;
    }

}
