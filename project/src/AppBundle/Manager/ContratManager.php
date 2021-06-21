<?php

namespace AppBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use AppBundle\Model\MouvementManagerInterface;
use AppBundle\Document\Contrat;
use AppBundle\Document\Etablissement;
use AppBundle\Document\Passage;
use AppBundle\Document\CompteInfos;
use AppBundle\Document\Prestation;
use AppBundle\Document\Produit;
use AppBundle\Document\Societe;
use AppBundle\Model\DocumentFacturableInterface;
use AppBundle\Manager\PassageManager;

class ContratManager implements MouvementManagerInterface {

    const STATUT_BROUILLON = "BROUILLON";
    const STATUT_EN_ATTENTE_ACCEPTATION = "EN_ATTENTE_ACCEPTATION";
    const STATUT_EN_COURS = "EN_COURS";
    const STATUT_FINI = "FINI";
    const STATUT_RESILIE = "RESILIE"; // statut à retirer = ce n'est pas un statut mais un type!!
    const STATUT_ANNULE = "ANNULE";
    const TYPE_CONTRAT_RECONDUCTION_TACITE = 'RECONDUCTION_TACITE';
    const TYPE_CONTRAT_PONCTUEL = 'PONCTUEL';
    const TYPE_CONTRAT_RENOUVELABLE_SUR_PROPOSITION = 'RENOUVELABLE_SUR_PROPOSITION';
    const TYPE_CONTRAT_AUTRE = 'AUTRE';
    const TYPE_CONTRAT_ANNULE = 'ANNULE';
    const MOYEN_3D = 'MOYEN_3D';
    const MOYEN_PIGEONS = 'MOYEN_PIGEONS';
    const MOYEN_BOIS = 'MOYEN_BOIS';
    const MOYEN_VO = 'MOYEN_VO';
    const FREQUENCE_PRELEVEMENT = 'PRELEVEMENT';
    const FREQUENCE_RECEPTION = 'RECEPTION';
    const FREQUENCE_30J = '30J';
    const FREQUENCE_30JMOIS = '30JMOIS';
    const FREQUENCE_45JMOIS = '45JMOIS';
    const FREQUENCE_60J = '60J';

    const EXPORT_PCA_CLIENT = 0;
    const EXPORT_PCA_NUMERO_CONTRAT = 1;
    const EXPORT_PCA_DEBUT = 2;
    const EXPORT_PCA_FIN = 3;
    const EXPORT_PCA_MONTANT_HT = 4;
    const EXPORT_PCA_MONTANT_FACTURE = 5;
    const EXPORT_PCA_RATIO_FACTURE = 6;
    const EXPORT_PCA_NB_PASSAGE = 7;
    const EXPORT_PCA_NB_PASSAGE_EFFECTUE  = 8;
    const EXPORT_PCA_RATIO_PASSAGE  = 9;
    const EXPORT_PCA_PCA_VALEUR = 10;
    const EXPORT_PCA_CONTROLE  = 11;

    public static $moyens_contrat_libelles = array(
        self::MOYEN_3D => '3D',
        self::MOYEN_PIGEONS => 'Pigeons',
        self::MOYEN_BOIS => 'Bois',
        self::MOYEN_VO => 'V.O'
    );
    public static $types_contrat_libelles = array(self::TYPE_CONTRAT_RECONDUCTION_TACITE => 'Reconduction tacite',
        self::TYPE_CONTRAT_PONCTUEL => 'Ponctuel',
        self::TYPE_CONTRAT_RENOUVELABLE_SUR_PROPOSITION => 'Renouvelable sur proposition',
        self::TYPE_CONTRAT_AUTRE => 'Autre',
        self::TYPE_CONTRAT_ANNULE => 'Résilié'
    );

    public static $types_contrats_reconductibles = array(self::TYPE_CONTRAT_RECONDUCTION_TACITE => 'Reconduction tacite',
        self::TYPE_CONTRAT_RENOUVELABLE_SUR_PROPOSITION => 'Renouvelable sur proposition'
    );

