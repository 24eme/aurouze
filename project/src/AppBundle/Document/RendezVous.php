<?php
namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\ODM\MongoDB\Mapping\Annotations\PreUpdate;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\RendezVousRepository") @HasLifecycleCallbacks
 */
class RendezVous {

    const COLOR_BORDER_BLUE = '#bce8f1';
    const COLOR_BORDER_YELLOW = '#faebcc';
    const COLOR_BORDER_GREEN = '#d6e9c6';
    const COLOR_BORDER_RED = '#ebccd1';
    const COLOR_BORDER_GREY = '#e1e1e8';

    const COLOR_TEXT_BLUE = '#31708f';
    const COLOR_TEXT_MAROON = '#8a6d3b';
    const COLOR_TEXT_BROWN = '#7d5e09';
    const COLOR_TEXT_GREEN = '#3c763d';
    const COLOR_TEXT_RED = '#a94442';
    const COLOR_TEXT_BLACK = '#333';

    const COLOR_STATUS_BLUE = '#d9edf7';
    const COLOR_STATUS_YELLOW = '#fcf8e3';
    const COLOR_STATUS_GOLD = '#ffd55f';
    const COLOR_STATUS_GREEN = '#dff0d8';
    const COLOR_STATUS_RED = '#f2dede';
    const COLOR_STATUS_WHITE = '#f7f7f9';

    /**
     * @MongoDB\Id(strategy="CUSTOM", type="string", options={"class"="AppBundle\Document\Id\RendezVousGenerator"})
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $titre;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $description;

    /**
     * @MongoDB\Field(type="date")
     * @Assert\NotBlank()
     */
    protected $dateDebut;

    /**
     * @MongoDB\Field(type="date")
     * @Assert\NotBlank()
     * @Assert\Expression(
     *     "this.getDateDebut() <= this.getDateFin()",
     *     message="La date de fin doit être supérieur à la date de début"
     * )
     */
    protected $dateFin;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Compte", inversedBy="lesRendezVous", simple=true)
     */
    protected $participants;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $lieu;

    /**
    * @MongoDB\ReferenceOne(targetDocument="Passage", simple=true)
     */
    protected $passage;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Devis", simple=true)
     */
    protected $devis;

  /**
   * @MongoDB\Field(type="bool")
   */
   protected $rendezVousConfirme;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $prerequis;

    public function __construct()
    {
        $this->participants = new \Doctrine\Common\Collections\ArrayCollection();
        $this->rendezVousConfirme = true;
    }

    public function getEventJson() {
        $colors = $this->calculateTilesColors();

        $event = new \stdClass();
        $event->id = $this->getId();
        $event->title = $this->getTitre();
        $event->start = $this->getDateDebut()->format('c');
        $event->end = $this->getDateFin()->format('c');
        $event->textColor = $colors['text'];
        $event->backgroundColor = $colors['background'];
        $event->borderColor = $colors['border'];
        $event->rendezVousConfirme = $this->getRendezVousConfirme();
        return $event;
    }

    public function calculateTilesColors()
    {
        $planifiable = $this->getPlanifiable();
        $colors = [
            'border' => self::COLOR_BORDER_GREY,
            'text' => self::COLOR_TEXT_BLACK,
            'background' => self::COLOR_STATUS_WHITE
        ];

        if (! $planifiable) { return $colors; }

        if ($planifiable->isAnnule()) {
            return $colors = [
                'border' => self::COLOR_BORDER_RED,
                'text' => self::COLOR_TEXT_RED,
                'background' => self::COLOR_STATUS_RED
            ];
        }

        if ($planifiable->isPlanifie()) {
            $colors['border'] = self::COLOR_BORDER_BLUE;
        }

        if ($planifiable->isRealise()) {
            $colors['border'] = self::COLOR_BORDER_GREEN;
        }

        if ($planifiable->isSaisieTechnicien()) {
            if ($planifiable->isPdfNonEnvoye() || $planifiable->isPdfNonEnvoye() === null) {
                $colors['text'] = self::COLOR_TEXT_BROWN;
                $colors['background'] = self::COLOR_STATUS_GOLD;
            } else {
                $colors['text'] = self::COLOR_TEXT_GREEN;
                $colors['background'] = self::COLOR_STATUS_GREEN;
            }
        } else {
            $colors['text'] = self::COLOR_TEXT_BLUE;
            $colors['background'] = self::COLOR_STATUS_BLUE;
        }

        return $colors;
    }

