<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Manager\EtablissementManager;
use AppBundle\Model\InterlocuteurInterface;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints as AssertMongo;

use Symfony\Component\Validator\Constraints as AssertDoctrine;
use AppBundle\Manager\ContratManager;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\SocieteRepository")
 */
class Societe implements InterlocuteurInterface {

    /**
     * @MongoDB\Id(strategy="CUSTOM", type="string", options={"class"="AppBundle\Document\Id\SocieteGenerator"})
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiant;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $raisonSociale;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $sousTraitant;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $commentaire;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $type;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $siret;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $codeComptable;

    /**
     * @MongoDB\EmbedOne(targetDocument="Adresse")
     */
    protected $adresse;

    /**
     * @MongoDB\EmbedOne(targetDocument="Sepa")
     */
    protected $sepa;

    /**
     * @MongoDB\EmbedOne(targetDocument="ContactCoordonnee")
     */
    protected $contactCoordonnee;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiantReprise;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiantAdresseReprise;

    /**
     * @MongoDB\Field(type="increment")
     */
    protected $etablissementIncrement;

    /**
     * @MongoDB\Field(type="increment")
     */
    protected $contratIncrement;

    /**
     * @MongoDB\Field(type="increment")
     */
    protected $compteIncrement;

    /**
     * @MongoDB\Field(type="increment")
     */
    protected $factureIncrement;

    /**
     *  @MongoDB\ReferenceMany(targetDocument="Etablissement", mappedBy="societe")
     */
    protected $etablissements;

    /**
     *  @MongoDB\ReferenceMany(targetDocument="Compte", mappedBy="societe")
     */
    protected $comptes;

    /**
     * @MongoDB\EmbedOne(targetDocument="Provenance")
     */
    protected $provenance;

    /**
     * @MongoDB\Field(type="collection")
     */
    protected $tags;

    /**
     *  @MongoDB\ReferenceMany(targetDocument="Attachement", mappedBy="societe")
     */
    protected $attachements;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $actif;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $frequencePaiement;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $methodeDeFacturation;

    public function __construct() {
        $this->etablissements = new \Doctrine\Common\Collections\ArrayCollection();
        $this->adresse = new Adresse();
        $this->sepa = new Sepa();
        $this->contactCoordonnee = new ContactCoordonnee();
        $this->setActif(true);
    }

    public function preInitRum($instanceapp = null){
        if(!$this->getSepa()){
            $this->sepa = new Sepa();
        }
        if(!$this->getSepa()->getRum()){
            $sepa = $this->getSepa();
            $sepa->setRum(strtoupper($instanceapp).$this->getIdentifiant());
        }
        if(strpos($this->getSepa()->getRum(),strtoupper($instanceapp)) === false){
          $sepa = $this->getSepa();
          $sepa->setRum(strtoupper($instanceapp).$this->getIdentifiant());
        }
    }

    public function generateCodeComptable($generateCodeComptable,$generateCodeComptableParticulier){

      $from = array(
      'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'ß', 'ç','Ç','è', 'é', 'ê', 'ë', 'È', 'É', 'Ê', 'Ë', 'ì',
      'í', 'î', 'ï','Ì', 'Í', 'Î', 'Ï', 'ñ', 'Ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö','š', 'Š','ù', 'ú', 'û', 'ü',
      'Ù', 'Ú', 'Û', 'Ü','ý', 'Ý', 'ž', 'Ž'
      );

      $to = array(
      'a', 'a', 'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'B',  'c', 'C','e', 'e', 'e',
      'e', 'E', 'E', 'E', 'E', 'i', 'i', 'i', 'i','I', 'I', 'I', 'I', 'n', 'N','o', 'o', 'o', 'o', 'o','O',
      'O', 'O', 'O', 'O','s', 'S', 'u', 'u', 'u', 'u','U', 'U', 'U', 'U', 'y',  'Y', 'z', 'Z'
      );

        if($generateCodeComptableParticulier && ($this->getType() == EtablissementManager::TYPE_ETB_PARTICULIER)){
            $this->setCodeComptable(strtoupper($generateCodeComptableParticulier));
        }
        elseif($generateCodeComptable){
            $this->setCodeComptable("2". substr((strtoupper(preg_replace("/[^a-z]/i", "",(str_replace($from, $to, $this->getRaisonSociale()))))), 0, 3).$this->getIdentifiant());
        }
    }

    public function getDestinataire() {

        return $this->getRaisonSociale();
    }

