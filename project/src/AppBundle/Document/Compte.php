<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use AppBundle\Manager\CompteManager;
use AppBundle\Model\DocumentSocieteInterface;
use AppBundle\Model\InterlocuteurInterface;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Manager\ContratManager;
use AppBundle\Document\Adresse;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\CompteRepository") @HasLifecycleCallbacks
 */
class Compte implements DocumentSocieteInterface, InterlocuteurInterface {

    const PREFIX = "COMPTE";
    const COULEUR_DEFAUT = 'yellow';

    /**
     * @MongoDB\Id(strategy="CUSTOM", type="string", options={"class"="AppBundle\Document\Id\CompteGenerator"})
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiant;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $fonction;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $nom;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $prenom;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identite;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $couleur;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Societe", inversedBy="comptes", simple=true)
     */
    protected $societe;

    /**
     * @MongoDB\EmbedOne(targetDocument="Adresse")
     */
    protected $adresse;

    /**
     * @MongoDB\EmbedOne(targetDocument="ContactCoordonnee")
     */
    protected $contactCoordonnee;

    /**
     *  @MongoDB\ReferenceMany(targetDocument="Passage", mappedBy="techniciens")
     */
    protected $passages = array();

    /**
     *  @MongoDB\ReferenceMany(targetDocument="Facture", mappedBy="commercial")
     */
    protected $facturesProduit = array();

    /**
     *  @MongoDB\ReferenceMany(targetDocument="Devis", mappedBy="commercial")
     */
    protected $devis = array();

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $actif;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiantReprise;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $civilite;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $titre;

    /**
     *  @MongoDB\EmbedMany(targetDocument="CompteTag")
     */
    protected $tags = array();

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set id
     *
     * @return id $id
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
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
     * Set nom
     *
     * @param string $nom
     * @return self
     */
    public function setNom($nom) {
        $this->nom = $nom;
        return $this;
    }

    /**
     * Get nom
     *
     * @return string $nom
     */
    public function getNom() {
        return $this->nom;
    }

    /**
     * Set prenom
     *
     * @param string $prenom
     * @return self
     */
    public function setPrenom($prenom) {
        $this->prenom = $prenom;
        return $this;
    }

    /**
     * Get prenom
     *
     * @return string $prenom
     */
    public function getPrenom() {
        return $this->prenom;
    }

    /**
     * Set identite
     *
     * @param string $identite
     * @return self
     */
    public function setIdentite($identite) {
        $this->identite = $identite;
        return $this;
    }

    /**
     * Get identite
     *
     * @return string $identite
     */
    public function getIdentite() {
        $identite = ($this->getCivilite()) ? $this->getCivilite() . ' ' : '';
        $identite.= ($this->getPrenom()) ? $this->getPrenom() . ' ' : '';
        $identite.= ($this->getNom()) ? $this->getNom() . ' ' : '';
        return $identite;
    }

    /**
     * Set couleur
     *
     * @param string $couleur
     * @return self
     */
    public function setCouleur($couleur) {
        $this->couleur = $couleur;
        return $this;
    }

    /**
     * Get couleur
     *
     * @return string $couleur
     */
    public function getCouleur() {
        if (!$this->couleur) {

            return '#ffffff';
        }
        return $this->couleur;
    }

    public function getCouleurText() {
        if (!$this->getCouleur() || $this->getCouleur() == '#ffffff') {

            return '#000000';
        }

        return '#ffffff';
    }

    public function getTag($identifiantTag) {
        foreach ($this->tags as $tag) {
            if ($tag->getIdentifiant() == $identifiantTag) {
                return $tag;
            }
        }
        return false;
    }

    public function hasTag($tagSearch) {
        foreach ($this->tags as $tag) {
            if ($tag->getIdentifiant() == $tagSearch) {
                return true;
            }
        }
        return false;
    }

    public function isTechnicien() {
        return $this->hasTag(CompteManager::TYPE_TECHNICIEN);
    }

    public function isCommercial() {
        return $this->hasTag(CompteManager::TYPE_COMMERCIAL);
    }

    public function isAutre() {
        return $this->hasTag(CompteManager::TYPE_AUTRE);
    }

    public function getIdentiteCourt() {

        return ($this->getNom())? $this->getPrenom().' '. substr(ucfirst($this->getNom()),0,1).'.' : $this->getPrenom();
    }

    public function getDestinataire() {

        return $this->getIdentite();
    }

    public function getLibelleComplet() {

        return ($this->getAdresse()->isEmpty())?  $this->getDestinataire() : $this->getDestinataire() . ', ' . $this->getAdresse()->getLibelleComplet();
    }

    public function __toString() {

        return $this->getIdentite();
    }

    public function __construct(Societe $societe) {
        $this->adresse = new Adresse();
        $this->contactCoordonnee = new ContactCoordonnee();
        $this->passages = new ArrayCollection();
        $this->facturesProduit = new ArrayCollection();
        $this->devis = new ArrayCollection();
        $this->prestations = new ArrayCollection();
        $this->setSociete($societe);

    }

    /**
     * Add passage
     *
     * @param AppBundle\Document\Passage $passage
     */
    public function addPassage(\AppBundle\Document\Passage $passage) {
        $this->passages[] = $passage;
    }

    /**
     * Remove passage
     *
     * @param AppBundle\Document\Passage $passage
     */
    public function removePassage(\AppBundle\Document\Passage $passage) {
        $this->passages->removeElement($passage);
    }

    /**
     * Get passages
     *
     * @return \Doctrine\Common\Collections\Collection $passages
     */
    public function getPassages() {
        return $this->passages;
    }