    public function getParticipantsIds() {
        $participants = array();

        foreach ($this->getParticipants() as $participant) {
            $participants[] = $participant->getId();
        }

        sort($participants);

        return $participants;
    }

    public function pushToPlanifiable() {
        if(!$this->getPlanifiable()) {
            return;
        }

        if(count(array_diff($this->getParticipantsIds(), $this->getPlanifiable()->getTechniciensIds())) > 0 || count(array_diff($this->getPlanifiable()->getTechniciensIds(), $this->getParticipantsIds())) > 0) {
            $this->getPlanifiable()->removeAllTechniciens();

            foreach($this->getParticipants() as $participant) {
                $this->getPlanifiable()->addTechnicien($participant);
            }
        }

        $this->getPlanifiable()->setDateDebut($this->getDateDebut());
        $this->getPlanifiable()->setDateFin($this->getDateFin());

        if ($this->getPlanifiable()->getTypePlanifiable() === Devis::DOCUMENT_TYPE) {
            $this->getPlanifiable()->setDatePrevision($this->getDateDebut());
            $this->getPlanifiable()->setCommentaireInterne($this->getDescription());

        } elseif ($this->getPlanifiable()->getTypePlanifiable() === Passage::DOCUMENT_TYPE) {
            $this->getPlanifiable()->setCommentaire($this->getDescription());
        }
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

    public function getDuree() {

        return $this->getDateFin()->diff($this->getDateDebut())->format('%Hh%I');
    }

    /** @MongoDB\PreFlush */
    public function preFlush() {
        $this->pushToPlanifiable();
    }

    /** @MongoDB\PreRemove */
    public function preRemove()
    {
        if(!$this->getPlanifiable()) {
            return;
        }
        $this->getPlanifiable()->deplanifier();
        $this->removePassage();
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
     * Set rendezVousConfirme
     *
     * @param boolean $rendezVousConfirme
     * @return self
     */
    public function setRendezVousConfirme($rendezVousConfirme) {
        $this->rendezVousConfirme = $rendezVousConfirme;
        return $this;
    }

    /**
     * Get rendezVousConfirme
     *
     * @return boolean $rendezVousConfirme
     */
    public function getRendezVousConfirme() {
        return $this->rendezVousConfirme;
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

    public function removePassage()
    {
        $this->passage = null;
        unset($this->passage);

        return $this;
    }

    public function getPlanifiable()
    {
        if($this->getPassage()){
          return $this->getPassage();
        }
        if($this->getDevis()){
          return $this->getDevis();
        }
        return null;
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

    public function setDevis(Devis $devis)
    {
        $this->devis = $devis;
    }

    /**
     * Get passage
     *
     * @return AppBundle\Document\Devis $devis
     */
    public function getDevis()
    {
        return $this->devis;
    }

    /**
     * Get prerequis
     * @return string $prerequis
     */
    public function getPrerequis()
    {
        return $this->prerequis;
    }

    /**
     * Set prerequis
     * @param string $prerequis
     */
    public function setPrerequis($prerequis)
    {
        $this->prerequis = $prerequis;
    }

    public function getInformations() {
        $informations = null;

        if($this->getPlanifiable()) {
            $informations .= $this->getPlanifiable()->getEtablissement()->getCommentaire();
        }

        $informations .= "\n\n".str_replace($informations, "", $this->description);

        $informations = trim($informations);

        return $informations;
    }
}
