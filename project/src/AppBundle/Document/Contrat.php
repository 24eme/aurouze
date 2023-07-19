<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Document\Etablissement;
use AppBundle\Document\Compte;
use AppBundle\Document\Prestation;
use AppBundle\Document\Societe;
use AppBundle\Document\ContratPassages;
use AppBundle\Document\Mouvement;
use AppBundle\Model\DocumentFacturableInterface;
use AppBundle\Model\DocumentSocieteInterface;
use AppBundle\Manager\ConfigurationManager;
use AppBundle\Manager\ContratManager;

/**
 * @MongoDB\Document(repositoryClass="AppBundle\Repository\ContratRepository") @HasLifecycleCallbacks
 *
 */
class Contrat implements DocumentSocieteInterface, DocumentFacturableInterface {

    /**
     * @MongoDB\Id(strategy="CUSTOM", type="string", options={"class"="AppBundle\Document\Id\ContratGenerator"})
     */
    protected $id;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Etablissement", inversedBy="contrats", simple=true)
     */
    protected $etablissements;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Compte", simple=true)
     */
    protected $commercial;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Compte", simple=true)
     */
    protected $technicien;

    /**
     * @MongoDB\EmbedMany(targetDocument="ContratPassages", strategy="set")
     */
    protected $contratPassages;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Societe", simple=true)
     */
    protected $societe;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Societe", simple=true)
     */
    protected $commanditaire;

    /**
     * @MongoDB\ReferenceOne()
     */
    protected $devisInterlocuteur;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiant;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $numeroArchive;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $multiTechnicien;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $reconduit;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $typeContrat;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $typeContratOriginal;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $nomenclature;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $description;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $commentaire;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $markdown;

    /**
     * @MongoDB\EmbedMany(targetDocument="Prestation")
     */
    protected $prestations;

    /**
     * @MongoDB\EmbedMany(targetDocument="Produit")
     */
    protected $produits;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateCreation;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateCreationAuto;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateDebut;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateFin;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateAcceptation;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateResiliation;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateReconduction;


    /**
     * @MongoDB\Field(type="int")
     */
    protected $duree;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $dureeGarantie;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $nbPassages;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $dureePassage;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $nbFactures;

    /**
     * @MongoDB\Field(type="float")
     */
    protected $prixHt;

    /**
     * @MongoDB\EmbedMany(targetDocument="Mouvement")
     */
    protected $mouvements;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $statut;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiantReprise;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $tvaReduite;

    /**
     * @MongoDB\Field(type="collection")
     */
    protected $moyens;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $conditionsParticulieres;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $referenceClient;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $factureDestinataire;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $frequencePaiement;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $auditPassage;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $zone;


    public function __construct() {
        $this->etablissements = new ArrayCollection();
        $this->prestations = new ArrayCollection();
        $this->produits = new ArrayCollection();
        $this->mouvements = new ArrayCollection();
        $this->contratPassages = array();
    }

    /**
     * Set commercial
     *
     * @param AppBundle\Document\Compte $commercial
     * @return self
     */
    public function setCommercial(\AppBundle\Document\Compte $commercial) {
        $this->commercial = $commercial;
        return $this;
    }

