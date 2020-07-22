<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Model\DocumentSocieteInterface;
use AppBundle\Manager\FactureManager;
use AppBundle\Manager\ContratManager;
use AppBundle\Model\FacturableInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\FactureRepository") @HasLifecycleCallbacks
 */
class Facture implements DocumentSocieteInterface, FacturableInterface
{
    const DOCUMENT_TYPE = 'Facture';

    /**
     * @MongoDB\Id(strategy="CUSTOM", type="string", options={"class"="AppBundle\Document\Id\FactureGenerator"})
     */
    protected $id;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Societe", inversedBy="factures", simple=true)
     */
    protected $societe;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Compte", inversedBy="facturesProduit")
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
    protected $dateFacturation;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateDevis;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $datePaiement;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateLimitePaiement;

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
     * @MongoDB\Field(type="float")
     */
    protected $montantPaye;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $montantAPayer;

    /**
     * @MongoDB\EmbedMany(targetDocument="LigneFacturable")
     */
    protected $lignes;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiantReprise;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $description;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $numeroFacture;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $numeroDevis;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $avoir;

     /**
     * @MongoDB\ReferenceOne(targetDocument="Facture", inversedBy="factures", simple=true)
     */
    protected $origineAvoir;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Paiements", mappedBy="paiement.facture", simple=true, repositoryMethod="findPaiementsByFacture")
     */
    protected $paiements;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $frequencePaiement;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $cloture;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $nbRelance;

    /**
     * @MongoDB\EmbedMany(targetDocument="Relance")
     */
    protected $relances;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $relanceCommentaire;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $avoirPartielRemboursementCheque;

    /**
     * @MongoDB\EmbedOne(targetDocument="Sepa")
     */
    protected $sepa;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $inPrelevement;

    public function __construct() {
        $this->lignes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->emetteur = new Soussigne();
        $this->destinataire = new Soussigne();
        $this->paiements = new \Doctrine\Common\Collections\ArrayCollection();
        $this->cloture = false;
        $this->avoirPartielRemboursementCheque = false;
    }

    /** @MongoDB\PreUpdate */
    public function preUpdate() {
        if (round($this->getMontantTTC() - $this->getMontantPaye(), 2) <= 0) {
            $this->cloture = true;
        } else {
            $this->cloture = false;
        }
    }

    public function updateCalcul() {
        $montant = 0;
        $montantTaxe = 0;
        foreach ($this->getLignes() as $ligne) {
            $ligne->update();
            $montant = $montant + $ligne->getMontantHT();
            $montantTaxe = $montantTaxe + $ligne->getMontantTaxe();
        }
        $this->setMontantHT(round($montant, 2));
        $this->setMontantTaxe(round($montantTaxe, 2));
        $this->setMontantTTC(round($montant + $montantTaxe, 2));
    }

    public function update() {
        $this->updateCalcul();
        $this->storeDestinataire();
        $this->setDateLimitePaiement($this->calculDateLimitePaiement());
        $this->updateMouvementsContrat();
        $this->updateMontantPaye();
    }

    public function updateMouvementsContrat() {
        foreach ($this->getLignes() as $ligne) {
            $ligne->updateMouvementContrat();
        }
    }

    public function storeDestinataire() {
        $societe = $this->getSociete();
        $destinataire = $this->getDestinataire();

        $nom = $societe->getRaisonSociale();

        if($this->getContrat() && $this->getContrat()->getFactureDestinataire()) {
            $nom = $this->getContrat()->getFactureDestinataire();
        }

        $destinataire->setRaisonSociale($societe->getRaisonSociale());
        $destinataire->setNom($nom);
        $destinataire->setAdresse($societe->getAdresse()->getAdresseFormatee());
        $destinataire->setCodePostal($societe->getAdresse()->getCodePostal());
        $destinataire->setCommune($societe->getAdresse()->getCommune());
        $destinataire->setCodeComptable($societe->getCodeComptable());
    }

    public function facturerMouvements() {
        foreach ($this->getLignes() as $ligne) {
            $ligne->facturerMouvement();
        }
    }

    public function isPaye() {

        return $this->isCloture();
    }

    public function getContrat() {
        foreach($this->getLignes() as $ligne) {
            if($ligne->isOrigineContrat()) {

                return $ligne->getOrigineDocument();
            }
        }
    }

    public function getOrigines() {
        $origines = array();
        foreach ($this->getLignes() as $ligne) {
            if (!$ligne->getOrigineDocument()) {
                continue;
            }
            $origines[$ligne->getOrigineDocument()->getId()] = $ligne->getOrigineDocument();
        }

        return $origines;
    }