    public static $types_contrat_import_index = array(1 => self::TYPE_CONTRAT_RECONDUCTION_TACITE,
        2 => self::TYPE_CONTRAT_PONCTUEL,
        3 => self::TYPE_CONTRAT_RENOUVELABLE_SUR_PROPOSITION,
        4 => self::TYPE_CONTRAT_AUTRE
    );
    public static $statuts_libelles = array(
        self::STATUT_BROUILLON => 'Brouillon',
        self::STATUT_EN_ATTENTE_ACCEPTATION => "En attente",
        self::STATUT_EN_COURS => 'En cours',
        self::STATUT_FINI => 'Terminé',
        self::STATUT_RESILIE => 'Résilié',
        self::STATUT_ANNULE => 'Annulé'
    );
    public static $statuts_libelles_long = array(
        self::STATUT_BROUILLON => 'en brouillon',
        self::STATUT_EN_ATTENTE_ACCEPTATION => "en attente d'acceptation",
        self::STATUT_EN_COURS => 'en cours',
        self::STATUT_FINI => 'terminé',
        self::STATUT_RESILIE => 'résilié',
        self::STATUT_ANNULE => 'annulé',
    );
    public static $statuts_couleurs = array(
        self::STATUT_BROUILLON => 'info',
        self::STATUT_EN_ATTENTE_ACCEPTATION => "warning",
        self::STATUT_EN_COURS => 'default',
        self::STATUT_FINI => 'success',
        self::STATUT_RESILIE => 'danger',
        self::STATUT_ANNULE => 'danger'
    );
    public static $statuts_positions = array(
        self::STATUT_BROUILLON => 0,
        self::STATUT_EN_ATTENTE_ACCEPTATION => 2,
        self::STATUT_EN_COURS => 1,
        self::STATUT_FINI => 4,
        self::STATUT_RESILIE => 5,
        self::STATUT_ANNULE => 6
    );
    public static $frequences = array(
        self::FREQUENCE_PRELEVEMENT => 'Par Prélèvement (après 30 jours le 20 du mois)',
        self::FREQUENCE_RECEPTION => 'À réception',
        self::FREQUENCE_30J => '30 jours',
        self::FREQUENCE_30JMOIS => '30 jours fin de mois',
        self::FREQUENCE_45JMOIS => '45 jours fin de mois',
        self::FREQUENCE_60J => '60 jours'
    );
    public static $frequencesImport = array(
        "2" => self::FREQUENCE_RECEPTION,
        "7" => self::FREQUENCE_30J,
        "3" => self::FREQUENCE_30JMOIS,
        "6" => self::FREQUENCE_45JMOIS,
        "9" => self::FREQUENCE_60J
    );

    public static $pca_entete_libelle = array(
      self::EXPORT_PCA_CLIENT => "Client",
      self::EXPORT_PCA_NUMERO_CONTRAT => "Numéro Contrat",
      self::EXPORT_PCA_DEBUT => "Début",
      self::EXPORT_PCA_FIN => "Fin",
      self::EXPORT_PCA_MONTANT_HT => "Montant HT",
      self::EXPORT_PCA_MONTANT_FACTURE => "Montant facturé",
      self::EXPORT_PCA_RATIO_FACTURE => "Pourcentage facturé",
      self::EXPORT_PCA_NB_PASSAGE => "Nb. passages du contrat",
      self::EXPORT_PCA_NB_PASSAGE_EFFECTUE => "Nb. passages effectués",
      self::EXPORT_PCA_RATIO_PASSAGE => "Pourcentage effectué",
      self::EXPORT_PCA_PCA_VALEUR => "PCA",
      self::EXPORT_PCA_CONTROLE => "Contrôle"

    );

    protected $dm;
    protected $parameters;

