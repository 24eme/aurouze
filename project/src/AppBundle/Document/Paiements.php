<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use Behat\Transliterator\Transliterator;
use AppBundle\Manager\PaiementsManager;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\PaiementsRepository")
 * @HasLifecycleCallbacks
 */
class Paiements {

    const PREFIX = "PAIEMENTS";

    /**
     * @MongoDB\Id(strategy="CUSTOM", type="string", options={"class"="AppBundle\Document\Id\PaiementsGenerator"})
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $version;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiant;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateCreation;

    /**
     * @MongoDB\EmbedMany(targetDocument="Paiement")
     */
    protected $paiement;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $numeroRemise;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $imprime;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $prelevement;

    /**
     * @MongoDB\Field(type="string")
     *
     */
    protected $xmlbase64;

    protected $dm;

    public function __construct(DocumentManager $dm) {
        $this->paiement = new ArrayCollection();
        $this->imprime = false;
        $this->dm = $dm;
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
     * Set version
     *
     * @param string $version
     * @return self
     */
    public function setVersion($version) {
        $this->version = $version;
        return $this;
    }

    /**
     * Get version
     *
     * @return string $version
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * Set dateCreation
     *
     * @param date $dateCreation
     * @return self
     */
    public function setDateCreation($dateCreation) {
        $this->dateCreation = $dateCreation;
        return $this;
    }


    public function getDate() {
        return $this->dateCreation;
    }

    /**
     * Get dateCreation
     *
     * @return date $dateCreation
     */
    public function getDateCreation() {
        return $this->dateCreation;
    }

    /**
     * Add paiement
     *
     * @param AppBundle\Document\Paiement $paiement
     */
    public function addPaiement(\AppBundle\Document\Paiement $paiement) {
        $this->paiement[] = $paiement;
    }

    /**
     * Remove paiement
     *
     * @param AppBundle\Document\Paiement $paiement
     */
    public function removePaiement(\AppBundle\Document\Paiement $paiement) {
        $this->paiement->removeElement($paiement);
    }

    /**
     * Get paiement
     *
     * @return \Doctrine\Common\Collections\Collection $paiement
     */
    public function getPaiement() {
        return $this->paiement;
    }

    public function getAggregatePaiements($societe = null) {
    	$result = array();
    	foreach ($this->getPaiement() as $paiement) {
    		if ($societe && $paiement->getFacture()->getSociete()->getId() != $societe->getId()) {
    			continue;
    		}
    		$k = ($paiement->getMoyenPaiement())? $paiement->getMoyenPaiement() : md5(microtime().rand());
    		if (!isset($result[$k])) {
    			$result[$k] = array();
    			$result[$k]['items'] = array();
    			$result[$k]['montant'] = 0;
    			$result[$k]['factures'] = 0;
    		}

    		$result[$k]['libelle'] = ($paiement->getMoyenPaiement())? PaiementsManager::$moyens_paiement_libelles[$k] : '';
    		$result[$k]['factures'] += 1;
    		$result[$k]['montant'] += $paiement->getMontant();

    		$key = ($paiement->getLibelle())? Transliterator::urlize($paiement->getLibelle()) : md5(microtime().rand());
    		if (!isset($result[$k]['items'][$key])) {
    			$result[$k]['items'][$key] = array();
    			$result[$k]['items'][$key]['items'] = array();
    			$result[$k]['items'][$key]['montant'] = 0;
    			$result[$k]['items'][$key]['factures'] = 0;
    		}

    		$result[$k]['items'][$key]['libelle'] = $paiement->getLibelle();
    		$result[$k]['items'][$key]['montant'] += $paiement->getMontant();
    		$result[$k]['items'][$key]['factures'] += 1;

    		$result[$k]['items'][$key]['items'][] = $paiement;
    	}
    	return $result;
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

    public function isImprime() {
        return $this->imprime;
    }

    public function isRemiseEspece(){
      if(count($this->getPaiement()) != 1){
        return false;
      }

      foreach ($this->getPaiement() as $paiement) {
        if($paiement->getMoyenPaiement() != PaiementsManager::MOYEN_PAIEMENT_ESPECE){
          return false;
        }
      }
      return true;
    }

    public function getPaiementUniqueParLibelle(){
      $paiementsUnique = array();

      foreach ($this->getPaiement() as $paiement) {
        if(!$paiement->getLibelle() || $paiement->getLibelle() == ""){
          $key = md5(microtime().rand());
          $paiementsUnique[$key] = clone $paiement;
        }else{
          $key = Transliterator::urlize($paiement->getMoyenPaiement().'-'.$paiement->getLibelle());
          if(!array_key_exists($key,$paiementsUnique)){
            $paiementsUnique[$key] = clone $paiement;
            $paiementsUnique[$key]->setMontantTemporaire($paiement->getMontant());
          }else{
            $paiementsUnique[$key]->addMontantTemporaire($paiement->getMontant());
          }
          $paiementsUnique[$key]->addFactureTemporaire($paiement->getFacture());
        }
      }
      return $paiementsUnique;
    }

    public function nbPaiementUniqueParMoyen($moyen = PaiementsManager::MOYEN_PAIEMENT_CHEQUE){
        $nb = 0;
        foreach ($this->getPaiementUniqueParLibelle() as $paiement) {
          if($paiement->getMoyenPaiement() == $moyen){
            $nb++;
          }
        }
        return $nb;
    }

    public function getMontantTotal() {
        $montantTotal = 0;
        foreach ($this->getPaiement() as $paiement) {
            $montantTotal+=$paiement->getMontant();
        }
        return $montantTotal;
    }

    public function getMontantTotalByOperation($paiement) {
        $montantTotal = 0;
        foreach ($this->getPaiement() as $p) {
	    if($p->getDatePaiement()->format('Y-m-d') != $paiement->getDatePaiement()->format('Y-m-d') || $p->getMoyenPaiement() != $paiement->getMoyenPaiement() || $p->getLibelle() != $paiement->getLibelle()) {
		continue;
	    }
            $montantTotal += $p->getMontant();
        }
        return $montantTotal;
    }

    public function getMontantTotalByMoyenPaiement($moyen_paiement) {
        $montantTotal = 0;
        foreach ($this->getPaiement() as $paiement) {
            if($moyen_paiement == $paiement->getMoyenPaiement()) {
                $montantTotal+=$paiement->getMontant();
            }
        }
        return $montantTotal;
    }

    public function getPaiementBySociete($societe) {
        $paiementBySoc = array();
        foreach ($this->getPaiement() as $paiement) {
            if ($paiement->getFacture()->getSociete() == $societe) {
                $paiementBySoc[] = $paiement;
            }
        }
        return $paiementBySoc;
    }

    public function getTotalBySociete($societe) {
        $montantTotal = 0;
        foreach ($this->getPaiementBySociete($societe) as $paiement) {
            $montantTotal+=$paiement->getMontant();
        }
        return $montantTotal;
    }

    public function getFacturesArrayIds() {
        return array_keys($this->getFacturesArray());
    }

    public function getFacturesArray() {
        $factureArray = array();
        foreach ($this->getPaiement() as $paiement) {
        	if ($p = $paiement->getFacture()) {
        		$factureArray[$p->getId()] = $p;
        	}
        }
        return $factureArray;
    }

    public function getNbPaiementsSaisis(){
      $cpt = 0;
      foreach ($this->getPaiement() as $paiement) {
        	if ($paiement->getFacture() && ($paiement->getMontant() > 0.0) && $paiement->getMoyenPaiement() && $paiement->getTypeReglement()) {
        		$cpt++;
        	}
        }
        return $cpt;
    }

    public function isAllSaisis(){
        return ($this->getNbPaiementsSaisis() == count($this->getPaiement()));
    }


    /**
     * Set numeroRemise
     *
     * @param string $numeroRemise
     * @return self
     */
    public function setNumeroRemise($numeroRemise)
    {
        $this->numeroRemise = $numeroRemise;
        return $this;
    }

    /**
     * Get numeroRemise
     *
     * @return string $numeroRemise
     */
    public function getNumeroRemise()
    {
        return $this->numeroRemise;
    }

    /**
     * Set prelevement
     *
     * @param boolean $prelevement
     * @return $this
     */
    public function setPrelevement($prelevement)
    {
        $this->prelevement = $prelevement;
        return $this;
    }

    /**
     * Get prelevement
     *
     * @return boolean $prelevement
     */
    public function getPrelevement()
    {
        return $this->prelevement;
    }

    /**
     * Set xmlbase64
     *
     * @param string $xmlbase64
     * @return $this
     */
    public function setXmlbase64($xmlbase64)
    {
        $this->xmlbase64 = $xmlbase64;
        return $this;
    }

    /**
     * Get xmlbase64
     *
     * @return string $xmlbase64
     */
    public function getXmlbase64()
    {
        return $this->xmlbase64;
    }

    public function getRepository() {
        return ($this->dm)? $this->dm->getRepository('AppBundle:Paiements') : null;
    }

    /** @MongoDB\PreUpdate */
    public function preUpdate() {
        $this->manageVersion();
    }

    /** @MongoDB\PrePersist */
    public function prePersist() {
        $this->manageVersion();
    }

    private function manageVersion() {
        if (!$this->getId()) {
            return;
        }
        if (!$this->getRepository()) {
            return;
        }
        $recDoc = $this->getRepository()->find($this->getId());
        if (!$recDoc || !$recDoc->getVersion()) {
            return;
        }
        if ($recDoc->getVersion() == $this->getVersion()) {
            $this->setVersion(md5(serialize($this)));
        }
        throw new \Exception("Document update conflict");
    }
}