    /**
     * Set actif
     *
     * @param boolean $actif
     * @return self
     */
    public function setActif($actif) {
        $this->actif = $actif;
        return $this;
    }

    /**
     * Get actif
     *
     * @return boolean $actif
     */
    public function getActif() {
        return $this->actif;
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
     * Add tag
     *
     * @param AppBundle\Document\CompteTag $tag
     */
    public function addTag(\AppBundle\Document\CompteTag $tag) {
        foreach ($this->getTags() as $t) {
            if ($t->getIdentifiant() == $tag->getIdentifiant()) {
                return;
            }
        }

        $this->tags[] = $tag;
    }

    /**
     * Remove tag
     *
     * @param AppBundle\Document\CompteTag $tag
     */
    public function removeTag(\AppBundle\Document\CompteTag $tag) {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection $tags
     */
    public function getTags() {
        return $this->tags;
    }

    public function getTagsArray() {
        $result= array();
        foreach ($this->getTags() as $tag) {
           $result[$tag->getIdentifiant()] = $tag;
        }
        return $result;
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
     * Set adresse
     *
     * @param AppBundle\Document\Adresse $adresse
     * @return self
     */
    public function setAdresse(Adresse $adresse) {
        $this->adresse = $adresse;
        return $this;
    }

    /**
     * Get adresse
     *
     * @return AppBundle\Document\Adresse $adresse
     */
    public function getAdresse() {
        if(!$this->adresse) {

            return clone $this->getSociete()->getAdresse();
        }
        return $this->adresse;
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

    /** @MongoDB\PreUpdate */
    public function preUpdate() {
        $this->pullInfosFromSociete();
    }

    /** @MongoDB\PrePersist */
    public function prePersist() {
        $this->pullInfosFromSociete();
    }

    public function getSameAdresse() {
        return $this->isSameAdresseThanSociete();
    }

    public function setSameAdresse($value) {
        return $this;
    }

    public function getSameContact() {
        return $this->isSameContactCoordonneeThanSociete();
    }

    public function setSameContact($value) {
        return $this;
    }

    public function isSameAdresseThanSociete() {
        return $this->getAdresse()->isSameThan($this->getSociete()->getAdresse());
    }

    public function isSameContactCoordonneeThanSociete() {

        return $this->getContactCoordonnee()->isSameThan($this->getSociete()->getContactCoordonnee());
    }

    public function pullInfosFromSociete() {
        if ($this->isSameAdresseThanSociete()) {
            $this->getAdresse()->copyFrom($this->getSociete()->getAdresse());
        }
        if ($this->isSameContactCoordonneeThanSociete()) {
            $this->getContactCoordonnee()->copyFrom($this->getSociete()->getContactCoordonnee());
        }
    }

    public function isActif() {
        return boolval($this->getActif());
    }

    /**
     * Set civilite
     *
     * @param string $civilite
     * @return self
     */
    public function setCivilite($civilite) {
        $this->civilite = $civilite;
        return $this;
    }

    /**
     * Get civilite
     *
     * @return string $civilite
     */
    public function getCivilite() {
        return $this->civilite;
    }

    /**
     * Set titre
     *
     * @param string $titre
     * @return self
     */
    public function setTitre($titre) {
        $this->titre = $titre;
        return $this;
    }

    /**
     * Get titre
     *
     * @return string $titre
     */
    public function getTitre() {
        return $this->titre;
    }


    /**
     * Set fonction
     *
     * @param string $fonction
     * @return self
     */
    public function setFonction($fonction)
    {
        $this->fonction = $fonction;
        return $this;
    }

    /**
     * Get fonction
     *
     * @return string $fonction
     */
    public function getFonction()
    {
        return $this->fonction;
    }

    public function getIcon()
    {
    	return 'face';
    }

    /**
     * Add facturesProduit
     *
     * @param AppBundle\Document\Facture $facturesProduit
     */
    public function addFacturesProduit(\AppBundle\Document\Facture $facturesProduit)
    {
        $this->facturesProduit[] = $facturesProduit;
    }

    /**
     * Remove facturesProduit
     *
     * @param AppBundle\Document\Facture $facturesProduit
     */
    public function removeFacturesProduit(\AppBundle\Document\Facture $facturesProduit)
    {
        $this->facturesProduit->removeElement($facturesProduit);
    }

    /**
     * Get facturesProduit
     *
     * @return \Doctrine\Common\Collections\Collection $facturesProduit
     */
    public function getFacturesProduit()
    {
        return $this->facturesProduit;
    }

    /**
     * Add devis
     *
     * @param AppBundle\Document\Devis $devis
     */
    public function addDevis(\AppBundle\Document\Devis $devis)
    {
        $this->devis[] = $devis;
    }

    /**
     * Remove devis
     *
     * @param AppBundle\Document\Devis $devis
     */
    public function removeDevis(\AppBundle\Document\Devis $devis)
    {
        $this->devis->removeElement($devis);
    }

    /**
     * Get devis
     *
     * @return \Doctrine\Common\Collections\Collection $devis
     */
    public function getDevis()
    {
        return $this->devis;
    }

    /**
     * Add devi
     *
     * @param AppBundle\Document\Devis $devi
     */
    public function addDevi(\AppBundle\Document\Devis $devi)
    {
        $this->devis[] = $devi;
    }

    /**
     * Remove devi
     *
     * @param AppBundle\Document\Devis $devi
     */
    public function removeDevi(\AppBundle\Document\Devis $devi)
    {
        $this->devis->removeElement($devi);
    }
}
