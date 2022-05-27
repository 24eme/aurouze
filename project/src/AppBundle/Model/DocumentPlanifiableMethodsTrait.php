<?php

namespace AppBundle\Model;

use AppBundle\Document\Etablissement;
use AppBundle\Document\Compte;
use AppBundle\Document\RendezVous;
use AppBundle\Document\Passage;
use AppBundle\Document\Devis;
use AppBundle\Document\EtablissementInfos;
use AppBundle\Manager\PassageManager;
use Doctrine\Common\Collections\ArrayCollection;

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
        $this->getEtablissementInfos()->pull($this->getEtablissement());
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
     * Set etablissementInfos
     *
     * @param EtablissementInfos $etablissementInfos
     * @return self
     */
    public function setEtablissementInfos(EtablissementInfos $etablissementInfos) {
        $this->etablissementInfos = $etablissementInfos;
        return $this;
    }

    /**
     * Get etablissementInfos
     *
     * @return EtablissementInfos $etablissementInfos
     */
    public function getEtablissementInfos() {
        return $this->etablissementInfos;
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

    public function removeAllTechniciens() {
        $this->techniciens = new ArrayCollection();
        if (property_exists($this, 'imprime')) {
            $this->setImprime(false);
        }
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
     * Set commentaireInterne
     *
     * @param string $commentaireInterne
     * @return self
     */
    public function setCommentaireInterne($commentaireInterne)
    {
        $this->commentaireInterne = $commentaireInterne;
        return $this;
    }

    /**
     * Get commentaireInterne
     *
     * @return string $commentaireInterne
     */
    public function getCommentaireInterne()
    {
        return $this->commentaireInterne;
    }

    /**
     * Set dateAcceptation
     *
     * @param date $dateAcceptation
     * @return $this
     */
    public function setDateAcceptation($dateAcceptation)
    {
        $this->dateAcceptation = $dateAcceptation;
        return $this;
    }

    /**
     * Get dateAcceptation
     *
     * @return date $dateAcceptation
     */
    public function getDateAcceptation()
    {
        return $this->dateAcceptation;
    }



    /** @MongoDB\PrePersist */
    public function prePersist() {
        $this->updateStatut();
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

    public function isSaisieTechnicien(){
      return $this->saisieTechnicien;
    }

    public function isTransmis(){
      return (boolval($this->signatureBase64) && boolval($this->duree)) || (boolval($this->emailTransmission)&& boolval($this->duree));
    }

    public function isValideTechnicien()
    {
        return $this->getSignatureBase64() || $this->getNomTransmission() || $this->getEmailTransmission();
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

    public function isAAccepter() {
        return $this->statut == PassageManager::STATUT_A_ACCEPTER;
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

    /*
     * Get statut
     *
     * @return string $statut
     */
    public function getStatut() {
        return $this->statut;
    }

    public function getStatutLibelle() {
        return PassageManager::$statutsLibelles[$this->getStatut()];
    }

    public function updateStatut() {
        if ($this->isAnnule()) {
            return;
        }

        if ($this->getDocumentType() === Devis::DOCUMENT_TYPE && ! $this->getDateAcceptation()) {
            $this->setStatut(PassageManager::STATUT_A_ACCEPTER);
            return;
        }

        if (! $this->getDateFin() || ! $this->getDateDebut()) {
            $this->setStatut(PassageManager::STATUT_A_PLANIFIER);
            return;
        }

        if ($this->getDateDebut() && $this->getDateFin() && ! $this->getDateRealise()) {
            $this->setStatut(PassageManager::STATUT_PLANIFIE);
            return;
        }

        if ($this->getDateRealise()) {
            $this->setStatut(PassageManager::STATUT_REALISE);
            return;
        }
    }

    /*
    * Get description
    *
    * @return string $signatureBase64
    */
   public function getSignatureBase64()
   {
       return $this->signatureBase64;
   }

   /*
   * Set signatureBase64
   *
   * @return string $setSignatureBase64
   */
  public function setSignatureBase64($signatureBase64)
  {
      $this->signatureBase64 = $signatureBase64;
      return $this;
  }

    /**
     * Set pdfNonEnvoye
     *
     * @inheritDoc
     */
    public function setPdfNonEnvoye($pdfNonEnvoye)
    {
        $this->pdfNonEnvoye = $pdfNonEnvoye;
        return $this;
    }

    /**
     * Get pdfNonEnvoye
     *
     * @inheritDoc
     */
    public function getPdfNonEnvoye()
    {
        return $this->pdfNonEnvoye;
    }

    public function isPdfNonEnvoye()
    {
        return $this->pdfNonEnvoye;
    }

    public function getColors()
    {
        $rdv = $this->rendezVous;

        if (! $rdv || ! $this->isSaisieTechnicien()) {
            return '';
        }

        $colors = $rdv->calculateTilesColors();

        return "background: " . $colors['background'] . "; color: " . $colors['text'] . ";";
    }

    public function getDocumentType() {
        return self::DOCUMENT_TYPE;
    }

    public function getDateForPlanif() {
        $today = new \DateTime();
        if ($this->datePrevision && $this->datePrevision->format('Ymd') < $today->format('Ymd')) {
            return $today;
        }
        return $this->datePrevision;
    }
 }
