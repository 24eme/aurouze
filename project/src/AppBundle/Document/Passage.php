<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\ODM\MongoDB\Mapping\Annotations\PreUpdate;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Manager\ContratManager;
use AppBundle\Manager\PassageManager;
use AppBundle\Model\DocumentEtablissementInterface;
use AppBundle\Model\DocumentSocieteInterface;
use AppBundle\Model\DocumentPlanifiableInterface;
use AppBundle\Model\DocumentPlanifiableTrait;
use AppBundle\Document\Prestation;
use AppBundle\Document\Produit;
use AppBundle\Document\RendezVous;
use AppBundle\Document\EtablissementInfos;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\PassageRepository") @HasLifecycleCallbacks
 */
class Passage implements DocumentEtablissementInterface, DocumentSocieteInterface, DocumentPlanifiableInterface
{
    use DocumentPlanifiableTrait;

    const PREFIX = "PASSAGE";
    const DOCUMENT_TYPE = 'Passage';

    /**
     * @MongoDB\Id(strategy="CUSTOM", type="string", options={"class"="AppBundle\Document\Id\PassageGenerator"})
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiant;

    /**
     * @MongoDB\EmbedMany(targetDocument="Prestation")
     */
    protected $prestations;

    /**
     * @MongoDB\EmbedMany(targetDocument="Produit")
     */
    protected $produits;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $societeIdentifiant;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateModification;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $etablissementIdentifiant;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $libelle;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $commentaire;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Contrat", simple=true)
     */
    protected $contrat;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $numeroArchive;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $numeroContratArchive;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $mouvement_declenchable;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $mouvement_declenche;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $imprime;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiantReprise;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $typePassage;

    /**
     *  @MongoDB\Field(type="collection")
     */
    protected $nettoyages;

    /**
     *  @MongoDB\Field(type="collection")
     */
    protected $applications;

    /**
     *  @MongoDB\Field(type="date")
     */
    protected $duree;

    /**
     *  @MongoDB\Field(type="date")
     */
    protected $dureePrecedente;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $datePrecedente;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $numeroOrdre;

    /**
     * @MongoDB\EmbedMany(targetDocument="NiveauInfestation")
     */
    protected $niveauInfestation;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $signatureBase64;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $description;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $audit;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $multiTechnicien;

    public function __construct() {
        $this->etablissementInfos = new EtablissementInfos();
        $this->prestations = new ArrayCollection();
        $this->techniciens = new ArrayCollection();
        $this->produits = new ArrayCollection();
        $this->mouvement_declenchable = false;
        $this->mouvement_declenche = false;
        $this->nettoyages = array();
        $this->applications = array();
        $this->imprime = false;
    }

    public function getNbProduitsContrat($identifiant) {
        foreach ($this->getContrat()->getProduits() as $produit) {
            if ($produit->getIdentifiant() == $identifiant) {

                return $produit->getNbTotalContrat();
            }
        }

        return null;
    }

    public function calculNumeroOrdre() {
        if ($this->isControle()) {
            return "C";
        }
        if ($this->isGarantie()) {
            return "G";
        }
        $numero = 1;

        if(!$this->getContrat()) {
            return null;
        }

        foreach ($this->getContrat()->getPassages($this->getEtablissement()) as $passageId => $p) {
            if ($passageId == $this->getId()) {
                return $numero;
            }
            if ($p->isSousContrat()) {
                $numero++;
            }
        }

        return "?";
    }

    public function getNumeroPassage() {

        return $this->getNumeroOrdre();
    }