    /**
     * Add ligne
     *
     * @param AppBundle\Document\LigneFacturable $ligne
     */
    public function addLigne(\AppBundle\Document\LigneFacturable $ligne) {
        $this->lignes[] = $ligne;
    }

    /**
     * Remove ligne
     *
     * @param AppBundle\Document\LigneFacturable $ligne
     */
    public function removeLigne(\AppBundle\Document\LigneFacturable $ligne) {
        $this->lignes->removeElement($ligne);
    }

    /**
     * Get lignes
     *
     * @return \Doctrine\Common\Collections\Collection $lignes
     */
    public function getLignes() {
        return $this->lignes;
    }

    /**
     * Set montantHT
     *
     * @param float $montantHT
     * @return self
     */
    public function setMontantHT($montantHT) {
        $this->montantHT = $montantHT;
        return $this;
    }

    /**
     * Get montantHT
     *
     * @return float $montantHT
     */
    public function getMontantHT() {
        return $this->montantHT;
    }

    /**
     * Set montantTTC
     *
     * @param float $montantTTC
     * @return self
     */
    public function setMontantTTC($montantTTC) {
        $this->montantTTC = $montantTTC;
        return $this;
    }

    /**
     * Get montantTTC
     *
     * @return float $montantTTC
     */
    public function getMontantTTC() {
        return $this->montantTTC;
    }

    /**
     * Set montantTaxe
     *
     * @param float $montantTaxe
     * @return self
     */
    public function setMontantTaxe($montantTaxe) {
        $this->montantTaxe = $montantTaxe;
        return $this;
    }

    /**
     * Get montantTaxe
     *
     * @return float $montantTaxe
     */
    public function getMontantTaxe() {
        return $this->montantTaxe;
    }

    /**
     * Set dateEmission
     *
     * @param date $dateEmission
     * @return self
     */
    public function setDateEmission($dateEmission) {
        $this->dateEmission = $dateEmission;
        return $this;
    }

    /**
     * Get dateEmission
     *
     * @return date $dateEmission
     */
    public function getDateEmission() {
        return $this->dateEmission;
    }

    /**
     * Set dateFacturation
     *
     * @param date $dateFacturation
     * @return self
     */
    public function setDateFacturation($dateFacturation) {
        $this->dateFacturation = $dateFacturation;
        return $this;
    }

