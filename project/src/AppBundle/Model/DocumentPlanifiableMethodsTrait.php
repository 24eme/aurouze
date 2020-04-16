<?php

namespace AppBundle\Model;

use AppBundle\Document\Etablissement;
use AppBundle\Document\Compte;
use AppBundle\Document\RendezVous;
use AppBundle\Manager\PassageManager;

trait DocumentPlanifiableMethodsTrait
{
    /**
     * Set etablissement
     *
     * @param Etablissement $etablissement
     * @return $this
     */
    public function setEtablissement(Etablissement $etablissement)
    {
        $this->etablissement = $etablissement;
        return $this;
    }

    /**
     * Return Etablissement
     *
     * @return Etablissement
     */
    public function getEtablissement()
    {
        return $this->etablissement;
    }

    /**
     * Add technicien
     *
     * @param Compte $technicien
     * @return $this
     */
    public function addTechnicien(Compte $technicien)
    {
        if (! $this->techniciens->contains($technicien)) {
            $this->techniciens[] = $technicien;
        }
        return $this;
    }

    /**
     * Get technicien
     *
     * @return Collection $techniciens
     */
    public function getTechniciens()
    {
        return $this->techniciens;
    }

    public function getTechniciensIds() {
        $techniciens = array();

        foreach ($this->getTechniciens() as $technicien) {
            $techniciens[] = $technicien->getId();
        }

        sort($techniciens);

        return $techniciens;
    }

    /**
     * Remove technicien
     *
     * @param Compte $technicien
     */
    public function removeTechnicien(Compte $technicien)
    {
        $this->techniciens->removeElement($technicien);
    }

    /**
     * Set dateDebut
     *
     * @param date $dateDebut
     * @return $this
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
     * @return $this
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
     * Set datePrevision
     *
     * @param date $datePrevision
     * @return $this
     */
    public function setDatePrevision($datePrevision)
    {
        $this->datePrevision = $datePrevision;
        return $this;
    }

    /**
     * Get datePrevision
     *
     * @return date $datePrevision
     */
    public function getDatePrevision()
    {
        return $this->datePrevision;
    }

    /**
     * Set dateRealise
     *
     * @param date $dateRealise
     * @return self
     */
    public function setDateRealise($dateRealise) {
        $this->dateRealise = $dateRealise;
        return $this;
    }

    /**
     * Get dateRealise
     *
     * @return date $dateRealise
     */
    public function getDateRealise() {
        return $this->dateRealise;
    }

    /**
     * Set rendezvous
     *
     * @param RendezVous $rendezvous
     * @return $this
     */
    public function setRendezvous(RendezVous $rendezvous)
    {
        $this->rendezVous = $rendezvous;
        return $this;
    }

    /**
     * Get rendezvous
     *
     * @return RendezVous $rendezvous
     */
    public function getRendezvous()
    {
        return $this->rendezVous;
    }

    public function removeRendezVous()
    {
        $this->rendezVous = null;
        return $this;
    }

    /**
     * Set emailTransmission
     *
     * @param string $emailTransmission
     * @return $this
     */
    public function setEmailTransmission($emailTransmission)
    {
        $this->emailTransmission = $emailTransmission;
        return $this;
    }

    /**
     * Get emailTransmission
     *
     * @return string $emailTransmission
     */
    public function getEmailTransmission()
    {
        return $this->emailTransmission;
    }

    /**
     * Set secondEmailTransmission
     *
     * @param string $secondEmailTransmission
     * @return $this
     */
    public function setSecondEmailTransmission($secondEmailTransmission)
    {
        $this->secondEmailTransmission = $secondEmailTransmission;
        return $this;
    }

    /**
     * Get secondEmailTransmission
     *
     * @return string $secondEmailTransmission
     */
    public function getSecondEmailTransmission()
    {
        return $this->secondEmailTransmission;
    }

    /**
     * Set nomTransmission
     *
     * @param string $nomTransmission
     * @return $this
     */
    public function setNomTransmission($nomTransmission)
    {
        $this->nomTransmission = $nomTransmission;
        return $this;
    }

    /**
     * Get nomTransmission
     *
     * @return string $nomTransmission
     */
    public function getNomTransmission()
    {
        return $this->nomTransmission;
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

    public function deplanifier() {
        $this->setDateDebut($this->getDatePrevision());
        $this->setDateFin(null);
        if($this->isRealise()) {
            $this->setDateRealise(null);
        }
        $this->removeRendezVous();

        $this->updateStatut();
    }

    public function getDescriptionTransformed() {
        return str_replace('\n', "\n", $this->description);
    }

    public function isRealise() {
        return $this->statut == PassageManager::STATUT_REALISE;
    }

    public function isPlanifie() {
        return $this->statut == PassageManager::STATUT_PLANIFIE;
    }

    public function isAPlanifie() {
        return $this->statut == PassageManager::STATUT_A_PLANIFIER;
    }

    public function isAnnule() {
        return $this->statut == PassageManager::STATUT_ANNULE;
    }

    /**
     * Set statut
     *
     * @param string $statut
     * @return self
     */
    public function setStatut($statut) {
        $this->statut = $statut;
        return $this;
    }

    /**
     * Get statut
     *
     * @return string $statut
     */
    public function getStatut() {
        return $this->statut;
    }

    public function updateStatut() {
        if (!$this->isAnnule()) {
            if ($this->getDatePrevision() && !boolval($this->getDateFin()) && !boolval($this->getDateDebut()) && !boolval($this->getDateRealise())) {
                $this->setStatut(PassageManager::STATUT_A_PLANIFIER);
                return;
            }
            if (boolval($this->getDateDebut()) && !boolval($this->getDateFin()) && !boolval($this->getDateRealise())) {
                $this->setStatut(PassageManager::STATUT_A_PLANIFIER);
                return;
            }
            if (boolval($this->getDateDebut()) && boolval($this->getDateFin()) && !boolval($this->getDateRealise())) {
                $this->setStatut(PassageManager::STATUT_PLANIFIE);
                return;
            }
            if (boolval($this->getDateRealise())) {
                $this->setStatut(PassageManager::STATUT_REALISE);
            }
        }
    }
}