    public function getTechniciensIdentite() {
        $techniciens = array();

        foreach ($this->getTechniciens() as $technicien) {
            $techniciens[$technicien->getId()] = $technicien->getIdentiteCourt();
        }

        return $techniciens;
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

    public function getTechniciensIds() {
        $techniciens = array();

        foreach ($this->getTechniciens() as $technicien) {
            $techniciens[] = $technicien->getId();
        }

        sort($techniciens);

        return $techniciens;
    }

    /** @MongoDB\PreUpdate */
    public function preUpdate() {
        $this->setDateModification(new \DateTime());
        $this->updateStatut();
        $this->getLibelle();
        $this->getNumeroOrdre();
        if($this->getStatut() != PassageManager::STATUT_REALISE) {
            $this->pullEtablissementInfos();
        }
    }


    public function getIntitule() {

        return $this->getEtablissementInfos()->getIntitule();
    }

    public function getDureePrevisionnelle() {
		if ($this->getContrat()->getDureePassage()) {
			return str_replace('h', ':', $this->getContrat()->getDureePassageFormat());
		}
        return '01:00';
    }

    public function setDureePrevisionnelle($dureePrevisionnelle){}

    public function getPassageIdentifiant() {
        return $this->identifiant;
    }

    public function getSociete() {

        return $this->getEtablissement()->getSociete();
    }

    /**
     * Set id
     *
     * @param string $id
     * @return self
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
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
        if(!$this->libelle) {
            $this->libelle = $this->calculLibelle();
        }

        return $this->libelle;
    }

    public function calculLibelle() {
        $nbPassage = $this->getNumeroPassage();
        if ($nbPassage == 'G') {
            return "Passage en garantie";
        }
        if ($nbPassage == 'C') {
            return "Passage de contrôle";
        }
        $nbPassagePrevu = 0;
        if($this->getContrat()) {
            $nbPassagePrevu = $this->getContrat()->getNbPassagesPrevu();
        }
        return "Passage " . $nbPassage . " sur " . $nbPassagePrevu . " (sous contrat)";
    }

    public function getRegion() {

        return $this->getEtablissement()->getRegion();
    }

    public function getDateForPlanif() {
    	$today = new \DateTime();
    	if ($this->datePrevision && $this->datePrevision->format('Ymd') < $today->format('Ymd')) {
    		return $today;
    	}
    	return $this->datePrevision;
    }

    /**
     * Set mouvementDeclenchable
     *
     * @param boolean $mouvementDeclenchable
     * @return self
     */
    public function setMouvementDeclenchable($mouvementDeclenchable) {
        $this->mouvement_declenchable = $mouvementDeclenchable;
        return $this;
    }

    /**
     * Get mouvementDeclenchable
     *
     * @return boolean $mouvementDeclenchable
     */
    public function getMouvementDeclenchable() {
        return $this->mouvement_declenchable;
    }

    /**
     * Set mouvementDeclenche
     *
     * @param boolean $mouvementDeclenche
     * @return self
     */
    public function setMouvementDeclenche($mouvementDeclenche) {
        $this->mouvement_declenche = $mouvementDeclenche;
        return $this;
    }

    /**
     * Get mouvementDeclenche
     *
     * @return boolean $mouvementDeclenche
     */
    public function getMouvementDeclenche() {
        return $this->mouvement_declenche;
    }

    /**
     * Set contrat
     *
     * @param AppBundle\Document\Contrat $contrat
     * @return self
     */
    public function setContrat(\AppBundle\Document\Contrat $contrat) {
        $this->contrat = $contrat;
        return $this;
    }

    /**
     * Get contrat
     *
     * @return AppBundle\Document\Contrat $contrat
     */
    public function getContrat() {

        return $this->contrat;
    }

    public function pullEtablissementInfos() {
        if(!$this->getEtablissementInfos()) {
            return;
        }
        $this->getEtablissementInfos()->pull($this->getEtablissement());
    }

    /**
     * Set etablissement
     *
     * @param AppBundle\Document\Etablissement $etablissement
     * @return self
     */
    public function setEtablissement(\AppBundle\Document\Etablissement $etablissement) {
        $this->etablissement = $etablissement;
        $this->setEtablissementIdentifiant($etablissement->getIdentifiant());
        $this->pullEtablissementInfos();
        return $this;
    }

    /**
     * Get etablissement
     *
     * @return AppBundle\Document\Etablissement $etablissement
     */
    public function getEtablissement() {
        return $this->etablissement;
    }

    /**
     * Set numeroContratArchive
     *
     * @param string $numeroContratArchive
     * @return self
     */
    public function setNumeroContratArchive($numeroContratArchive) {
        $this->numeroContratArchive = $numeroContratArchive;
        return $this;
    }

    /**
     * Get numeroContratArchive
     *
     * @return string $numeroContratArchive
     */
    public function getNumeroContratArchive() {
        return $this->numeroContratArchive;
    }

    /**
     * Add prestation
     *
     * @param AppBundle\Document\Prestation $prestation
     */
    public function addPrestation(\AppBundle\Document\Prestation $prestation) {
        foreach ($this->getPrestations() as $prest) {
            if ($prest->getIdentifiant() == $prestation->getIdentifiant()) {
                return;
            }
        }
        $this->prestations[] = $prestation;
    }

    /**
     * Remove prestation
     *
     * @param AppBundle\Document\Prestation $prestation
     */
    public function removePrestation(\AppBundle\Document\Prestation $prestation) {
        $this->prestations->removeElement($prestation);
    }

    public function removeAllPrestations() {
        $this->prestations = new ArrayCollection();
    }

    /**
     * Get prestations
     *
     * @return \Doctrine\Common\Collections\Collection $prestations
     */
    public function getPrestations() {
        return $this->prestations;
    }

    public function getPrestationsNom() {
        $prestations = array();

        foreach ($this->getPrestations() as $prestation) {
            $prestations[] = $prestation->getNom();
        }

        return $prestations;
    }

    public function setTimeDebut($time) {
        $dateTime = $this->getDateDebut();
        $this->setDateDebut(new \DateTime($dateTime->format('Y-m-d') . 'T' . $time . ':00'));
    }

    public function setTimeFin($time) {
        $dateTime = $this->getDateFin();
        $this->setDateFin(new \DateTime($dateTime->format('Y-m-d') . 'T' . $time . ':00'));
    }

    public function getTimeDebut() {
        $dateTime = $this->getDateDebut();
        return ($dateTime) ? $dateTime->format('H:i') : null;
    }

    public function getTimeFin() {
        $dateTime = $this->getDateFin();
        return ($dateTime) ? $dateTime->format('H:i') : null;
    }

    /**
     * Add produit
     *
     * @param AppBundle\Document\Produit $produit
     */
    public function addProduit(\AppBundle\Document\Produit $produit) {
        foreach ($this->getProduits() as $prod) {
            if ($prod->getIdentifiant() == $produit->getIdentifiant()) {
                return;
            }
        }
        $this->produits[] = $produit;
    }

    /**
     * Remove produit
     *
     * @param AppBundle\Document\Produit $produit
     */
    public function removeProduit(\AppBundle\Document\Produit $produit) {
        $this->produits->removeElement($produit);
    }

    /**
     * Get produits
     *
     * @return \Doctrine\Common\Collections\Collection $produits
     */
    public function getProduits() {
        return $this->produits;
    }

    public function getStatutLibelleActions() {
        return PassageManager::$statutsLibellesActions[$this->getStatut()];
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
     * Set numeroArchive
     *
     * @param increment $numeroArchive
     * @return self
     */
    public function setNumeroArchive($numeroArchive) {
        $this->numeroArchive = $numeroArchive;
        return $this;
    }

    /**
     * Get numeroOrdre
     *
     * @return increment $numeroOrdre
     */
    public function getNumeroOrdre() {
        if(!$this->numeroOrdre) {
            $this->numeroOrdre = $this->calculNumeroOrdre();
        }
        return $this->numeroOrdre;
    }

    /**
     * Set numeroOrdre
     *
     * @param increment $numeroOrdre
     * @return self
     */
    public function setNumeroOrdre($numeroOrdre) {
        $this->numeroOrdre = $numeroOrdre;
        return $this;
    }

    /**
     * Get numeroArchive
     *
     * @return increment $numeroArchive
     */
    public function getNumeroArchive() {
        return $this->numeroArchive;
    }

    /**
     * Set typePassage
     *
     * @param string $typePassage
     * @return self
     */
    public function setTypePassage($typePassage) {
        $this->typePassage = $typePassage;
        return $this;
    }

    /**
     * Get typePassage
     *
     * @return string $typePassage
     */
    public function getTypePassage() {
        return $this->typePassage;
    }

    public function isSousContrat() {
        return $this->getTypePassage() == PassageManager::TYPE_PASSAGE_CONTRAT;
    }

    public function isControle() {
        return $this->getTypePassage() == PassageManager::TYPE_PASSAGE_CONTROLE;
    }

    public function isGarantie() {
        return $this->getTypePassage() == PassageManager::TYPE_PASSAGE_GARANTIE;
    }

    public function copyTechnicienFromPassage(Passage $p) {
        if (!count($this->getTechniciens()) && count($p->getTechniciens())) {
            foreach ($p->getTechniciens() as $technicien) {
                $this->addTechnicien($technicien);
            }
            return $this;
        }
        return false;
    }

    /**
     * Set nettoyages
     *
     * @param collection $nettoyages
     * @return self
     */
    public function setNettoyages($nettoyages) {
        $this->nettoyages = $nettoyages;
        return $this;
    }

    /**
     * Get nettoyages
     *
     * @return collection $nettoyages
     */
    public function getNettoyages() {
        return $this->nettoyages;
    }

    /**
     * Set applications
     *
     * @param collection $applications
     * @return self
     */
    public function setApplications($applications) {
        $this->applications = $applications;

        return $this;
    }

    /**
     * Get applications
     *
     * @return collection $applications
     */
    public function getApplications() {
        return $this->applications;
    }

    public function getPrevious() {

        $passagesEtablissement = $this->getContrat()->getPassagesEtablissementNode($this->getEtablissement());
        $previousPassage = null;
        $founded = false;
        foreach ($passagesEtablissement->getPassagesSorted() as $key => $passageEtb) {
            if ($founded) {
                return $previousPassage;
            }

            if ($this->getId() == $passageEtb->getId()) {
                $founded = true;
            } else {
                $previousPassage = $passageEtb;
            }
        }
        return null;
    }

    public function isImprime() {
        return $this->getImprime();
    }

    /**
     * Set imprime
     *
     * @param boolean $imprime
     * @return self
     */
    public function setImprime($imprime) {
        $this->imprime = $imprime;
        return $this;
    }

    /**
     * Get imprime
     *
     * @return boolean $imprime
     */
    public function getImprime() {
        return $this->imprime;
    }

   /**
    * Get duree
    *
    * @return date $duree
    */
    public function getDuree() {
       if (!$this->duree) {
           if (!$this->dateFin || !$this->dateDebut) {
               return null;
           }
           $interval = $this->dateFin->diff($this->dateDebut);
           return $interval->format('%Hh%I');
       }

       return $this->duree->format('H').'h'.$this->duree->format('i');
    }

    public function getDureeMinute() {
       if($duree = $this->getDuree()) {
       	if (strpos($duree, 'h') !== false) {
       		$ed = explode('h', $duree);
       		return ($ed[0] * 60) + $ed[1];
       	}
       }
       return 0;
    }

    public function getDureeDate() {
        if (!$this->duree) {
            if (!$this->dateFin || !$this->dateDebut) {

                return null;
            }

            $today = new \DateTime(date('Y-m-d 00:00:00'));
            return $today->sub($this->dateFin->diff($this->dateDebut));
        }

        return $this->duree;
    }

   /**
    * Set duree
    *
    * @param date $duree
    * @return self
    */
   public function setDuree($duree) {
       $this->duree = $duree;

       return $this;
   }

   /**
    * Get dureeRaw
    *
    * @return date $dureeRaw
    */
    public function getDureeRaw() {
       return $this->duree;
    }

   /**
    * Set duree
    *
    * @param date $dureeRaw
    * @return self
    */
   public function setDureeRaw($dureeRaw) {
       $this->duree = $dureeRaw;

       return $this;
   }



    /**
     * Set commentaire
     *
     * @param string $commentaire
     * @return self
     */
    public function setCommentaire($commentaire)
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    /**
     * Get commentaire
     *
     * @return string $commentaire
     */
    public function getCommentaire()
    {
        return $this->commentaire;
    }


    /**
     * Set dureePrecedente
     *
     * @param date $dureePrecedente
     * @return self
     */
    public function setDureePrecedente($dureePrecedente)
    {
        $this->dureePrecedente = $dureePrecedente;
        return $this;
    }

    /**
     * Get dureePrecedente
     *
     * @return date $dureePrecedente
     */
    public function getDureePrecedente()
    {
        if(!$this->dureePrecedente) {

            return null;
        }

        return $this->dureePrecedente->format('H').'h'.$this->dureePrecedente->format('i');
    }

    /**
     * Set datePrecedente
     *
     * @param date $datePrecedente
     * @return self
     */
    public function setDatePrecedente($datePrecedente)
    {
        $this->datePrecedente = $datePrecedente;
        return $this;
    }

    /**
     * Get datePrecedente
     *
     * @return date $datePrecedente
     */
    public function getDatePrecedente()
    {
        return $this->datePrecedente;
    }

    /**
     * Add niveauInfestation
     *
     * @param AppBundle\Document\NiveauInfestation $niveauInfestation
     */
    public function addNiveauInfestation(\AppBundle\Document\NiveauInfestation $niveauInfestation)
    {
        $this->niveauInfestation[] = $niveauInfestation;
    }

    /**
     * Remove niveauInfestation
     *
     * @param AppBundle\Document\NiveauInfestation $niveauInfestation
     */
    public function removeNiveauInfestation(\AppBundle\Document\NiveauInfestation $niveauInfestation)
    {
        $this->niveauInfestation->removeElement($niveauInfestation);
    }

    /**
     * Get niveauInfestation
     *
     * @return \Doctrine\Common\Collections\Collection $niveauInfestation
     */
    public function getNiveauInfestation()
    {
      if(!count($this->niveauInfestation)){
          foreach ($this->getPrestations() as $prestation) {
            $niveauInfestation = new niveauInfestation();
            $niveauInfestation->setIdentifiant($prestation->getIdentifiant());
            $this->addNiveauInfestation($niveauInfestation);
          }
        }
        return $this->niveauInfestation;
    }

    /**
     * Set signatureBase64
     *
     * @param string $signatureBase64
     * @return self
     */
    public function setSignatureBase64($signatureBase64)
    {
        $this->signatureBase64 = $signatureBase64;
        return $this;
    }

    /**
     * Get signatureBase64
     *
     * @return string $signatureBase64
     */
    public function getSignatureBase64()
    {
        return $this->signatureBase64;
    }

    /**
     * Set audit
     *
     * @param string $audit
     * @return self
     */
    public function setAudit($audit)
    {
        $this->audit = $audit;
        return $this;
    }

    /**
     * Get audit
     *
     * @return string $audit
     */
    public function getAudit()
    {
        return $this->audit;
    }

    /**
     * Set dateModification
     *
     * @param date $dateModification
     * @return self
     */
    public function setDateModification($dateModification)
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    /**
     * Get dateModification
     *
     * @return date $dateModification
     */
    public function getDateModification()
    {
        return $this->dateModification;
    }

    /**
     * Set multiTechnicien
     *
     * @param int $multiTechnicien
     * @return self
     */
    public function setMultiTechnicien($multiTechnicien) {
        $this->multiTechnicien = $multiTechnicien;
        return $this;
    }

    /**
     * Get multiTechnicien
     *
     * @return int $multiTechnicien
     */
    public function getMultiTechnicien() {
        return $this->multiTechnicien;
    }

    public static function triPerHourPrecedente($p_0, $p_1) {
        if(!$p_0->getDatePrecedente() && !$p_1->getDatePrecedente()){
          return 0;
        }
        if(!$p_0->getDatePrecedente()){
          return -1;
        }
        if(!$p_1->getDatePrecedente()){
          return +1;
        }
        if ($p_0->getDatePrecedente()->format('Hi') == $p_1->getDatePrecedente()->format('Hi')) {
                return 0;
        }
        return ($p_0->getDatePrecedente()->format('Hi') > $p_1->getDatePrecedente()->format('Hi')) ? +1 : -1;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function getDescriptionTransformed() {
        return str_replace('\n', "\n", $this->description);
    }

    public function getMouvementFacture(){
      foreach ($this->getContrat()->getMouvements() as $mouvement) {
        if($mouvement->getOrigineDocumentGeneration() && ($mouvement->getOrigineDocumentGeneration()->getId() == $this->getId())){
          return $mouvement;
        }
      }
      return null;
    }

    public function isMouvementAlreadyFacture(){
      $mvtPassage = $this->getMouvementFacture();
      if(!$mvtPassage) { return false; }
      return $mvtPassage->getFacture();
    }

    public function getWordingsArrFacturant(){
      return ($this->getMouvementDeclenchable())? array("facturant") : array("nonfacturant");
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
}