    /**
     * Get dateFacturation
     *
     * @return date $dateFacturation
     */
    public function getDateFacturation() {
        return $this->dateFacturation;
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
     * Set societe
     *
     * @param AppBundle\Document\Societe $societe
     * @return self
     */
    public function setSociete(\AppBundle\Document\Societe $societe, $store = true) {
        $this->societe = $societe;
        if($store) {
            $this->storeDestinataire();
        }
        if($this->societe->getSepa()){
            $this->setSepa($this->societe->getSepa());
        }
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
     * Set origineAvoir
     *
     * @param AppBundle\Document\Facture $facture
     * @return self
     */
    public function setOrigineAvoir(\AppBundle\Document\Facture $facture) {
    	$this->origineAvoir = $facture;

    	return $this;
    }

    /**
     * Get origineAvoir
     *
     * @return AppBundle\Document\Facture $facture
     */
    public function getOrigineAvoir() {
    	return $this->origineAvoir;
    }

    /**
     * Get id
     *
     * @return string $id
     */
    public function getId() {
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
     * @return self
     */
    public function setDestinataire(\AppBundle\Document\Soussigne $destinataire) {
        $this->destinataire = $destinataire;
        return $this;
    }

    /**
     * Get destinataire
     *
     * @return AppBundle\Document\Soussigne $destinataire
     */
    public function getDestinataire() {
        return $this->destinataire;
    }

    /**
     * Set identifiantReprise
     *
     * @param string $identifiantReprise
     * @return self
     */
    public function setIdentifiantReprise($identifiantReprise) {
        $this->identifiantReprise = $identifiantReprise;
        return $this;
    }

    /**
     * Get identifiantReprise
     *
     * @return string $identifiantReprise
     */
    public function getIdentifiantReprise() {
        return $this->identifiantReprise;
    }

    /**
     * Set numeroFacture
     *
     * @param string $numeroFacture
     * @return self
     */
    public function setNumeroFacture($numeroFacture) {
        $this->numeroFacture = $numeroFacture;
        return $this;
    }

    /**
     * Get numeroFacture
     *
     * @return string $numeroFacture
     */
    public function getNumeroFacture() {
        return $this->numeroFacture;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return self
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string $description
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set avoir
     *
     * @param string $avoir
     * @return self
     */
    public function setAvoir($avoir) {
        $this->avoir = $avoir;
        return $this;
    }

    /**
     * Get sepa
     *
     * @return AppBundle\Document\Sepa $sepa
     */
    public function getSepa() {
        $sepa = $this->sepa;
        if(!$sepa || !$sepa->getIban() || !$sepa->getBic()){
            $sepa = $this->getSociete()->getSepa();
        }
        return $sepa;
    }

    /**
     * Set sepa
     *
     * @param AppBundle\Document\Sepa $sepa
     * @return self
     */
    public function setSepa(\AppBundle\Document\Sepa $sepa) {
        $this->sepa = $sepa;
        return $this;
    }

    /**
     * Get avoir
     *
     * @return string $avoir
     */
    public function getAvoir() {
        return $this->avoir;
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

    public function getTva() {
        $tva = 0;
        foreach ($this->getLignes() as $ligne) {
            if (!$tva) {
                $tva = $ligne->getTauxTaxe();
            }
            if ($tva != $ligne->getTauxTaxe()) {
                throw new \Exception("TVA différente dans les lignes de facture.");
            }
        }
        return $tva;
    }

    public function calculDateLimitePaiement() {
        if($this->getSepa() && $this->getSepa()->getActif()) {

            return $this->getPrelevementDate();
        }

        $frequence = $this->getFrequencePaiement();
        $date = null;
        if($this->getDateFacturation()) {
            $date = clone $this->getDateFacturation();
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

    public function getFrequencePaiementLibelle() {

        return ContratManager::$frequences[$this->getFrequencePaiement()];
    }

    /**
     * Get frequencePaiement
     *
     * @return string $frequencePaiement
     */
    public function getFrequencePaiement() {
        if ($this->frequencePaiement) {

            return $this->frequencePaiement;
        }

        if (!$this->frequencePaiement) {
            foreach ($this->getLignes() as $ligne) {
                if ($ligne->isOrigineContrat() && $ligne->getOrigineDocument()->getFrequencePaiement()) {

                    return $ligne->getOrigineDocument()->getFrequencePaiement();
                }
            }
        }

        if (!$this->frequencePaiement) {

            return ContratManager::FREQUENCE_RECEPTION;
        }
    }

    /**
     * Set frequencePaiement
     *
     * @param string $frequencePaiement
     * @return self
     */
    public function setFrequencePaiement($frequencePaiement) {
        $this->frequencePaiement = $frequencePaiement;

        return $this;
    }

    /**
     * Add paiement
     *
     * @param AppBundle\Document\Paiement $paiement
     */
    public function addPaiement(\AppBundle\Document\Paiement $paiement) {
        $this->paiements[] = $paiement;
    }

    /**
     * Remove paiement
     *
     * @param AppBundle\Document\Paiement $paiement
     */
    public function removePaiement(\AppBundle\Document\Paiement $paiement) {
        $this->paiements->removeElement($paiement);
    }

    /**
     * Get paiements
     *
     * @return \Doctrine\Common\Collections\Collection $paiements
     */
    public function getPaiements() {
        return $this->paiements;
    }

    public function __toString() {
        return "N°" . $this->getNumeroFacture() . " " . $this->getDestinataire()->getNom() . " (" . $this->getMontantAPayer() . "€ / " . $this->getMontantTTC() . "€ TTC)";
    }

    public function __clone() {
        $this->removeId();

        $lignes = $this->lignes;
        $this->lignes = new \Doctrine\Common\Collections\ArrayCollection();
        foreach($lignes as $ligne) {
            $this->lignes[] = clone $ligne;
        }
    }

    /**
     * Set montantPaye
     *
     * @param float $montantPaye
     * @return self
     */
    public function setMontantPaye($montantPaye) {
        $this->montantPaye = $montantPaye;
        $this->updateRestantAPayer();
        return $this;
    }

    /**
     * Get montantPaye
     *
     * @return float $montantPaye
     */
    public function getMontantPaye() {
        if (!$this->montantPaye) {
            return 0.0;
        }
        return $this->montantPaye;
    }

    public function ajoutMontantPaye($montant) {
        $this->setMontantPaye($this->getMontantPaye() + $montant);
        return $this;
    }

    public function updateMontantPaye($output = null) {
        $this->setMontantPaye(0.0);
        foreach ($this->getPaiements() as $paiements) {
            foreach ($paiements->getPaiement() as $paiement) {
            	if ($p = $paiement->getFacture()) {
                if ($p->getId() == $this->getId()) {
                    if ($output) {
                        $output->writeln(sprintf("<comment>Ajout d'un paiement de %s euros HT pour facture d'id %s </comment>", $paiement->getMontant(), $this->getId()));
                    }
                    $this->ajoutMontantPaye($paiement->getMontant());
                }
            	}
            }
        }
    }

    /**
     * Set cloture
     *
     * @param boolean $cloture
     * @return self
     */
    public function setCloture($cloture) {
        $this->cloture = $cloture;
        return $this;
    }

    /**
     * Get cloture
     *
     * @return boolean $cloture
     */
    public function getCloture() {
        return $this->cloture;
    }

    public function isCloture() {
        return $this->cloture;
    }

    public function updateRestantAPayer() {
        $this->setMontantAPayer(round($this->getMontantTTC() - $this->getMontantPaye(), 2));
    }

    public function getRestantAPayer() {
        return round($this->getMontantTTC() - $this->getMontantPaye(), 2);
    }

    /**
     * Set montantAPayer
     *
     * @param float $montantAPayer
     * @return self
     */
    public function setMontantAPayer($montantAPayer) {
        $this->montantAPayer = $montantAPayer;
        return $this;
    }

    /**
     * Get montantAPayer
     *
     * @return float $montantAPayer
     */
    public function getMontantAPayer() {
        return $this->montantAPayer;
    }

    /* Set dateDevis
     *
     * @param date $dateDevis
     * @return self
     */

    public function setDateDevis($dateDevis) {
        $this->dateDevis = $dateDevis;
        return $this;
    }

    /* Get dateDevis
     *
     * @return date $dateDevis
     */

    public function getDateDevis() {
        return $this->dateDevis;
    }

    /**
     * @deprecated Devis déplacés dans un doc différent
     */
    public function isDevis() {

        return false;
        /* return $this->getDateDevis() && !$this->isFacture(); */
    }

    public function hasDevis()
    {
        return (bool) $this->getNumeroDevis();
    }

    public function isFacture() {

        return ($this->getDateFacturation() || $this->getNumeroFacture());
    }

    public function removeId() {
        $this->id = null;
    }
    public function removeNumeroFacture() {
        $this->numeroFacture = null;
    }


    /**
     * Set numeroDevis
     *
     * @param string $numeroDevis
     * @return self
     */
    public function setNumeroDevis($numeroDevis) {
        $this->numeroDevis = $numeroDevis;
        return $this;
    }

    /**
     * Get numeroDevis
     *
     * @return string $numeroDevis
     */
    public function getNumeroDevis() {
        return $this->numeroDevis;
    }

    public function isEnRetardPaiement() {
        if($this->isDevis()) {

            return false;
        }

        if (!$this->isCloture() && ($this->getDateLimitePaiement()->format('Ymd') < (new \DateTime())->format('Ymd'))) {
            return true;
        }
        return false;
    }

    public function isAvoir() {
        return $this->getMontantTTC() < 0;
    }

    public function isRedressee() {
        return boolval($this->getAvoir());
    }

    public function cloturer() {
        $this->setMontantPaye($this->getMontantTTC());
    }

    public function decloturer() {
        $this->updateMontantPaye();
    }
    public function isDecloturable() {
       return $this->getMontantPayeCalcule() != $this->getMontantTTC();
    }

    public function getMontantPayeCalcule() {
        $sommeMontantPayeCalcule = 0.0;
        foreach ($this->getPaiements() as $paiements) {
            foreach ($paiements->getPaiement() as $paiement) {
                if ($paiement->getFacture()->getId() == $this->getId()) {
                    $sommeMontantPayeCalcule+=$paiement->getMontant();
                }
            }
        }
        return $sommeMontantPayeCalcule;
    }

    public function genererAvoir(){
        $avoir = clone $this;
        $avoir->removeNumeroFacture();
        $avoir->setCloture(true);
        $avoir->setOrigineAvoir($this);
        $avoir->setMontantPaye(-1 * $avoir->getMontantTTC());
        $avoir->setMontantHT(-1 * $avoir->getMontantHT());
        $avoir->setMontantTaxe(-1 * $avoir->getMontantTaxe());
        $avoir->setMontantTTC(-1 * $avoir->getMontantTTC());
        foreach($avoir->getLignes() as $ligne) {
            $ligne->setQuantite(-1 * $ligne->getQuantite());
        }
        $avoir->setDateEmission(new \DateTime());
        $avoir->setDateFacturation(new \DateTime());
        $avoir->setDateLimitePaiement($avoir->calculDateLimitePaiement());
        return $avoir;
    }

    public function isEditable() {

        return !$this->isCloture() || $this->isAvoir();
    }

    public function getLibelle() {
    	return $this->getNumeroFacture();
    }


    /**
     * Set commercial
     *
     * @param AppBundle\Document\Compte $commercial
     * @return self
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
     * Set nbRelance
     *
     * @param int $nbRelance
     * @return self
     */
    public function setNbRelance($nbRelance)
    {
        $this->nbRelance = $nbRelance;
        return $this;
    }

    /**
     * Get nbRelance
     *
     * @return int $nbRelance
     */
    public function getNbRelance()
    {
        return $this->nbRelance;
    }

    public function getRelanceColor()
    {
        $nb = $this->getNbRelance();
        if(!$nb){
          return "";
        }
        if($nb == 1){
          return "background-color: #d9edf7";
        }
        if($nb == 2){
          return "background-color: #fcf8e3";
        }
        if($nb >= 2 ){
          return "background-color:#f2dede";
        }
    }

    public function has1ereRelance(){
      return $this->getNbRelance() >= 1;
    }
    public function has2ndRelance(){
      return $this->getNbRelance() >= 2;
    }
    public function has3emeRelance(){
      return $this->getNbRelance() >= 3;
    }


    /**
     * Set relanceCommentaire
     *
     * @param string $relanceCommentaire
     * @return self
     */
    public function setRelanceCommentaire($relanceCommentaire)
    {
        $this->relanceCommentaire = $relanceCommentaire;
        return $this;
    }

    /**
     * Get relanceCommentaire
     *
     * @return string $relanceCommentaire
     */
      public function getRelanceCommentaire()
    {
        return $this->relanceCommentaire;
    }

    /**
     * Set avoirPartielRemboursementCheque
     *
     * @param boolean $avoirPartielRemboursementCheque
     * @return self
     */
    public function setAvoirPartielRemboursementCheque($avoirPartielRemboursementCheque)
    {
        $this->avoirPartielRemboursementCheque = $avoirPartielRemboursementCheque;
        return $this;
    }

    /**
     * Get avoirPartielRemboursementCheque
     *
     * @return boolean $avoirPartielRemboursementCheque
     */
    public function getAvoirPartielRemboursementCheque()
    {
        return $this->avoirPartielRemboursementCheque;
    }

    /**
     * Add relance
     *
     * @param AppBundle\Document\Relance $relance
     */
    public function addRelance(\AppBundle\Document\Relance $relance)
    {
        $this->relances[] = $relance;
    }

    /**
     * Remove relance
     *
     * @param AppBundle\Document\Relance $relance
     */
    public function removeRelance(\AppBundle\Document\Relance $relance)
    {
        $this->relances->removeElement($relance);
    }

    /**
     * Get relances
     *
     * @return \Doctrine\Common\Collections\Collection $relances
     */
    public function getRelances()
    {
        return $this->relances;
    }

    public function getRelanceObjNumero($numeroRelance){
      foreach ($this->getRelances() as $relanceObj) {
        if($numeroRelance == $relanceObj->getNumeroRelance()){
          return $relanceObj;
        }
      }
      return null;
    }

    /**
     * Set inPrelevement
     *
     * @param date $inPrelevement
     * @return $this
     */
    public function setInPrelevement($inPrelevement)
    {
        $this->inPrelevement = $inPrelevement;
        return $this;
    }

    /**
     * Get inPrelevement
     *
     * @return date $inPrelevement
     */
    public function getInPrelevement()
    {
        return $this->inPrelevement;
    }

    public function getPrelevementDate(){
      $dateEmission = clone $this->getDateEmission();
      if($dateEmission->format('m') > 20){
          $dateEmission->modify("+2 month");
      }else{
          $dateEmission->modify("+1 month");
      }
      $dueDate = \DateTime::createFromFormat("Ymd",$dateEmission->format("Y").$dateEmission->format("m")."20");
      $now = new \DateTime();
      if($dueDate < $now){
          if($now->format('m') > 20){
              $now->modify("+1 month");
          }
          $dueDate = \DateTime::createFromFormat("Ymd",$now->format("Y").$now->format("m")."20");
      }
      return $dueDate;
    }

    public function getDocumentType()
    {
        return self::DOCUMENT_TYPE;
    }

    public function getNumero()
    {
        return $this->getNumeroFacture();
    }

}