    /**
     * Get commercial
     *
     * @return AppBundle\Document\Compte $commercial
     */
    public function getCommercial() {
        return $this->commercial;
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
     * Set typeContrat
     *
     * @param string $typeContrat
     * @return self
     */
    public function setTypeContrat($typeContrat) {
        $this->typeContrat = $typeContrat;
        if ($typeContrat == ContratManager::TYPE_CONTRAT_ANNULE) {
            $this->setStatut(ContratManager::STATUT_FINI);
        }
        return $this;
    }

    /**
     * Get typeContrat
     *
     * @return string $typeContrat
     */
    public function getTypeContrat() {
        return $this->typeContrat;
    }

    /**
     * Set typeContratOriginal
     *
     * @param string $typeContratOriginal
     * @return self
     */
    public function setTypeContratOriginal($typeContrat) {
        $this->typeContratOriginal = $typeContrat;

        return $this;
    }

    /**
     * Get typeContrat
     *
     * @return string $typeContratOriginal
     */
    public function getTypeContratOriginal() {

        return $this->typeContratOriginal;
    }

    /**
     * Set nomenclature
     *
     * @param string $nomenclature
     * @return self
     */
    public function setNomenclature($nomenclature) {
        $this->nomenclature = $nomenclature;
        return $this;
    }

    /**
     * Get nomenclature
     *
     * @return string $nomenclature
     */
    public function getNomenclature() {
        return $this->nomenclature;
    }



    /**
     * Get nomenclature
     *
     * @return string $nomenclature
     */
    public function getHtmlNomenclature() {
    	return preg_replace('/\*\*(.+)\*\*/', '<strong>$1</strong>', $this->nomenclature);
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

    /**
     * Get prestations
     *
     * @return \Doctrine\Common\Collections\Collection $prestations
     */
    public function getPrestations() {
        return $this->prestations;
    }

    public function hasPrestation($part_of_name){
      foreach ($this->getPrestations() as $prestation) {
        if(preg_match("/$part_of_name/",$prestation->getNomToString())){
          return true;
        }
      }
      return false;
    }

    public function getUniquePrestations() {
        $prestations = array();

        foreach ($this->getPrestations() as $prestation) {
            $prestations[$prestation->getIdentifiant()] = $prestation->getNom();
        }

        return $prestations;
    }

    public function setUniquePrestations($prestationsIdentifiant) {
        $this->prestations = new ArrayCollection();

        foreach ($prestationsIdentifiant as $identifiant) {
            $prestation = new Prestation();
            $prestation->setIdentifiant($identifiant);
            $prestation->setNbPassages(1);
            $this->addPrestation($prestation);
        }

        return $this;
    }

    /**
     * Add produit
     *
     * @param AppBundle\Document\Produit $prestation
     */
    public function addProduit(\AppBundle\Document\Produit $produit) {
        foreach ($this->getProduits() as $prod) {
            if ($prod == $produit) {
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
     * @return \Doctrine\Common\Collections\Produit $produits
     */
    public function getProduits() {
        return $this->produits;
    }

    public function getProduit($identifiant){
      foreach ($this->produits as $produit) {
        if($produit->getIdentifiant() == $identifiant){
          return $produit;
        }
      }
      return null;
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

    /**
     * Get dateCreation
     *
     * @return date $dateCreation
     */
    public function getDateCreation() {
        return $this->dateCreation;
    }

    /**
     * Set dateCreationAuto
     *
     * @param date $dateCreationAuto
     * @return self
     */
    public function setDateCreationAuto($dateCreationAuto) {
        $this->dateCreationAuto = $dateCreationAuto;
        return $this;
    }

    /**
     * Get dateCreationAuto
     *
     * @return date $dateCreationAuto
     */
    public function getDateCreationAuto() {
        return $this->dateCreationAuto;
    }

    /**
     * Set dateDebut
     *
     * @param date $dateDebut
     * @return self
     */
    public function setDateDebut($dateDebut) {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    /**
     * Get dateDebut
     *
     * @return date $dateDebut
     */
    public function getDateDebut() {
        return $this->dateDebut;
    }

    /**
     * Set dateFin
     *
     * @param date $dateFin
     * @return self
     */
    public function setDateFin($dateFin) {
        $this->dateFin = $dateFin;
        return $this;
    }

    /**
     * Get dateFin
     *
     * @return date $dateFin
     */
    public function getDateFin() {
        return $this->dateFin;
    }

    /**
     * Set dateAcceptation
     *
     * @param date $dateAcceptation
     * @return self
     */
    public function setDateAcceptation($dateAcceptation) {
        $this->dateAcceptation = $dateAcceptation;
        return $this;
    }

    /**
     * Get dateAcceptation
     *
     * @return date $dateAcceptation
     */
    public function getDateAcceptation() {
        return $this->dateAcceptation;
    }

    /**
     * Set duree
     *
     * @param int $duree
     * @return self
     */
    public function setDuree($duree) {
        $this->duree = $duree;
        return $this;
    }

    /**
     * Get duree
     *
     * @return int $duree
     */
    public function getDuree() {
        return $this->duree;
    }

    /**
     * Set dureeGarantie
     *
     * @param int $dureeGarantie
     * @return self
     */
    public function setDureeGarantie($dureeGarantie) {
        $this->dureeGarantie = $dureeGarantie;
        return $this;
    }

    /**
     * Get dureeGarantie
     *
     * @return int $dureeGarantie
     */
    public function getDureeGarantie() {
        return $this->dureeGarantie;
    }

    /**
     * Set nbPassages
     *
     * @param int $nbPassages
     * @return self
     */
    public function setNbPassages($nbPassages) {
        $this->nbPassages = $nbPassages;
        return $this;
    }

    /**
     * Get nbPassages
     *
     * @return int $nbPassages
     */
    public function getNbPassages() {
        if (!$this->nbPassages) {
            $nbPassagesEtb = array();
            foreach ($this->getContratPassages() as $contratPassage) {
                $nbPassagesEtb[$contratPassage->getEtablissement()->getId()] = 0;
                foreach ($contratPassage->getPassages() as $p) {
                    if ($p->isSousContrat()) {
                        $nbPassagesEtb[$contratPassage->getEtablissement()->getId()] = $nbPassagesEtb[$contratPassage->getEtablissement()->getId()] + 1;
                    }
                }
            }
            if ($nbPassagesEtb && count($nbPassagesEtb)) {
                $this->setNbPassages(max($nbPassagesEtb));
            } else {
                $this->setNbPassages(0);
            }
        }

        return $this->nbPassages;
    }

    /**
     * Set dureePassage
     *
     * @param int $dureePassage
     * @return self
     */
    public function setDureePassage($dureePassage) {
        $this->dureePassage = $dureePassage;
        return $this;
    }

    /**
     * Get dureePassage
     *
     * @return int $dureePassage
     */
    public function getDureePassage() {
        return $this->dureePassage;
    }

    public function getDureePassageFormat() {
        $minute = $this->getDureePassage();
        return $this->getDureeFormatee($minute);
    }

    /**
     * Set nbFactures
     *
     * @param int $nbFactures
     * @return self
     */
    public function setNbFactures($nbFactures) {
        $this->nbFactures = $nbFactures;
        return $this;
    }

    /**
     * Get nbFactures
     *
     * @return int $nbFactures
     */
    public function getNbFactures() {
        return $this->nbFactures;
    }

    /**
     * Set prixHt
     *
     * @param float $prixHt
     * @return self
     */
    public function setPrixHt($prixHt) {
        $this->prixHt = $prixHt;
        return $this;
    }

    /**
     * Get prixHt
     *
     * @return float $prixHt
     */
    public function getPrixHt() {
        return $this->prixHt;
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

    /**
     * Set auditPassage
     *
     * @param int $auditPassage
     * @return self
     */
    public function setAuditPassage($auditPassage) {
        $this->auditPassage = $auditPassage;
        return $this;
    }

    /**
     * Get auditPassage
     *
     * @return int $auditPassage
     */
    public function getAuditPassage() {
        return $this->auditPassage;
    }

    /**
     * Get zone
     *
     * @return string $zone
     */
    public function getZone(){
      return $this->zone;
    }


    /**
     * Set zone
     *
     * @param string $commercialSeineEtMarne
     * @return self
     */
    public function setZone($commercialSeineEtMarne){
      if($this->getCommercial()->getNom() == $commercialSeineEtMarne){
        $this->zone = ContratManager::ZONE_SEINE_ET_MARNE;
      }
      else{
        $this->zone = ContratManager::ZONE_PARIS;
      }

      foreach($this->getContratPassages() as $cp){
        foreach($cp->getPassages() as $passage){
          $passage->setZone($this->getZone());
        }
      }

      return $this;
    }

    public function updateObject() {
            $max = 0;
            foreach ($this->getPrestations() as $prestation) {
                if ($prestation->getNbPassages() > $max) {
                    $max = $prestation->getNbPassages();
                }
            }
            $this->setNbPassages($max);
    }

    public function updatePassages() {

    }

    public function updatePrestations($dm) {
        $cm = new ConfigurationManager($dm);
        $configuration = $cm->getRepository()->findOneById(Configuration::PREFIX);
        $prestationArray = $configuration->getPrestationsArray();
        foreach ($this->getPrestations() as $prestation) {
            $prestationNom = $prestationArray[$prestation->getIdentifiant()];
            $prestation->setNom($prestationNom);
        }
    }

    public function updateProduits($dm) {
        $cm = new ConfigurationManager($dm);
        $configuration = $cm->getRepository()->findOneById(Configuration::PREFIX);
        $produitsArray = $configuration->getProduitsArray();

        foreach ($this->getProduits() as $produit) {
          if($produit->getIdentifiant()){
            $produitConf = $produitsArray[$produit->getIdentifiant()];
            $produit->setNom($produitConf->getNom());
            $produit->setPrixHt($produitConf->getPrixHt());
            $produit->setPrixPrestation($produitConf->getPrixPrestation());
          }
        }
    }

    public function getHumanDureePassage() {
        $duree = $this->getDureePassage();
        return $this->getDureeFormatee($duree);
    }

    public function getPrixMouvements() {
        $prix = 0;
        foreach ($this->getMouvements() as $mouvement) {
            $prix = $prix + $mouvement->getPrixUnitaire();
        }

        return $prix;
    }

    public function getPrixFactures() {
        $prix = 0;
        foreach ($this->getMouvements() as $mouvement) {
          if($mouvement->isFacture()){
            $prix = $prix + $mouvement->getPrixUnitaire();
            }
        }
        return $prix;
    }

    public function getPrixRestant() {
        return $this->getPrixHt() - $this->getPrixMouvements();
    }

    public function getNbPassagesPrevu() {
        return $this->getNbPassages();
    }

    public function getNbFacturesRestantes() {
    $nbFacturesGenerees = 0;
      foreach ($this->getMouvements() as $mouvement) {
        if($mouvement->getPrixUnitaire() > 0){
          $nbFacturesGenerees++;
        }else{
          $nbFacturesGenerees--;
        }
      }
      return $this->getNbFactures() - $nbFacturesGenerees;
    }

    public function generateMouvement($origineDocumentGeneration = null) {
        if ($this->getPrixRestant() <= 0 || $this->getNbFacturesRestantes() <= 0) {
            return null;
        }

        $mouvement = $this->buildMouvement($origineDocumentGeneration);

        $this->addMouvement($mouvement);

        return $mouvement;
    }

    public function buildMouvement($origineDocumentGeneration = null) {
        if ($this->getPrixRestant() <= 0 || $this->getNbFacturesRestantes() <= 0) {
            return null;
        }

        $mouvement = new Mouvement();
        $mouvement->setIdentifiant(uniqid());
        $mouvement->setPrixUnitaire(round($this->getPrixRestant() / $this->getNbFacturesRestantes(), 2));
        $mouvement->setQuantite(1);
        $mouvement->setTauxTaxe($this->getTva());
        $mouvement->setFacturable(true);
        $mouvement->setFacture(false);
        $mouvement->setSociete($this->getSociete());
        $mouvement->setLibelle($this->getLibelleMouvement());

        $mouvement->setDocument($this);
        if ($origineDocumentGeneration) {
            $mouvement->setOrigineDocumentGeneration($origineDocumentGeneration);
        }

        return $mouvement;
    }

    public function getLibelleMouvement() {
        $nbFactures = $this->getNbFactures();
        $nbMvts = count($this->getMouvements()) + 1;
        if ($nbMvts > $nbFactures) {
            $nbFactures = $nbMvts;
        }
        return sprintf("Facture %s/%s - Proposition n° %s du %s au %s", count($this->getMouvements()) + 1, $nbFactures, $this->getNumeroArchive(), $this->getDateDebut()->format('m/Y'), $this->getDateFin()->format('m/Y'));
    }

    public function restaureMouvements($documentGenere = null) {
      foreach ($this->getMouvements() as $mouvement) {
        if ($documentGenere){
          $mvtToRestaure = null;
          foreach ($documentGenere->getLignes() as $ligne) {
            if($ligne->getOrigineMouvement() == $mouvement->getIdentifiant()){
              $mvtToRestaure = $mouvement;
              break;
            }
          }
          if($mvtToRestaure){
            $mvtToRestaure->setFacturable(true);
            $mvtToRestaure->setFacture(false);

          }
        }else{
          $mouvement->setFacturable(true);
          $mouvement->setFacture(false);
        }
      }
    }

    public function resetFacturableMouvement($identifiant)
    {
    	foreach ($this->getMouvements() as $mouvement) {
    		if ($identifiant == $mouvement->getIdentifiant()) {
    			$this->removeMouvement($mouvement);
    		}
    	}
    }


    public function hasMouvements() {
        return boolval(count($this->getMouvements()));
    }

    public function getPrevisionnel($dateDebut = null) {
        if (!$dateDebut) {
            $dateDebut = new \DateTime();
        }

        $dureeContratMois = $this->getDuree();

        $maxNbPrestations = 0;
        $typePrestationPrincipal = "";
        foreach ($this->getPrestations() as $prestation) {
            if ($prestation->getNbPassages() > $maxNbPrestations) {
                $maxNbPrestations = $prestation->getNbPassages();
                $typePrestationPrincipal = $prestation;
            }
        }
        $passagesDatesArray = array();
        if (!count($this->getPrestations())) {
            return $passagesDatesArray;
        }
        if (!$maxNbPrestations) {
            return $passagesDatesArray;
        }
        $monthInterval = (floatval($dureeContratMois) / floatval($maxNbPrestations));
        $nb_month = intval($monthInterval);
        $dateLastPassage = clone $dateDebut;
        $passagesDatesArray[$dateLastPassage->format('Y-m-d')] = new \stdClass();
        $passagesDatesArray[$dateLastPassage->format('Y-m-d')]->prestations = array();

        foreach ($this->getPrestations() as $prestation) {
            if ($prestation->getNbPassages() > 0) {
                $passagesDatesArray[$dateLastPassage->format('Y-m-d')]->prestations[] = $prestation;
                $passagesDatesArray[$dateLastPassage->format('Y-m-d')]->mouvement_declenchable = boolval(floatval($this->getNbFactures()));

            }
        }

        for ($index = 1; $index < $maxNbPrestations; $index++) {
            $monthDate = clone $dateLastPassage;
            $nextMonth = $monthDate->modify("+" . $nb_month . " month");
            $nb_days = intval(($monthInterval - $nb_month) * cal_days_in_month(CAL_GREGORIAN, $nextMonth->format('m'), $nextMonth->format('Y')));

            $dateLastPassage = $dateLastPassage->modify("+" . $nb_month . " month")->modify("+" . $nb_days . " day");
            $passagesDatesArray[$dateLastPassage->format('Y-m-d')] = new \stdClass();
            $passagesDatesArray[$dateLastPassage->format('Y-m-d')]->prestations = array();
            $passagesDatesArray[$dateLastPassage->format('Y-m-d')]->prestations[] = $typePrestationPrincipal;
        }

        foreach ($this->getPrestations() as $prestation) {
            if (($prestation->getIdentifiant() != $typePrestationPrincipal->getIdentifiant()) && $prestation->getNbPassages() > 1) {
                $nbPassagesPrestationRestant = $prestation->getNbPassages();
                $nbPassagesRestant = count($passagesDatesArray);
                $occurPassage = (floatval($nbPassagesRestant) / floatval($nbPassagesPrestationRestant));
                $compteurPassage = $occurPassage;
                $cpt = 0;
                foreach ($passagesDatesArray as $date => $passage) {
                    if ($cpt < 1) {
                        $cpt++;
                        continue;
                    }
                    if ($cpt >= $compteurPassage) {
                        $passagesDatesArray[$date]->prestations[] = $prestation;
                        $compteurPassage+=$occurPassage;
                    }
                    $cpt++;
                }
            }
        }
        $facturationInterval = 0;

        if(floatval($this->getNbFactures())){
          $facturationInterval = (floatval($maxNbPrestations) / floatval($this->getNbFactures()));
        }

        $compteurFacturation = $facturationInterval;
        $cpt = 0;
        $nbMouvementsDeclenchables = 0;
        foreach ($passagesDatesArray as $date => $passage) {
            if ($cpt < 1) {
                $passagesDatesArray[$date]->mouvement_declenchable = boolval($this->getNbFactures() && $nbMouvementsDeclenchables < $this->getNbFactures());
                $compteurFacturation--;
                if($passagesDatesArray[$date]->mouvement_declenchable) {
                    $nbMouvementsDeclenchables++;
                }
                $cpt++;
                continue;
            }
            if ($cpt > $compteurFacturation) {
                $passagesDatesArray[$date]->mouvement_declenchable = boolval($this->getNbFactures() && $nbMouvementsDeclenchables < $this->getNbFactures() && (floatval($this->getNbFactures()) > 1));
                $compteurFacturation+=$facturationInterval;
            } else {
                $passagesDatesArray[$date]->mouvement_declenchable = 0;
            }
            $cpt++;
            if($passagesDatesArray[$date]->mouvement_declenchable) {
                $nbMouvementsDeclenchables++;
            }
        }
        $cpt = 0;
        foreach ($passagesDatesArray as $date => $passage) {
        	$passagesDatesArray[$date]->audit = 0;
        	if ($cpt == ($this->getAuditPassage()-1)) {
        		$passagesDatesArray[$date]->audit = ($this->getCommercial()->getIdentite())? $this->getCommercial()->getIdentite() : 'Commercial';
        	}
        	$cpt++;
        }
        krsort($passagesDatesArray);
        return $passagesDatesArray;
    }

    public function getTypeContratLibelle() {
        if (!$this->getTypeContrat()) {
            return "";
        }
        return ContratManager::$types_contrat_libelles[$this->getTypeContrat()];
    }

    public function getTypeContratOriginalLibelle() {
        if (!$this->getTypeContratOriginal()) {
            return "";
        }
        return ContratManager::$types_contrat_libelles[$this->getTypeContratOriginal()];
    }

    public function getStatutLibelle() {
        $today = new \DateTime();
        if($this->isEnCours() && $this->getDateDebut() && ($today->format("Ymd") < $this->getDateDebut()->format("Ymd"))){
          return "A venir";
        }elseif($this->isFini() && $this->getDateFin() && ($today->format("Ymd") < $this->getDateFin()->format("Ymd"))){
          return "En cours (réalisé)";
        }
        return ContratManager::$statuts_libelles[$this->getStatut()];
    }

    public function getStatutLibelleLong() {
        $today = new \DateTime();
        if($this->isEnCours() && $this->getDateDebut() && ($today->format("Ymd") < $this->getDateDebut()->format("Ymd"))) {

            return "à venir";
        } elseif($this->isFini() && $this->getDateFin() && ($today->format("Ymd") < $this->getDateFin()->format("Ymd"))) {

            return "en cours réalisé";
        }

        return ContratManager::$statuts_libelles_long[$this->getStatut()];
    }

    public function getStatutCouleur() {
        if ($this->isAnnule()) {
            return ContratManager::$statuts_couleurs[$this->getTypeContrat()];
        }
      $today = new \DateTime();
      if($this->isEnCours() && $this->getDateDebut() && ($today->format("Ymd") < $this->getDateDebut()->format("Ymd"))){
        return "default";
      }elseif($this->isFini() && $this->getDateFin() && ($today->format("Ymd") < $this->getDateFin()->format("Ymd"))){
        return "info";
      }

        return ContratManager::$statuts_couleurs[$this->getStatut()];
    }

    public function getPassagesEtablissementNode(Etablissement $etablissement) {
        $contratPassages = $this->getContratPassages();
        if (!isset($contratPassages[$etablissement->getId()])) {
            return null;
        }

        return $contratPassages[$etablissement->getId()];
    }

    public function hasEtablissementNode(Etablissement $etablissement) {
        $etablissements = array();
        foreach ($this->getEtablissements() as $etab) {
        	$etablissements[] = $etab->getId();
        }
        return in_array($etablissement->getId(), $etablissements);
    }

    public static function cmpContrat($a, $b) {
        $statutsPositions = ContratManager::$statuts_positions;
        $pa = ($a->getStatut()) ? $statutsPositions[$a->getStatut()] : 99;
        $pb = ($b->getStatut()) ? $statutsPositions[$b->getStatut()] : 99;
        if ($pa == $pb) {
            $paDate = ($a->getDateDebut()) ? $a->getDateDebut() : $a->getDateCreation();
            $pbDate = ($b->getDateDebut()) ? $b->getDateDebut() : $b->getDateCreation();
            if(!$paDate) {
                return +1;
            }
            if(!$pbDate) {
                return -1;
            }
            if ($paDate->format('Ymd') == $pbDate->format('Ymd')) {
                return 0;
            } else {
                return ($paDate->format('Ymd') < $pbDate->format('Ymd')) ? +1 : -1;
            }
        }
        return ($pa > $pb) ? +1 : -1;
    }

    public function getPassages(Etablissement $etablissement) {
        if (!isset($this->contratPassages[$etablissement->getId()])) {
            return array();
        }

        return $this->contratPassages[$etablissement->getId()]->getPassagesSorted();
    }

    public function getUniquePassage() {
        if ($this->getNbPassages() != 1 || count($this->getEtablissements()) > 1) {

            throw new \Exception("Il y a plusieurs passage pour ce contrat");
        }
        try{
          foreach ($this->getContratPassages() as $contratPassage) {
              foreach ($contratPassage->getPassagesSorted() as $passage) {
                return $passage;
            }
          }
        }catch(Exception $e){
          throw new \Exception("Il y a un problème avec le contrat ".$this->getId());
        }
        return null;
    }

    /**
     * Add etablissement
     *
     * @param AppBundle\Document\Etablissement $etablissement
     */
    public function addEtablissement(\AppBundle\Document\Etablissement $etablissement) {
        foreach ($this->getEtablissements() as $etb) {
            if ($etb->getId() == $etablissement->getId()) {
                return;
            }
        }
        $this->etablissements[] = $etablissement;
    }

    /**
     * Remove etablissement
     *
     * @param AppBundle\Document\Etablissement $etablissement
     */
    public function removeEtablissement(Etablissement $etablissement) {
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

    public function getEtablissementIds() {
        $ids = array();
        foreach($this->getEtablissements() as $etablissement) {
            $ids[] = $etablissement->getId();
        }
        return $ids;
    }

    /**
     * Set technicien
     *
     * @param AppBundle\Document\Compte $technicien
     * @return self
     */
    public function setTechnicien(\AppBundle\Document\Compte $technicien) {
        $this->technicien = $technicien;
        return $this;
    }

    /**
     * Get technicien
     *
     * @return AppBundle\Document\Compte $technicien
     */
    public function getTechnicien() {
        return $this->technicien;
    }

    public function changeTechnicien($newTechnicien) {
        if (!$newTechnicien) {
            return false;
        }
        $this->setTechnicien($newTechnicien);
        foreach ($this->getContratPassages() as $contratPassage) {
            foreach ($contratPassage->getPassagesSorted() as $passage) {
                if ($passage->isAPlanifie()) {
                    $passage->removeAllTechniciens();
                    $passage->addTechnicien($newTechnicien);
                }
            }
        }
    }

    public function getTechnicienPlusUtilise(){
      $techniciensArray = array();
      foreach ($this->getContratPassages() as $contratPassage) {
          foreach ($contratPassage->getPassagesSorted() as $passage) {
              foreach ($passage->getTechniciens() as $technicien) {
                if(!array_key_exists($technicien->getId(),$techniciensArray)){
                  $techniciensArray[$technicien->getId()] = new \stdClass();
                  $techniciensArray[$technicien->getId()]->occurs = 0;
                  $techniciensArray[$technicien->getId()]->technicien = $technicien;
                }
                $techniciensArray[$technicien->getId()]->occurs = $techniciensArray[$technicien->getId()]->occurs + 1;
              }
          }
      }
      $techNum = 0;
      $tech = null;
      foreach ($techniciensArray as $techId => $techRefs) {
        if($techRefs->occurs > $techNum){
          $tech = $techRefs->technicien;
        }
      }
      return $tech;
    }

    /**
     * Add contratPassage
     *
     * @param AppBundle\Document\Passage $passage
     */
    public function addPassage(\AppBundle\Document\Etablissement $etablissement, Passage $passage) {
        $contratPassagesToSet = new ContratPassages();
        foreach ($this->getContratPassages() as $idNode => $contratPassages) {
            if (($etablissement->getId() == $contratPassages->getEtablissement()->getId()) || ($etablissement->getId() == $idNode)) {
                $contratPassagesToSet = $contratPassages;
            }
        }
        $contratPassagesToSet->addPassage($passage);
        $contratPassagesToSet->setEtablissement($etablissement);

        $this->addContratPassage($etablissement, $contratPassagesToSet);
    }

    /**
     * Remove contratPassage
     *
     * @param AppBundle\Document\Passage $passage
     */
    public function removePassage(Passage $passage) {
        $contratPassagesToUnset = new ContratPassages();
        foreach ($this->getContratPassages() as $contratPassages) {
            foreach ($contratPassages->getPassages() as $p) {
                if ($p->getId() == $passage->getId()) {
                    $contratPassagesToUnset = $contratPassages;
                }
            }
        }
        $contratPassagesToUnset->removePassage($passage);
    }

    /**
     * Add contratPassage
     *
     * @param AppBundle\Document\ContratPassages $contratPassage
     */
    public function addContratPassage($etablissement, \AppBundle\Document\ContratPassages $contratPassage) {
        $this->contratPassages[$etablissement->getId()] = $contratPassage;
    }

    /**
     * Remove contratPassage
     *
     * @param AppBundle\Document\ContratPassages $contratPassage
     */
    public function removeContratPassage(\AppBundle\Document\ContratPassages $contratPassage) {
        $this->contratPassages->removeElement($contratPassage);
    }

    /**
     * Get contratPassages
     *
     * @return \Doctrine\Common\Collections\Collection $contratPassages
     */
    public function getContratPassages() {
        return $this->contratPassages;
    }

    public function reInitContratPassages() {
        $this->contratPassages = array();
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

    /**
     * Set numeroArchive
     *
     * @param string $numeroArchive
     * @return self
     */
    public function setNumeroArchive($numeroArchive) {
        $this->numeroArchive = $numeroArchive;
        return $this;
    }

    /**
     * Get numeroArchive
     *
     * @return string $numeroArchive
     */
    public function getNumeroArchive() {
        return $this->numeroArchive;
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
     * Set dateResiliation
     *
     * @param date $dateResiliation
     * @return self
     */
    public function setDateResiliation($dateResiliation) {
        $this->dateResiliation = $dateResiliation;
        return $this;
    }

    /**
     * Get dateResiliation
     *
     * @return date $dateResiliation
     */
    public function getDateResiliation() {
        return $this->dateResiliation;
    }

    /**
     * Set tvaReduite
     *
     * @param boolean $tvaReduite
     * @return self
     */
    public function setTvaReduite($tvaReduite) {
        $this->tvaReduite = $tvaReduite;
        return $this;
    }

    /**
     * Get tvaReduite
     *
     * @return boolean $tvaReduite
     */
    public function getTvaReduite() {

        return $this->tvaReduite;
    }

    public function isModifiable() {

        if($this->hasMouvements()) {

            return false;
        }

        if ($this->isEnAttenteAcceptation() || $this->isBrouillon()) {
            return true;
        }
        if ($this->isEnCours()) {
            foreach ($this->getContratPassages() as $contratPassage) {
                foreach ($contratPassage->getPassages() as $p) {
                    if ($p->isPlanifie() || $p->isRealise() || $p->isAnnule()) {
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }

    public function isAnnulable() {
        return (($this->isEnCours()  || $this->isFini()) && !$this->isAnnule());
    }

    public function isReactivable() {
        return ($this->isAnnule() && $this->getTypeContratOriginal());
    }

    /*
     * Fonction à retiré => un contrat ne doit pas être resilié sous forme de statut mais sous forme de type
     */

    public function isResilie() {

        return ($this->statut == ContratManager::STATUT_RESILIE);
    }

    public function isAnnule() {

        return ($this->typeContrat == ContratManager::TYPE_CONTRAT_ANNULE);
    }

    public function isBrouillon() {
        return ($this->statut == ContratManager::STATUT_BROUILLON);
    }

    public function isEnCours() {
        return ($this->statut == ContratManager::STATUT_EN_COURS);
    }


    public function isEnAttenteAcceptation() {

        return ($this->statut == ContratManager::STATUT_EN_ATTENTE_ACCEPTATION);
    }

    public function isFini() {

        return ($this->statut == ContratManager::STATUT_FINI);
    }

    public function removeId() {
        $this->id = null;
    }

    public function removeAllEtablissements(){
      $this->etablissements = new ArrayCollection();
    }

    public function copier() {
        $contrat = clone $this;
        $contrat->removeId();
        $contrat->setNumeroArchive(null);
        $contrat->setIdentifiant(null);
        $contrat->setReferenceClient(null);
        $contrat->setDateAcceptation(null);
        $contrat->setDateDebut(null);
        $contrat->setDateFin(null);
        $contrat->setDateCreation(new \DateTime());
        $contrat->setDateCreationAuto(new \DateTime());
        $contrat->setStatut(ContratManager::STATUT_EN_ATTENTE_ACCEPTATION);
        $contrat->contratPassages = array();
        $contrat->cleanMouvements();
        $contrat->setReconduit(false);
        $contrat->updateObject();
        $contrat->setTypeContrat(null);

        return $contrat;
    }

    public function reconduire($augmentation = 0) {
        if($this->getReconduit()) {

            throw new \Exception("Ce contrat à déjà été reconduit");
        }
        $contrat = clone $this;
        $contrat->removeId();
        $contrat->setIdentifiant(null);
        if (!$contrat->isKeepNumeroArchivage()) {
            $contrat->setNumeroArchive(null);
        }
        foreach ($contrat->getEtablissements() as $etb) {
          $this->addEtablissement($etb);
        }
        if ($augmentation > 0) {
        	$contrat->setPrixHt(round($this->getPrixHt() * (1+($augmentation/100)),2));
        }

        $dateDebut = clone $contrat->getDateDebut();
        $dateAcceptation = clone $contrat->getDateAcceptation();
        $dateFin= clone $contrat->getDateFin();
        $nbMois = $contrat->getDuree();

        $dateDebut = $dateDebut->modify("+" . $nbMois . " month");
        $dateFin = $dateFin->modify("+" . $nbMois . " month -1 second ");

        $contrat->setDateDebut($dateDebut);
        $contrat->setDateFin($dateFin);
        $contrat->setDateCreation(new \DateTime());
        $contrat->setDateCreationAuto(new \DateTime());

        if($contrat->isTypeReconductionTacite()){
          $contrat->setStatut(ContratManager::STATUT_EN_COURS);
          $contrat->setDateAcceptation($dateAcceptation);
        }else{
          $contrat->setStatut(ContratManager::STATUT_EN_ATTENTE_ACCEPTATION);
          $contrat->setDateAcceptation(null);
        }

        $contrat->removeAllEtablissements();

        foreach ($this->getEtablissements() as $etablissement) {
          $contrat->addEtablissement($etablissement);
        }
        $contrat->contratPassages = array();
        $contrat->cleanMouvements();
        $contrat->setReconduit(false);
        $contrat->setDateReconduction(new \DateTime());
        $contrat->setReferenceClient(null);
        if(!$contrat->getTechnicien()){
            $technicien = $this->getTechnicienPlusUtilise();
            if($technicien) {
                $contrat->setTechnicien($technicien);
            }
        }
        $contrat->updateObject();
        return $contrat;
    }

    public function isReconductible() {
        if (!$this->isTypeReconductionTacite() && !$this->isTypeRenouvelableSurProposition()) {

            return false;
        }

        return ($this->isEnCours() || $this->isFini()) && !$this->getReconduit();
    }

    public function isCopiable() {

        return true;
    }

    public function isTypeReconductionTacite() {

        return ($this->getTypeContrat() == ContratManager::TYPE_CONTRAT_RECONDUCTION_TACITE);
    }
    public function isTypePonctuel() {

        return ($this->getTypeContrat() == ContratManager::TYPE_CONTRAT_PONCTUEL);
    }

    public function isTypeRenouvelableSurProposition() {

        return ($this->getTypeContrat() == ContratManager::TYPE_CONTRAT_RENOUVELABLE_SUR_PROPOSITION);
    }

    public function isKeepNumeroArchivage() {
        return $this->isTypeReconductionTacite() || $this->isTypeRenouvelableSurProposition();
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
     * Set markdown
     *
     * @param string $markdown
     * @return self
     */
    public function setMarkdown($markdown) {
        $this->markdown = $markdown;
        return $this;
    }

    /**
     * Get markdown
     *
     * @return string $markdown
     */
    public function getMarkdown() {
        return $this->markdown;
    }

    public function isTacite() {
        return $this->getTypeContrat() == ContratManager::TYPE_CONTRAT_RECONDUCTION_TACITE;
    }

    public function getTva() {
        return ($this->getTvaReduite()) ? 0.1 : 0.2;
    }

    /**
     * Set moyens
     *
     * @param collection $moyens
     * @return self
     */
    public function setMoyens($moyens) {
        $this->moyens = $moyens;
        return $this;
    }

    /**
     * Get moyens
     *
     * @return collection $moyens
     */
    public function getMoyens() {
        return $this->moyens;
    }

    /**
     * Set conditionsParticulieres
     *
     * @param string $conditionsParticulieres
     * @return self
     */
    public function setConditionsParticulieres($conditionsParticulieres) {
        $this->conditionsParticulieres = $conditionsParticulieres;
        return $this;
    }

    /**
     * Get conditionsParticulieres
     *
     * @return string $conditionsParticulieres
     */
    public function getConditionsParticulieres() {
        return $this->conditionsParticulieres;
    }

    /**
     * Set referenceClient
     *
     * @param string $referenceClient
     * @return self
     */
    public function setReferenceClient($referenceClient) {
        $this->referenceClient = $referenceClient;
        return $this;
    }

    /**
     * Get referenceClient
     *
     * @return string $referenceClient
     */
    public function getReferenceClient() {
        return $this->referenceClient;
    }

    /**
     * Add mouvement
     *
     * @param AppBundle\Document\Mouvement $mouvement
     */
    public function addMouvement(\AppBundle\Document\Mouvement $mouvement) {
        $this->mouvements[] = $mouvement;
    }

    /**
     * Remove mouvement
     *
     * @param AppBundle\Document\Mouvement $mouvement
     */
    public function removeMouvement(\AppBundle\Document\Mouvement $mouvement) {
        if($mouvement->getOrigineDocumentGeneration() && $mouvement->getOrigineDocumentGeneration() instanceof Passage && $mouvement->getOrigineDocumentGeneration()->getMouvementDeclenche()) {
            $mouvement->getOrigineDocumentGeneration()->setMouvementDeclenche(false);
        }

        $this->mouvements->removeElement($mouvement);
    }

    /**
     * Get mouvements
     *
     * @return \Doctrine\Common\Collections\Collection $mouvements
     * @return self
     */
    public function getMouvements() {
        return $this->mouvements;
    }

    public function cleanMouvements() {
        $this->mouvements = new ArrayCollection();
    }

    /**
     * Set reconduit
     *
     * @param boolean $reconduit
     * @return self
     */
    public function setReconduit($reconduit) {
        $this->reconduit = $reconduit;
        return $this;
    }

    /**
     * Get reconduit
     *
     * @return boolean $reconduit
     */
    public function getReconduit() {
        return $this->reconduit;
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
        $this->frequencePaiement = $this->getSociete()->getFrequencePaiement();

        return $this->frequencePaiement;
    }

    public function getFrequencePaiementLibelle() {
        $this->getFrequencePaiement();

        if(is_null($this->frequencePaiement)) return "N.C.";

        return ContratManager::$frequences[$this->frequencePaiement];
    }

    public function isSousGarantie()
    {
    	if ($mois = $this->getDureeGarantie()) {
    		if ($dateDebut = $this->getDateDebut()) {
    			$now = new \DateTime();
    			$dateDebut->modify("+$mois month");
    			return ($dateDebut->format('Ymd') >= $now->format('Ymd'))? true : false;
    		}
    		return true;
    	}
    	return false;
    }

    public function getDateExpirationGarantie() {
		$mois = ($this->getDureeGarantie()) ? $this->getDureeGarantie() : 0;

    	if ($this->getDateDebut()) {
    		$date = clone $this->getDateDebut();
    	} elseif ($this->getDateAcceptation()) {
    		$date = clone $this->getDateAcceptation();
    	} else {
    		$date = clone $this->getDateCreation();
    	}
    	$date->modify("+$mois month");
    	return $date;
    }

    /**
     * Set factureDestinataire
     *
     * @param string $factureDestinataire
     * @return self
     */
    public function setFactureDestinataire($factureDestinataire)
    {
        $this->factureDestinataire = $factureDestinataire;
        return $this;
    }

    /**
     * Get factureDestinataire
     *
     * @return string $factureDestinataire
     */
    public function getFactureDestinataire()
    {
        return $this->factureDestinataire;
    }


    /**
     * Set devisInterlocuteur
     *
     * @param $devisInterlocuteur
     * @return self
     */
    public function setDevisInterlocuteur($devisInterlocuteur)
    {
        $this->devisInterlocuteur = $devisInterlocuteur;
        return $this;
    }

    /**
     * Get devisInterlocuteur
     *
     * @return $devisInterlocuteur
     */
    public function getDevisInterlocuteur()
    {
        if($this->getCommanditaire()) {

            return $this->getCommanditaire();
        }

        if(is_null($this->devisInterlocuteur)) {

            return $this->getSociete();
        }

        return $this->devisInterlocuteur;
    }

    public function getDevisDestinataire() {

        return $this->getDevisInterlocuteur()->getDestinataire();
    }

    public function getDevisAdresse() {

        return $this->getDevisInterlocuteur()->getAdresse();
    }

    public function getLibelle() {
    	return $this->getNumeroArchive();
    }

    public function updateNumeroOrdrePassage() {
        $this->nbPassages = null;
        $this->getNbPassages();
        foreach($this->getContratPassages() as $etablissementPassages) {
            $numero = 1;
            foreach ($etablissementPassages->getPassagesDateSorted() as $passage) {
                if ($passage->isControle()) {
                    $passage->setNumeroOrdre("C");
                } elseif ($passage->isGarantie()) {
                    $passage->setNumeroOrdre("G");
                } elseif($passage->isSousContrat()) {
                    $passage->setNumeroOrdre($numero);
                    $numero++;
                }

                $passage->setLibelle(null);
                $passage->getLibelle();
            }
        }
    }

    public function verifyAndClose(){
      $flag = true;
      foreach ($this->getContratPassages() as $contratPassages) {
          foreach ($contratPassages->getPassages() as $passage) {
              if (!$passage->isRealise() && !$passage->isAnnule()) {
                  $flag = false;
              }
          }
      }
      if ($flag) {
          $this->setStatut(ContratManager::STATUT_FINI);
      }else{
        $this->setStatut(ContratManager::STATUT_EN_COURS);
      }
    }

    public function getArrayDatePrevision(){
      $arrayPassages = array();
      foreach ($this->getContratPassages() as $contratPassage) {
          foreach ($contratPassage->getPassagesSorted() as $passage) {
              if($passage->isSousContrat()){
                $arrayPassages[$passage->getDatePrevision()->format("Y-m-d")] = $passage;
              }
          }
          break;
      }
      return $arrayPassages;
    }

    public function calculPca(){
      if(!$this->getContratPassages()->first()){
        return array('pca' => '0', 'ratioFacture' => '0', 'ratioActivite' => '0');
      }
      $nbPassagesEff = $this->getContratPassages()->first()->getNbPassagesRealises(true);
      $nbPassageTotal = $this->getNbPassages();
      if($this->isAnnule()) {
        $nbPassageTotal = 0;
      }
      $nbPassageRestant = $nbPassageTotal - $nbPassagesEff;
      $ratioEffectue = (!$nbPassageTotal)? "1" : (floatval($nbPassagesEff) / floatval($nbPassageTotal));

      $prixFacture =  $this->getPrixFactures();
      $prixTotal =  $this->getPrixHt();

      $ratioFacture = (!$prixTotal)? "0" : (floatval($prixFacture) / floatval($prixTotal));

      $diffRatio = $ratioEffectue - $ratioFacture;

      $pca = $diffRatio * $prixTotal;

      return array('pca' => $pca, 'ratioFacture' => $ratioFacture, 'ratioActivite' => $ratioEffectue);
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

    public function isBonbleu(){
      return boolval($this->getDescription());
    }

    /** @MongoDB\PreUpdate */
    public function preUpdate() {
        $this->updateNumeroOrdrePassage();
    }

    /** @MongoDB\PrePersist */
    public function prePersist() {
        $this->updateNumeroOrdrePassage();
    }

    /**
     * Set dateReconduction
     *
     * @param date $dateReconduction
     * @return self
     */
    public function setDateReconduction($dateReconduction)
    {
        $this->dateReconduction = $dateReconduction;
        return $this;
    }

    /**
     * Get dateReconduction
     *
     * @return date $dateReconduction
     */
    public function getDateReconduction()
    {
        return $this->dateReconduction;
    }

    /**
     * Set commanditaire
     *
     * @param AppBundle\Document\Societe $commanditaire
     * @return self
     */
    public function setCommanditaire($commanditaire = null) {
        $this->commanditaire = $commanditaire;
        return $this;
    }

    /**
     * Get commanditaire
     *
     * @return AppBundle\Document\Societe $commanditaire
     */
    public function getCommanditaire() {
        return $this->commanditaire;
    }

    public function getDestinataireFacturation() {

        if($this->getCommanditaire()) {

            return $this->getCommanditaire();
        }

        return $this->getSociete();
    }

    public function getNbPassagePrevu() {
    	$nbPrevus = 0;
    	foreach ($this->getContratPassages() as $passage){
    			$nbPrevus += $passage->getNbPassagePrevu();
    	}
    	return $nbPrevus;
    }

    public function getNbPassageNonPrevu() {
    	$nbNonPrevus = 0;
    	foreach ($this->getContratPassages() as $passage){
    			$nbNonPrevus += $passage->getNbPassageNonPrevu();
    	}
    	return $nbNonPrevus;
    }


    public function getDureePassagePrevu() {
    	$nbPrevus = 0;
    	foreach ($this->getContratPassages() as $passage){
    			$nbPrevus += $passage->getDureePassagePrevu();
    	}
        return $this->getDureeFormatee($nbPrevus);
    }

    public function getDureePassageNonPrevu() {
    	$nbNonPrevus = 0;
    	foreach ($this->getContratPassages() as $passage){
    			$nbNonPrevus += $passage->getDureePassageNonPrevu();
    	}
        return $this->getDureeFormatee($nbNonPrevus);
    }

    public function getProduitsUtilises()
    {
    	$produits = array();
    	foreach ($this->getContratPassages() as $passage){
    		foreach ($passage->getProduitsUtilises() as $id => $produit) {
    			if (!isset($produits[$id])) {
    				$produits[$id] = array();
    				$produits[$id][0] = '';
    				$produits[$id][1] = 0;
    				$produits[$id][2] = 0;
    				$produits[$id][3] = 0;
    			}
    			$produits[$id][0] = $produit[0];
    			$produits[$id][1] += $produit[1];
    			$produits[$id][2] = $produit[2];
    			$produits[$id][3] = $produits[$id][1] * $produits[$id][2];
    		}
    	}
    	return $produits;
    }

    public function getMontantProduitsUtilises()
    {
    	$total = 0;
    	foreach ($this->getProduitsUtilises() as $produit) {
    		$total += $produit[3];
    	}
    	return $total;
    }

    public function getIntitule(){
      return $this->getSociete()->getRaisonSociale()." contrat ".strtolower($this->getTypeContratLibelle())." n° ".$this->getNumeroArchive();
    }

    public function getDureeFormatee($duree)
    {
        $h = floor($duree/60);
        $m = round(($duree/60 - $h) * 60);
        return sprintf("%02dh%02d", $h, $m);
    }

    public function getEtablissementsWithoutPassages(){
      $etablissementsSansPassages = array();
      foreach($this->getContratPassages() as $contratPassage){
        if(count($contratPassage->getPassages()) < 1){
          $etablissementsSansPassages[] = $contratPassage->getEtablissement()->getId();
        }
      }
      return $etablissementsSansPassages;
    }


    public function isEnCoursStatutLibelle(){
      if($this->getStatutLibelle() == "En cours (réalisé)" || $this->getStatutLibelle() == ContratManager::$statuts_libelles[ContratManager::STATUT_EN_COURS]){
        return true;
      }
      return false;
    }

    public function isAVenirStatutLibelle(){
      if($this->getStatutLibelle() == "A venir" ){
        return true;
      }
      return false;
    }

    public function isEnAttenteStatutLibelle(){
      if($this->getStatutLibelle() == ContratManager::$statuts_libelles[ContratManager::STATUT_EN_ATTENTE_ACCEPTATION]){
        return true;
      }
      return false;
    }

    public function isBrouillonStatutLibelle(){
      if($this->getStatutLibelle() == ContratManager::$statuts_libelles[ContratManager::STATUT_BROUILLON]){
        return true;
      }
      return false;
    }

    public function isTermineStatutLibelle(){
      if($this->getStatutLibelle() == ContratManager::$statuts_libelles[ContratManager::STATUT_FINI]){
        return true;
      }
      return false;
    }

    public function isResilieStatutLibelle(){
      if($this->getStatutLibelle() == ContratManager::$statuts_libelles[ContratManager::STATUT_RESILIE]){
        return true;
      }
      return false;
    }

    public function isAnnuleStatutLibelle(){
      if($this->getStatutLibelle() == ContratManager::$statuts_libelles[ContratManager::STATUT_ANNULE]){
        return true;
      }
      return false;
    }


}
