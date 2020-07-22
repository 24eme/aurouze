<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use AppBundle\Model\EtablissementInfosInterface;
use AppBundle\Model\DocumentSocieteInterface;
use AppBundle\Model\InterlocuteurInterface;
use AppBundle\Document\Adresse;
use AppBundle\Manager\EtablissementManager;
use AppBundle\Document\Contrat;
use AppBundle\Document\Attachement;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\EtablissementRepository") @HasLifecycleCallbacks
 */
class Etablissement implements DocumentSocieteInterface, EtablissementInfosInterface, InterlocuteurInterface {

    const PREFIX = "ETABLISSEMENT";

    /**
     * @MongoDB\Id(strategy="CUSTOM", type="string", options={"class"="AppBundle\Document\Id\EtablissementGenerator"})
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiant;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Societe", inversedBy="etablissements", simple=true)
     */
    protected $societe;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $nom;

    /**
     * @MongoDB\EmbedOne(targetDocument="Adresse")
     */
    protected $adresse;

    /**
     * @MongoDB\EmbedOne(targetDocument="ContactCoordonnee")
     */
    protected $contactCoordonnee;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $type;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiantReprise;

    /**
     *  @MongoDB\ReferenceMany(targetDocument="Contrat", mappedBy="etablissement")
     */
    protected $contrats = array();

    /**
     *  @MongoDB\ReferenceMany(targetDocument="Passage", mappedBy="etablissement")
     */
    protected $passages;

    /**
     * @MongoDB\Field(type="increment")
     */
    protected $numeroPassageIncrement;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $commentaire;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $commentairePlanification;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $actif;

    /**
     *  @MongoDB\ReferenceMany(targetDocument="Attachement", mappedBy="etablissement")
     */
    protected $attachements;

    public function __construct() {
        $this->adresse = new Adresse();
        $this->contactCoordonnee = new ContactCoordonnee();
        $this->setActif(true);
    }

    public function getIdentite() {

        return $this->getNom();
    }

    /** @MongoDB\PreUpdate */
    public function preUpdate() {
        $this->pullInfosFromSociete();
    }

    /** @MongoDB\PrePersist */
    public function prePersist() {
        $this->pullInfosFromSociete();
    }

    public function updatePassages() {
        if($this->getPassages() && count($this->getPassages()))
        foreach($this->getPassages() as $passage) {
            if($passage->isRealise() || $passage->isAnnule()) {
                continue;
            }
            $passage->pullEtablissementInfos();
        }
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
     * Set contrats
     *
     * @param array $contrats
     * @return self
     */
    public function setContrats($contrats) {
        $this->contrats = $contrats;
        return $this;
    }

    /**
     * Add contrats
     *
     * @param Contrat $contrat
     * @return self
     */
    public function addContrat($contrat) {
        $this->contrats[] = $contrat;
        return $this;
    }

    /**
     * Get contrats
     *
     * @return array contrats
     */
    public function getContrats() {
        return $this->contrats;
    }

    public function getRaisonSociale() {
        return $this->getSociete()->getRaisonSociale();
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

    public function getSimpleNom() {
        return $this->nom;
    }

    public function setSimpleNom($simpleNom) {
        $this->nom = $simpleNom;
        return $this;
    }

    /**
     * Set setNom
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
    public function getNom($includeRaisonSociale = true) {
        if ($includeRaisonSociale && (trim($this->getSociete()->getRaisonSociale()) != trim($this->nom))) {
            return $this->getSociete()->getRaisonSociale() . ' - ' . $this->nom;
        }
        return $this->nom;
    }

    /**
     * Set adresse
     *
     * @param Adresse $adresse
     * @return self
     */
    public function setAdresse(Adresse $adresse) {
        $this->adresse = $adresse;
        return $this;
    }

    /**
     * Get adresse
     *
     * @return Adresse $adresse
     */
     public function getAdresse() {
         if(!$this->adresse || $this->adresse->isEmpty()) {

             return clone $this->getSociete()->getAdresse();
         }
         return $this->adresse;
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

    public function getTelephoneFixe() {
        if (!$this->getContactCoordonnee()) {
            return null;
        }
        return $this->getContactCoordonnee()->getTelephoneFixe();
    }

    public function getTelephonePortable() {
        if (!$this->getContactCoordonnee()) {
            return null;
        }
        return $this->getContactCoordonnee()->getTelephoneMobile();
    }

    public function getFax() {
        if (!$this->getContactCoordonnee()) {
            return null;
        }
        return $this->getContactCoordonnee()->getFax();
    }

    public function getEmail() {
        if (!$this->getContactCoordonnee()) {
            return null;
        }
        return $this->getContactCoordonnee()->getEmail();
    }

    public function getSiteInternet() {
        if (!$this->getContactCoordonnee()) {
            return null;
        }
        return $this->getContactCoordonnee()->getSiteInternet();
    }

    public function getIcon() {
        if(!$this->getType()){ return ""; }
        return EtablissementManager::$type_icon[$this->getType()];
    }

    public function getDestinataire() {

        return $this->getNom(true);
    }

    public function getLibelleComplet() {

        return $this->getDestinataire() . ', ' . $this->getAdresse()->getLibelleComplet();
    }

    public function getIntitule($includeRaisonSociale = true) {

        return $this->getNom($includeRaisonSociale) . ' ' . $this->getAdresse()->getIntitule() . ' (' . $this->identifiant . ')';
    }

    public function __toString() {

        return $this->getIntitule();
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
     * Get id
     *
     * @return string $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Remove contrat
     *
     * @param AppBundle\Document\Contrat $contrat
     */
    public function removeContrat(\AppBundle\Document\Contrat $contrat) {
        $this->contrats->removeElement($contrat);
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

    /**
     * Set numeroPassageIncrement
     *
     * @param increment $numeroPassageIncrement
     * @return self
     */
    public function setNumeroPassageIncrement($numeroPassageIncrement) {
        $this->numeroPassageIncrement = $numeroPassageIncrement;
        return $this;
    }

    /**
     * Get numeroPassageIncrement
     *
     * @return increment $numeroPassageIncrement
     */
    public function getNumeroPassageIncrement() {
        return $this->numeroPassageIncrement;
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
     * Add passage
     *
     * @param AppBundle\Document\Passage $passage
     */
    public function addPassage(\AppBundle\Document\Passage $passage)
    {
        $this->passages[] = $passage;
    }

    /**
     * Remove passage
     *
     * @param AppBundle\Document\Passage $passage
     */
    public function removePassage(\AppBundle\Document\Passage $passage)
    {
        $this->passages->removeElement($passage);
    }

    /**
     * Get passages
     *
     * @return \Doctrine\Common\Collections\Collection $passages
     */
    public function getPassages()
    {
        return $this->passages;
    }

    public function getRegion() {

        return EtablissementManager::getRegion($this->getAdresse()->getCodePostal());
    }

    /**
     * Set commentairePlanification
     *
     * @param string $commentairePlanification
     * @return self
     */
    public function setCommentairePlanification($commentairePlanification)
    {
        $this->commentairePlanification = $commentairePlanification;
        return $this;
    }

    public function getAdresseComplete()
    {
    	return $this->getAdresse()->getLibelleComplet();
    }

    /**
     * Get commentairePlanification
     *
     * @return string $commentairePlanification
     */
    public function getCommentairePlanification()
    {
        return $this->commentairePlanification;
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
}