    public function __construct(DocumentManager $dm, $parameters) {
        $this->dm = $dm;
        $this->parameters = $parameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getFrequence($freq){
      return self::$frequences[$freq];
    }

    public function createBySociete(Societe $societe, \DateTime $dateCreation = null, Etablissement $etablissement = null) {
        if (!$dateCreation) {
            $dateCreation = new \DateTime();
        }
        $contrat = new Contrat();
        $contrat->setSociete($societe);
        $contrat->setDateCreation($dateCreation);
        $contrat->setDateCreationAuto($dateCreation);
        $contrat->setStatut(self::STATUT_BROUILLON);
        $contrat->addPrestation(new Prestation());
        $contrat->addProduit(new Produit());
        $contrat->setFrequencePaiement($societe->getFrequencePaiement());

        if ($etablissement) {
            $contrat->addEtablissement($etablissement);
        } else {
            $contrat->addEtablissement($societe->getEtablissements()->first());
        }

        return $contrat;
    }

    public function createInterventionRapide(Etablissement $etablissement) {
        $contrat = $this->createBySociete($etablissement->getSociete(), new \DateTime(), $etablissement);
        $contrat->setTypeContrat(ContratManager::TYPE_CONTRAT_PONCTUEL);
        $contrat->setDuree(1);
        $contrat->setDureePassage(60);
        $contrat->setNbFactures(1);
        $contrat->setDureeGarantie(0);
        $contrat->setFrequencePaiement($etablissement->getSociete()->getFrequencePaiement());
        $contrat->setTvaReduite(false);

        return $contrat;
    }

    function create(Etablissement $etablissement, \DateTime $dateCreation = null) {
        $contrat = $this->createBySociete($etablissement->getSociete());
        $contrat->addEtablissement($etablissement);
        return $contrat;
    }

    public function getRepository() {

        return $this->dm->getRepository('AppBundle:Contrat');
    }

    public function sortedContratsByEtablissement(Etablissement $etablissement){
      $contratsByEtablissement = $this->getRepository()->findByEtablissement($etablissement);
      $sortedContratsByEtablissement = array();
      $today = new \DateTime();
      foreach ($contratsByEtablissement as $contrat) {
        $passagesEtablissement = $contrat->hasEtablissementNode($etablissement);
        if($passagesEtablissement){
          $contratObj = new \stdClass();
          $contratObj->contrat = $contrat;
          $contratObj->displayStatut = $contrat->getStatut();
          $contratObj->actif = false;
          if($contrat->isEnCours() && $contrat->getDateDebut() && ($today->format("Ymd") < $contrat->getDateDebut()->format("Ymd"))){
            $contratObj->displayStatut = "A_VENIR";
          }elseif($contrat->isFini() && $contrat->getDateFin() && ($today->format("Ymd") < $contrat->getDateFin()->format("Ymd"))){
          //  var_dump($contrat->getDateFin());
            $contratObj->displayStatut = "REALISE_NON_TERMINE";
          }
          if(($contrat->isEnCours() || $contratObj->displayStatut == "REALISE_NON_TERMINE")
          && ($today <= $contrat->getDateFin()) && ($today >= $contrat->getDateDebut())) {
            $contratObj->actif = true;
          }
          $sortedContratsByEtablissement[] = $contratObj;
        }
      }

      return $sortedContratsByEtablissement;
    }

    private function removeAllPassagesForContrat($contrat) {
        foreach ($contrat->getContratPassages() as $contratPassage) {
            foreach ($contratPassage->getPassages() as $p) {
                $this->dm->remove($p);
            }
        }
        $contrat->reInitContratPassages();
    }

    public function updateInfosPassagePrecedent($contrat, $etablissement = null) {
        $passagesByEtablissement = $this->getPassagesByNumeroArchiveContrat($contrat, true);
        foreach($passagesByEtablissement as $etablissementId => $passages) {
            $passagesNonRealises = array();
            $dernierPassageRealise = null;
            if($etablissement && $etablissement->getId() != $etablissementId) {
                continue;
            }
            foreach($passages as $passage)  {
                if(!$passage->isSousContrat()) {
                    continue;
                }
                if($passage->isAnnule()) {
                    continue;
                }
                if($passage->isRealise()) {
                    $dernierPassageRealise = $passage;
                    break;
                }
                $passagesNonRealises[] = $passage;
            }
            if($dernierPassageRealise) {
                foreach($passagesNonRealises as $passageNonRealise) {
                    $passageNonRealise->setDureePrecedente($dernierPassageRealise->getDureeDate());
                    $passageNonRealise->setDatePrecedente($dernierPassageRealise->getDateDebut());
                }
            }
        }
    }

    public function generateAllPassagesForContrat($contrat) {
        $this->removeAllPassagesForContrat($contrat);

        $date_debut = $contrat->getDateDebut();
        $date_acceptation = $contrat->getDateAcceptation();
        if (!$date_debut || !$date_acceptation) {
            return false;
        }
        $date_debut = clone $contrat->getDateDebut();
        $passagesArray = $contrat->getPrevisionnel($date_debut);
        ksort($passagesArray);
        $firstEtb = true;
        foreach ($contrat->getEtablissements() as $etablissement) {
            $cpt = 0;
            foreach ($passagesArray as $datePassage => $passageInfos) {
                $datePrevision = new \DateTime($datePassage);
                $passage = new Passage();
                $passage->setEtablissement($etablissement);
                $passage->setEtablissementIdentifiant($etablissement->getIdentifiant());
                if($contrat->getTechnicien()){
                  $passage->addTechnicien($contrat->getTechnicien());
                }
                $passage->setDatePrevision($datePrevision);
                if (!$cpt) {
                    $passage->setDateDebut($datePrevision);
                }
                if ($firstEtb && $passageInfos->mouvement_declenchable) {
                    $passage->setMouvementDeclenchable($passageInfos->mouvement_declenchable);
                }
                if ($audit = $passageInfos->audit) {
                	$passage->setAudit($audit);
                }

                $passage->setMultiTechnicien($contrat->getMultiTechnicien());

                $passage->setContrat($contrat);
                $passage->setTypePassage(PassageManager::TYPE_PASSAGE_CONTRAT);
                foreach ($passageInfos->prestations as $prestationPrevu) {
                    $prestationObj = new Prestation();
                    $prestationObj->setNom($prestationPrevu->getNom());
                    $prestationObj->setNomCourt($prestationPrevu->getNomCourt());
                    $prestationObj->setIdentifiant($prestationPrevu->getIdentifiant());
                    $prestationObj->setNbPassages(0);
                    $passage->addPrestation($prestationObj);
                }
                foreach ($contrat->getProduits() as $produit) {
                    $produitNode = clone $produit;
                    $passage->addProduit($produitNode);
                }

                if ($passage) {
                    $contrat->addPassage($etablissement, $passage);
                    $this->dm->persist($passage);
                }
                $cpt++;
            }
            $firstEtb = false;
        }

        $this->updateInfosPassagePrecedent($contrat);

        $contrat->updateNumeroOrdrePassage();

        $this->dm->flush();
    }

    public function getPassageManager()
    {
    	return new PassageManager($this->dm, $this);
    }

    public function copyPassagesForContratReconduit($contratReconduit,$contratOrigine) {
        $this->generateAllPassagesForContrat($contratReconduit);
        if($contratReconduit->isTypeReconductionTacite()){
          $this->updateEcartDatesPrevisionPassage($contratReconduit,$contratOrigine);
        }
    }


    public function updateEcartDatesPrevisionPassage($contratReconduit,$contratOrigine){
        $datesPrevisionArray = array();
        foreach ($contratOrigine->getContratPassages() as $etb => $contratPassage) {
          $datesPrevisionArray[$etb] = array();
            foreach ($contratPassage->getPassagesSorted() as $passage) {
              if($passage->isSousContrat()){
                $datesPrevisionArray[$etb][] = $passage;
              }
          }
        }
      $dateDebutContratOrigine = clone $contratOrigine->getDateDebut();
      foreach ($contratReconduit->getContratPassages() as $etb => $contratPassage) {
          $cpt = 0;
          foreach ($contratPassage->getPassagesSorted() as $passage) {
           $dateDebutContratReconduit = clone $contratReconduit->getDateDebut();
           if(!isset($datesPrevisionArray[$etb][$cpt])){ continue; }
           $datePrevisionCloned = clone $datesPrevisionArray[$etb][$cpt]->getDatePrevision();
           $ecartDateDebutDatePrev = $dateDebutContratOrigine->diff($datePrevisionCloned)->format('%R%a');
           $datePrevision = $dateDebutContratReconduit->modify($ecartDateDebutDatePrev." days");
           $passage->setDatePrevision($datePrevision);
           $cpt++;
        }
      }
    }

    public function updateNbFactureForContrat($contrat) {
        $passagesDatesArray = $contrat->getPrevisionnel($contrat->getDateDebut());
        ksort($passagesDatesArray);
        $newDateWithMvtDeclenchable = array();
        foreach ($passagesDatesArray as $datePrev => $passagePrev) {
            if ($passagePrev->mouvement_declenchable) {
                $newDateWithMvtDeclenchable[] = true;
            } else {
                $newDateWithMvtDeclenchable[] = false;
            }
        }

        foreach ($contrat->getContratPassages() as $contratPassage) {
            $num_passage = 0;
            foreach ($contratPassage->getPassagesSorted() as $passage) {
                if ($passage->isSousContrat() && isset($newDateWithMvtDeclenchable[$num_passage])) {
                    if ($newDateWithMvtDeclenchable[$num_passage]) {
                        $passage->setMouvementDeclenchable(true);
                    } else {
                        $passage->setMouvementDeclenchable(false);
                    }
                    $num_passage++;
                    $this->dm->persist($passage);
                }
            }
            break;
        }
        $this->dm->flush();
    }

    public function getMouvementsBySociete(Societe $societe, $isFaturable, $isFacture) {
        $contrats = $this->getRepository()->findContratMouvements($societe, $isFaturable, $isFacture);
        $mouvements = array();

        foreach ($contrats as $contrat) {
            foreach ($contrat->getMouvements() as $mouvement) {
                if ($mouvement->getFacturable() != $isFaturable || $mouvement->getFacture() != $isFacture) {
                    continue;
                }

                $mouvements[] = $mouvement;
            }
        }

        return $mouvements;
    }

    public function getMouvements($isFaturable, $isFacture) {
        $mouvements = array();

        return $mouvements;
    }

    public function getNbContratWithCompteForCommercial($compte) {
        return $this->getRepository()->countContratByCommercial($compte);
    }

    public function getPassagesByNumeroArchiveContrat(Contrat $contrat, $reverse = false) {
        $contratsByNumero = $this->getRepository()->findByNumeroArchive($contrat->getNumeroArchive());
        $passagesByNumero = array();
        foreach ($contratsByNumero as $contrat) {
            foreach ($contrat->getContratPassages() as $contratPassages) {
                $idEtb = $contratPassages->getEtablissement()->getId();
                if (!array_key_exists($idEtb, $passagesByNumero)) {
                    $passagesByNumero[$idEtb] = array();
                }
                foreach ($contratPassages->getPassages() as $passage) {
                    $passagesByNumero[$idEtb][$passage->getDatePrevision()->format('Ymd').uniqid()] = $passage;
                }
            }
        }
        foreach ($passagesByNumero as $idEtb => $passagesByNumeroAndEtb) {
            $passages = $passagesByNumeroAndEtb;
            if ($reverse) {
                krsort($passages);
            } else {
                ksort($passages);
            }
            $passagesByNumero[$idEtb] = $passages;
        }
        return $passagesByNumero;
    }

    public function getHistoriquePassagesByNumeroArchive(Passage $passage, $nbHistorique = 10){
    $passagesNumArchive = $this->getPassagesByNumeroArchiveContrat($passage->getContrat());
    $historiquePassages =  array();
      foreach ($passagesNumArchive as $etablissementId => $passagesEtablissement) {
        if($etablissementId == $passage->getEtablissement()->getId()){
          krsort($passagesEtablissement);
          $found = false;
          foreach ($passagesEtablissement as $key => $p) {
            if($found && (count($historiquePassages) < $nbHistorique)){
              $historiquePassages[$key] = $p;
            }
            if($passage->getId() == $p->getId()){
              $found = true;
            }
          }
          break;
        }
      }
    return $historiquePassages;
    }

    public function getAllFactureForContrat($contrat) {
        return $this->dm->getRepository('AppBundle:Facture')->findAllByContrat($contrat);
    }

    public function isContratEnRetardPaiement($contrat) {
        $factures = $this->getAllFactureForContrat($contrat);
        foreach ($factures as $facture) {
            if (!$facture->isCloture()) {
                if ($facture->getDateLimitePaiement()->format('Ymd') < (new \DateTime())->format('Ymd')) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getPcaForCsv($dateDebut = null) {
        if(!$dateDebut){
          $dateDebut = new \DateTime();
          }
        $contratsObjs = $this->getRepository()->findByDateEntreDebutFin($dateDebut);

        $pcaArray = array();
        $pcaArray[] = self::$pca_entete_libelle;

        foreach ($contratsObjs as $contratObj) {

                $pcaArr = array();
                $pcaArr[self::EXPORT_PCA_CLIENT] = $contratObj->getSociete()->getRaisonSociale();
                $pcaArr[self::EXPORT_PCA_NUMERO_CONTRAT] = $contratObj->getNumeroArchive();
                $pcaArr[self::EXPORT_PCA_DEBUT] = $contratObj->getDateDebut()->format('d/m/Y');
                $pcaArr[self::EXPORT_PCA_FIN] = ($contratObj->getDateFin())? $contratObj->getDateFin()->format('d/m/Y') : "";
                $pcaArr[self::EXPORT_PCA_MONTANT_HT] = sprintf("%01.02f",$contratObj->getPrixHt());
                $pcaArr[self::EXPORT_PCA_MONTANT_FACTURE] = sprintf("%01.02f",$contratObj->getPrixFactures());
                $calculPca = $contratObj->calculPca();
                $pcaArr[self::EXPORT_PCA_RATIO_FACTURE] = sprintf("%01.02f",($calculPca['ratioFacture'] * 100))."%";

                $pcaArr[self::EXPORT_PCA_NB_PASSAGE] = $contratObj->getNbPassages();
                $pcaArr[self::EXPORT_PCA_NB_PASSAGE_EFFECTUE] = ($contratObj->getContratPassages()->first())? $contratObj->getContratPassages()->first()->getNbPassagesRealisesOuAnnule(true) : "pas de passages";
                $pcaArr[self::EXPORT_PCA_RATIO_PASSAGE] = sprintf("%01.02f",($calculPca['ratioActivite'] * 100))."%";

                $pcaArr[self::EXPORT_PCA_PCA_VALEUR] = sprintf("%01.02f",$calculPca['pca']);
                $pcaArr[self::EXPORT_PCA_CONTROLE] = $contratObj->getStatutLibelle();

                $pcaArray[] = $pcaArr;

        }
        return $pcaArray;
    }

    public function getNbContratsReconduitByDateReconduction(){
      $contratsWithDateReconduction = $this->getRepository()->findAllContratWithDateReconduction();
      $contratsReconduits = array();
      foreach ($contratsWithDateReconduction as $contrat) {
        if(!array_key_exists($contrat->getDateReconduction()->format("Ymd"), $contratsReconduits)){
          $contratsReconduits[$contrat->getDateReconduction()->format("Ymd")] = new \stdClass();
          $contratsReconduits[$contrat->getDateReconduction()->format("Ymd")]->contrats = array();
          $contratsReconduits[$contrat->getDateReconduction()->format("Ymd")]->date = $contrat->getDateReconduction();
        }
        $contratsReconduits[$contrat->getDateReconduction()->format("Ymd")]->contrats[$contrat->getId()] = $contrat;
      }
      krsort($contratsReconduits);
      return $contratsReconduits;
    }



    public function getStatsForCommerciauxForCsv($dateDebut = null, $dateFin = null, $commercial = null, $statut = null){
    	if(!$dateDebut){
    		$dateDebut = new \DateTime();
    		$dateFin = new \DateTime();
    		$dateFin->modify("+1month");
    	}

    	$contrats = $this->getRepository()->exportAccepteOrResilieByDates($dateDebut,$dateFin);
    	$csv = array();
    	$cpt = 0;
      $csv["AAAaaa_0_0000000000"] = array("Commercial","Client","Contacts", "Com.", "Num.", "Type", "Presta.", "Statut","Montant HT", "Facturé HT", "% Facturé", "Nb Passages");
    	foreach ($contrats as $contrat) {
    		if($contrat->getCommercial()){
    			$commercialContrat = $contrat->getCommercial();
    			if($commercial && ($commercial != $commercialContrat->getId())) {
    				continue;
    			}

    			$identite = $this->dm->getRepository('AppBundle:Compte')->findOneById($commercialContrat->getId())->getIdentite();
    			$arr_ligne = array();
                $resiliation = 0;
                $key = $identite."_0_".$contrat->getDateCreation()->format('Ymd');
                if($contrat->getDateAcceptation()){
                  $key = $identite."_0_".$contrat->getDateAcceptation()->format('Ymd');
                }
                if($contrat->getDateResiliation() && ($contrat->getDateResiliation()->format('Ymd') >= $dateDebut->format('Ymd') && $contrat->getDateResiliation()->format('Ymd') <= $dateFin->format('Ymd'))){
                    $resiliation = 1;
                    $key = $identite."_1_".$contrat->getDateResiliation()->format('Ymd');
                }
    			$keyTotal = $identite."_9_9999999999_TOTAL";
    			$arr_ligne[] = $identite;
    			$arr_ligne[] = $contrat->getSociete()->getRaisonSociale();
    			$arr_ligne[] = str_replace(' / ', "\n", $contrat->getSociete()->getComptesLibelle(true));
    			$arr_ligne[] = $contrat->getCommentaire();
    			$arr_ligne[] = $contrat->getNumeroArchive();
    			$arr_ligne[] = $contrat->getTypeContratLibelle();
    			$arr_ligne[] = implode(', ', $contrat->getUniquePrestations());
                $changementStatutStr = "";
                if($contrat->getDateAcceptation()){
                    $changementStatutStr.=" Accepté le ".$contrat->getDateAcceptation()->format('d/m/Y')."\n";
                }
                if($contrat->getDateResiliation()){
                    $resiliationDate = $contrat->getDateResiliation();
                    if(($resiliationDate->format('Ymd') >= $dateDebut->format('Ymd')) && ($resiliationDate->format('Ymd') <= $dateFin->format('Ymd'))){
                        $changementStatutStr.=" Résilié le ".$resiliationDate->format('d/m/Y');
                    }
                }
    			$arr_ligne[] = $changementStatutStr;
    			$arr_ligne[] = number_format($contrat->getPrixHT(), 2, ',', '');
    			$arr_ligne[] = number_format($contrat->getPrixFactures(), 2, ',', '');
    			$arr_ligne[] = ($contrat->getPrixHT() > 0)? round((100 * $contrat->getPrixFactures() / $contrat->getPrixHT())) : 0;
          $arr_ligne[] = $contrat->getNbPassages();
    			$csv[$key] = $arr_ligne;
    			$csv[$keyTotal][0] = $identite;
    			$csv[$keyTotal][1] = "TOTAL";
    			$csv[$keyTotal][2] = "";
    			$csv[$keyTotal][3] = "";
    			$csv[$keyTotal][4] = "";
    			$csv[$keyTotal][5] = "";
    			$csv[$keyTotal][6] = "";
    			$csv[$keyTotal][7] = "";
    			$csv[$keyTotal][8] = (isset($csv[$keyTotal][8]))? number_format(str_replace(',', '.', $csv[$keyTotal][8]) + $contrat->getPrixHT(), 2, ',', '') : number_format($contrat->getPrixHT(), 2, ',', '');
    			$csv[$keyTotal][9] = (isset($csv[$keyTotal][9]))? number_format(str_replace(',', '.', $csv[$keyTotal][9]) + $contrat->getPrixFactures(), 2, ',', '') : number_format($contrat->getPrixFactures(), 2, ',', '');
    			$csv[$keyTotal][10] = ($csv[$keyTotal][8] > 0)? round((100 * str_replace(',', '.', $csv[$keyTotal][9]) / str_replace(',', '.', $csv[$keyTotal][8]))) : 0;
    		}else{
    			$arr_ligne = array();
    			$key = "zZ_".$cpt."_". $contrat->getNumeroArchive();
    			$keyTotal = "zZ_9_9999999999_TOTAL";
    			if($commercial){
    				continue;
    			}
    			$arr_ligne[] = "Pas de commercial";
    			$arr_ligne[] = $contrat->getSociete()->getRaisonSociale();
    			$arr_ligne[] = str_replace(' / ', "\n", $contrat->getSociete()->getComptesLibelle(true));
    			$arr_ligne[] = $contrat->getNumeroArchive();
    			$arr_ligne[] = $contrat->getTypeContratLibelle();
    			$arr_ligne[] = $contrat->getStatutLibelle();
    			$arr_ligne[] = number_format($contrat->getPrixHT(), 2, ',', '');
    			$arr_ligne[] = number_format($contrat->getPrixFactures(), 2, ',', '');
    			$arr_ligne[] = ($contrat->getPrixHT() > 0)? round((100 * $contrat->getPrixFactures() / $contrat->getPrixHT())) : 0;
          $arr_ligne[] = $contrat->getNbPassages();
    			$csv[$key] = $arr_ligne;
    			$csv[$keyTotal][0] = "Pas de commercial";
    			$csv[$keyTotal][1] = "TOTAL";
    			$csv[$keyTotal][2] = "";
    			$csv[$keyTotal][3] = "";
    			$csv[$keyTotal][4] = "";
    			$csv[$keyTotal][5] = "";
    			$csv[$keyTotal][6] = "";
    			$csv[$keyTotal][7] = "";
    			$csv[$keyTotal][8] = (isset($csv[$keyTotal][8]))? number_format(str_replace(',', '.', $csv[$keyTotal][8]) + $contrat->getPrixHT(), 2, ',', '') : number_format($contrat->getPrixHT(), 2, ',', '');
    			$csv[$keyTotal][9] = (isset($csv[$keyTotal][9]))? number_format(str_replace(',', '.', $csv[$keyTotal][9]) + $contrat->getPrixFactures(), 2, ',', '') : number_format($contrat->getPrixFactures(), 2, ',', '');
    			$csv[$keyTotal][10] = ($csv[$keyTotal][8] > 0)? round((100 * str_replace(',', '.', $csv[$keyTotal][9]) / str_replace(',', '.', $csv[$keyTotal][8]))) : 0;
    		}
    		$csv['zzzZZZ_TOTAL'][0] = "TOTAL";
    		$csv['zzzZZZ_TOTAL'][1] = "";
    		$csv['zzzZZZ_TOTAL'][2] = "";
    		$csv['zzzZZZ_TOTAL'][3] = "";
    		$csv['zzzZZZ_TOTAL'][4] = "";
    		$csv['zzzZZZ_TOTAL'][5] = "";
    		$csv['zzzZZZ_TOTAL'][6] = "";
    		$csv['zzzZZZ_TOTAL'][7] = "";
    		$csv['zzzZZZ_TOTAL'][8] = (isset($csv['zzzZZZ_TOTAL'][8]))? number_format(str_replace(',', '.', $csv['zzzZZZ_TOTAL'][8]) + $contrat->getPrixHT(), 2, ',', '') : number_format($contrat->getPrixHT(), 2, ',', '');
    		$csv['zzzZZZ_TOTAL'][9] = (isset($csv['zzzZZZ_TOTAL'][9]))? number_format(str_replace(',', '.', $csv['zzzZZZ_TOTAL'][9]) + $contrat->getPrixFactures(), 2, ',', '') : number_format($contrat->getPrixFactures(), 2, ',', '');
    		$csv['zzzZZZ_TOTAL'][10] = ($csv['zzzZZZ_TOTAL'][8] > 0)? round((100 * str_replace(',', '.', $csv['zzzZZZ_TOTAL'][9]) / str_replace(',', '.', $csv['zzzZZZ_TOTAL'][8]))) : 0;
    		$cpt++;
    	}
    	ksort($csv);
    	return $csv;

    }


/*
    public function getStatsForRentabiliteForCsv($dateDebut = null, $dateFin = null, $client = null){
    	if(!$dateDebut){
    		$dateDebut = new \DateTime();
    		$dateFin = new \DateTime();
    		$dateFin->modify("+1month");
    	}

    	$contrats = $this->getRepository()->exportOneMonthByDate($dateDebut,$dateFin);
    	$csv = array();
    	$cpt = 0;
    	$csv["AAAaaa_0_0000000000"] = array("Objet", "Client", "Num. contrat", "Type contrat","Statut contrat","Montant HT", "Nb passage", "Nb garantie", "Temps passage", "Temps garantie", "Produit", "Qté", "Prix u. HT", "Total produit HT");
    	foreach ($contrats as $contrat) {
    			if($client && ($client != $contrat->getSociete()->getIdentifiant())) {
    				continue;
    			}
    			$identite = $contrat->getSociete()->getRaisonSociale();
    			$arr_ligne = array();
    			$key = $identite."_".$cpt."_".$contrat->getNumeroArchive();
    			$keyTotal = $identite."_9_9999999999_TOTAL";
    			$arr_ligne[] = 'Contrat';
    			$arr_ligne[] = $identite;
    			$arr_ligne[] = $contrat->getNumeroArchive();
    			$arr_ligne[] = $contrat->getTypeContratLibelle();
    			$arr_ligne[] = $contrat->getStatutLibelle();
    			$arr_ligne[] = number_format($contrat->getPrixHT(), 2, ',', '');
    			$arr_ligne[] = $contrat->getNbPassagePrevu();
    			$arr_ligne[] = $contrat->getNbPassageNonPrevu();
    			$arr_ligne[] = $contrat->getDureePassagePrevu();
    			$arr_ligne[] = $contrat->getDureePassageNonPrevu();
    			$arr_ligne[] = '';
    			$arr_ligne[] = '';
    			$arr_ligne[] = '';
    			$arr_ligne[] = number_format($contrat->getMontantProduitsUtilises(), 2, ',', '');
    			$csv[$key] = $arr_ligne;
    			$i = 0;
    			foreach ($contrat->getProduitsUtilises() as $produit) {
    				$i++;
    				$arr_produit = array();
    				$arr_produit[] = 'Produit';
    				$arr_produit[] = $identite;
    				$arr_produit[] = $contrat->getNumeroArchive();
    				$arr_produit[] = $contrat->getTypeContratLibelle();
    				$arr_produit[] = $contrat->getStatutLibelle();
    				$arr_produit[] = '';
    				$arr_produit[] = '';
    				$arr_produit[] = '';
    				$arr_produit[] = '';
    				$arr_produit[] = '';
    				$arr_produit[] = $produit[0];
    				$arr_produit[] = $produit[1];
    				$arr_produit[] = number_format($produit[2], 2, ',', '');
    				$arr_produit[] = number_format($produit[3], 2, ',', '');
    				$csv[$key.'p'.$i] = $arr_produit;
    			}
    		$cpt++;
    	}
    	ksort($csv);
    	return $csv;
    }*/

}