    public function getLibelleComplet() {

        return $this->getDestinataire() . ', ' . $this->getAdresse()->getLibelleComplet() . ', ' . $this->getIdentifiant();
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
     * Set raisonSociale
     *
     * @param string $raisonSociale
     * @return self
     */
    public function setRaisonSociale($raisonSociale) {
        $this->raisonSociale = $raisonSociale;
        return $this;
    }

    /**
     * Get raisonSociale
     *
     * @return string $raisonSociale
     */
    public function getRaisonSociale() {
        return $this->raisonSociale;
    }

    /**
     * Set sousTraitant
     *
     * @param boolean $sousTraitant
     * @return self
     */
    public function setSousTraitant($sousTraitant) {
        $this->sousTraitant = $sousTraitant;
        return $this;
    }

    /**
     * Get sousTraitant
     *
     * @return boolean $sousTraitant
     */
    public function getSousTraitant() {
        return $this->sousTraitant;
    }

    /**
     * Set commentaire
     *
     * @param string $commentaire
     * @return self
     */
    public function setCommentaire($commentaire) {
        $this->commentaire = $commentaire;
        return $this;
    }

    /**
     * Get commentaire
     *
     * @return string $commentaire
     */
    public function getCommentaire() {
        return $this->commentaire;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return self
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Get Siret
     *
     * @return string $siret
     */
    public function getSiret()
    {
        return $this->siret;
    }

    /**
     * Set Siret
     *
     * @param string $siret Le siret
     * @return $this
     */
    public function setSiret($siret)
    {
        $this->siret = $siret;
        return $this;
    }

    /**
     * Set codeComptable
     *
     * @param string $codeComptable
     * @return self
     */
    public function setCodeComptable($codeComptable) {
        $this->codeComptable = $codeComptable;
        return $this;
    }

    /**
     * Get codeComptable
     *
     * @return string $codeComptable
     */
    public function getCodeComptable() {
        return $this->codeComptable;
    }

    /**
     * Set adresse
     *
     * @param AppBundle\Document\Adresse $adresse
     * @return self
     */
    public function setAdresse(\AppBundle\Document\Adresse $adresse) {
        $this->adresse = $adresse;
        return $this;
    }

    /**
     * Get adresse
     *
     * @return AppBundle\Document\Adresse $adresse
     */
    public function getAdresse() {
        return $this->adresse;
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
     * Get sepa
     *
     * @return AppBundle\Document\Sepa $sepa
     */
    public function getSepa() {
        return $this->sepa;
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
     * Set identifiantAdresseReprise
     *
     * @param string $identifiantAdresseReprise
     * @return self
     */
    public function setIdentifiantAdresseReprise($identifiantAdresseReprise) {
        $this->identifiantAdresseReprise = $identifiantAdresseReprise;
        return $this;
    }

    /**
     * Get identifiantAdresseReprise
     *
     * @return string $identifiantAdresseReprise
     */
    public function getIdentifiantAdresseReprise() {
        return $this->identifiantAdresseReprise;
    }


    /**
     * Set etablissementIncrement
     *
     * @param increment $etablissementIncrement
     * @return self
     */
    public function setEtablissementIncrement($etablissementIncrement) {
        $this->etablissementIncrement = $etablissementIncrement;
        return $this;
    }

    /**
     * Get etablissementIncrement
     *
     * @return increment $etablissementIncrement
     */
    public function getEtablissementIncrement() {
        return $this->etablissementIncrement;
    }

    /**
     * Set contratIncrement
     *
     * @param increment $contratIncrement
     * @return self
     */
    public function setContratIncrement($contratIncrement) {
        $this->contratIncrement = $contratIncrement;
        return $this;
    }

    /**
     * Get contratIncrement
     *
     * @return increment $contratIncrement
     */
    public function getContratIncrement() {
        return $this->contratIncrement;
    }

    /**
     * Set factureIncrement
     *
     * @param increment $factureIncrement
     * @return self
     */
    public function setFactureIncrement($factureIncrement) {
        $this->factureIncrement = $factureIncrement;
        return $this;
    }

    /**
     * Get factureIncrement
     *
     * @return increment $factureIncrement
     */
    public function getFactureIncrement() {
        return $this->factureIncrement;
    }

    /**
     * Add etablissement
     *
     * @param AppBundle\Document\Etablissement $etablissement
     */
    public function addEtablissement(\AppBundle\Document\Etablissement $etablissement) {
        $this->etablissements[] = $etablissement;
    }

    /**
     * Remove etablissement
     *
     * @param AppBundle\Document\Etablissement $etablissement
     */
    public function removeEtablissement(\AppBundle\Document\Etablissement $etablissement) {
        $this->etablissements->removeElement($etablissement);
    }

    /**
     * Get etablissements
     *
     * @return \Doctrine\Common\Collections\Collection $etablissements
     */
    public function getEtablissements() {
        return $this->etablissements;
    }

    public function getEtablissementsByStatut($statut) {
        $etablissements = array();

        foreach($this->getEtablissements() as $etablissement) {
            if($etablissement->getActif() != $statut) {
                continue;
            }

            $etablissements[$etablissement->getId()] = $etablissement;
        }

        return $etablissements;
    }

    /**
     * Get comptes
     *
     * @return \Doctrine\Common\Collections\Collection $comptes
     */
    public function getComptes() {
        return $this->comptes;
    }

    public function getComptesByStatut($statut) {
        $comptes = array();

        foreach($this->getComptes() as $compte) {
            if($compte->getActif() != $statut) {
                continue;
            }

            $comptes[$compte->getId()] = $compte;
        }

        return $comptes;
    }

    public function getComptesLibelle($statut)
    {
    	$comptes = $this->getComptesByStatut($statut);
    	$libelle = '';
    	foreach ($comptes as $compte) {
    		if ($libelle) {
    			$libelle .= ' / ';
    		}
    		$libelle .= $compte->getIdentite().' ';
    		if ($compte->getContactCoordonnee()->getTelephoneFixe()) {
    			$libelle .= ' - '.$compte->getContactCoordonnee()->getTelephoneFixe();
    		}
    		if ($compte->getContactCoordonnee()->getTelephoneMobile()) {
    			$libelle .= ' - '.$compte->getContactCoordonnee()->getTelephoneMobile();
    		}
    		if($compte->getContactCoordonnee()->getEmail()) {
    			$libelle .= ' - '.$compte->getContactCoordonnee()->getEmail();
    		}
    	}
    	return $libelle;
    }

    public function getIcon() {
        return 'business';
    }

    public function getTypeLibelle() {
        return EtablissementManager::$type_libelles[$this->getType()];
    }

    /**
     * Set provenance
     *
     * @param AppBundle\Document\Provenance $provenance
     * @return self
     */
    public function setProvenance(\AppBundle\Document\Provenance $provenance) {
        $this->provenance = $provenance;
        return $this;
    }

    /**
     * Get provenance
     *
     * @return AppBundle\Document\Provenance $provenance
     */
    public function getProvenance() {
        return $this->provenance;
    }

    /**
     * Set tags
     *
     * @param collection $tags
     * @return self
     */
    public function setTags($tags) {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Get tags
     *
     * @return collection $tags
     */
    public function getTags() {
        return $this->tags;
    }

    public function getTagsNom() {
        $result= array();
        foreach ($this->getTags() as $tag) {
           $result[] = $tag;
        }
        return $result;
    }

    /**
     * Set contactCoordonnee
     *
     * @param AppBundle\Document\ContactCoordonnee $contactCoordonnee
     * @return self
     */
    public function setContactCoordonnee(\AppBundle\Document\ContactCoordonnee $contactCoordonnee) {
        $this->contactCoordonnee = $contactCoordonnee;
        return $this;
    }

    /**
     * Get contactCoordonnee
     *
     * @return AppBundle\Document\ContactCoordonnee $contactCoordonnee
     */
    public function getContactCoordonnee() {
        return $this->contactCoordonnee;
    }

    public function getIntitule() {
        return $this->getRaisonSociale() . " " . $this->getAdresse()->getIntitule() . ' (' . $this->identifiant . ')';
    }

    public function getAdresseComplete()
    {
    	return $this->getAdresse()->getLibelleComplet();
    }

    /**
     * Add compte
     *
     * @param AppBundle\Document\Compte $compte
     */
    public function addCompte(\AppBundle\Document\Compte $compte) {
        $this->comptes[] = $compte;
    }

    /**
     * Remove compte
     *
     * @param AppBundle\Document\Compte $compte
     */
    public function removeCompte(\AppBundle\Document\Compte $compte) {
        $this->comptes->removeElement($compte);
    }

    /**
     * Set compteIncrement
     *
     * @param increment $compteIncrement
     * @return self
     */
    public function setCompteIncrement($compteIncrement) {
        $this->compteIncrement = $compteIncrement;
        return $this;
    }

    /**
     * Get compteIncrement
     *
     * @return increment $compteIncrement
     */
    public function getCompteIncrement() {
        return $this->compteIncrement;
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
     * Get frequencePaiement
     *
     * @return string $frequencePaiement
     */
    public function getFrequencePaiement() {
        return $this->frequencePaiement;
    }


    public function getFrequencePaiementLibelle() {

        return ContratManager::$frequences[$this->frequencePaiement];
    }

    public function getSociete() {
    	return $this;
    }

    /**
     * Set methodeDeFacturation
     *
     * @param string $methodeDeFacturation
     * @return self
     */
    public function setMethodeDeFacturation($methodeDeFacturation) {
        $this->methodeDeFacturation = $methodeDeFacturation;
        return $this;
    }

    /**
     * Get methodeDeFacturation
     *
     * @return string $methodeDeFacturation
     */
    public function getMethodeDeFacturation() {
        return $this->methodeDeFacturation;
    }


    /**
     * Add attachement
     *
     * @param AppBundle\Document\Attachement $attachement
     */
    public function addAttachement(\AppBundle\Document\Attachement $attachement)
    {
        $this->attachements[] = $attachement;
    }

    /**
     * Remove attachement
     *
     * @param AppBundle\Document\Attachement $attachement
     */
    public function removeAttachement(\AppBundle\Document\Attachement $attachement)
    {
        $this->attachements->removeElement($attachement);
    }

    /**
     * Get attachements
     *
     * @return \Doctrine\Common\Collections\Collection $attachements
     */
    public function getAttachements()
    {
        return $this->attachements;
    }

    public function isFirstPrelevement(){
        if(!$this->sepa){
            throw new sfException("La societe ".$societe->getId()." ne posssède aucun SEPA");
        }
        return $this->sepa->isFirst();
    }

    public function __toString() {
        return $this->getLibelleComplet();
    }
}
