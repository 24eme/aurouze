<?php
namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\ODM\MongoDB\Mapping\Annotations\PreUpdate;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\RendezVousRepository") @HasLifecycleCallbacks
 */
class RendezVous {

    /**
     * @MongoDB\Id(strategy="CUSTOM", type="string", options={"class"="AppBundle\Document\Id\RendezVousGenerator"})
     */
    protected $id;

    /**
     * @MongoDB\String
     */
    protected $titre;

    /**
     * @MongoDB\String
     */
    protected $description;

    /**
     * @MongoDB\Date
     */
    protected $dateDebut;

    /**
     * @MongoDB\Date
     */
    protected $dateFin;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Compte", inversedBy="lesRendezVous", simple=true)
     */
    protected $participants;

    /**
     * @MongoDB\String
     */
    protected $lieu;

    /**
    * @MongoDB\ReferenceOne(targetDocument="Passage", simple=true)
     */
    protected $passage;

    public function __construct()
    {
        $this->participants = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getEventJson($backgroundColor) {
        $event = new \stdClass();
        $event->id = $this->getId();
        $event->title = $this->getTitre().$this->getPassage()->getStatut();
        $event->start = $this->getDateDebut()->format('c');
        $event->end = $this->getDateFin()->format('c');
        $event->textColor = $this->getTextColor();
        $event->backgroundColor =  $this->getStatusColor();
        $event->borderColor = $this->getBorderColor();

        return $event;
    }

    public function getBorderColor() {
        if($this->getPassage() && $this->getPassage()->isPlanifie()) {

            return "#bce8f1";
        }

        if($this->getPassage() && $this->getPassage()->isRealise()) {

            return "#d6e9c6";
        }

        return '#000';
    }

    public function getTextColor() {
        if($this->getPassage() && $this->getPassage()->isPlanifie()) {

            return "#31708f";
        }

        if($this->getPassage() && $this->getPassage()->isRealise()) {

            return "#3c763d";
        }

        return '#000';
    }

    public function getStatusColor() {
        if($this->getPassage() && $this->getPassage()->isPlanifie()) {

            return "#d9edf7";
        }

        if($this->getPassage() && $this->getPassage()->isRealise()) {

            return "#dff0d8";
        }

        return '#fff';
    }

    public function pushToPassage() {
        if(!$this->getPassage()) {

            return;
        }

        $this->getPassage()->setDateDebut($this->getDateDebut());
        $this->getPassage()->setDateFin($this->getDateFin());
    }

    /** @MongoDB\PreUpdate */
    public function preUpdate() {
        $this->pushToPassage();
    }

    /** @MongoDB\PrePersist */
    public function prePersist() {
        $this->pushToPassage();
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
     * Set titre
     *
     * @param string $titre
     * @return self
     */
    public function setTitre($titre)
    {
        $this->titre = $titre;
        return $this;
    }

    /**
     * Get titre
     *
     * @return string $titre
     */
    public function getTitre()
    {
        return $this->titre;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return self
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

    /**
     * Set dateDebut
     *
     * @param date $dateDebut
     * @return self
     */
    public function setDateDebut($dateDebut)
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    /**
     * Get dateDebut
     *
     * @return date $dateDebut
     */
    public function getDateDebut()
    {
        return $this->dateDebut;
    }

    /**
     * Set dateFin
     *
     * @param date $dateFin
     * @return self
     */
    public function setDateFin($dateFin)
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    /**
     * Get dateFin
     *
     * @return date $dateFin
     */
    public function getDateFin()
    {
        return $this->dateFin;
    }

    /**
     * Add participant
     *
     * @param AppBundle\Document\Compte $participant
     */
    public function addParticipant(\AppBundle\Document\Compte $participant)
    {
        $this->participants[] = $participant;
    }

    /**
     * Remove participant
     *
     * @param AppBundle\Document\Compte $participant
     */
    public function removeParticipant(\AppBundle\Document\Compte $participant)
    {
        $this->participants->removeElement($participant);
    }

    /**
     * Get participants
     *
     * @return \Doctrine\Common\Collections\Collection $participants
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    public function removeAllParticipants() {
        $this->participants = new ArrayCollection();
    }

    /**
     * Set lieu
     *
     * @param string $lieu
     * @return self
     */
    public function setLieu($lieu)
    {
        $this->lieu = $lieu;
        return $this;
    }

    /**
     * Get lieu
     *
     * @return string $lieu
     */
    public function getLieu()
    {
        return $this->lieu;
    }

    /**
     * Set passage
     *
     * @param AppBundle\Document\Passage $passage
     * @return self
     */
    public function setPassage(\AppBundle\Document\Passage $passage)
    {
        $this->passage = $passage;
        return $this;
    }

    /**
     * Get passage
     *
     * @return AppBundle\Document\Passage $passage
     */
    public function getPassage()
    {
        return $this->passage;
    }
}
