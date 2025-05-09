<?php

namespace AppBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Document\Etablissement;
use AppBundle\Document\Contrat;
use AppBundle\Document\Passage;
use AppBundle\Manager\ContratManager;

class PassageManager
{
    const STATUT_A_ACCEPTER = "A_ACCEPTER";
    const STATUT_A_PLANIFIER = "A_PLANIFIER";
    const STATUT_PLANIFIE = "PLANIFIE";
    const STATUT_REALISE = "REALISE";
    const STATUT_ANNULE = "ANNULE";
    const TYPE_PASSAGE_CONTRAT = "CONTRAT";
    const TYPE_PASSAGE_GARANTIE = "GARANTIE";
    const TYPE_PASSAGE_CONTROLE = "CONTROLE";
    const TYPE_PASSAGE_HORS_CONTRAT = "HORS CONTRAT - FACTURANT";


    const TYPE_INFESTATION_AUCUNE = "AUCUNE";
    const TYPE_INFESTATION_FAIBLE = "FAIBLE";
    const TYPE_INFESTATION_PRESENCE = "PRESENCE";
    const TYPE_INFESTATION_ELEVE = "ELEVE";

    public static $statutsLibellesActions = array(
        self::STATUT_A_ACCEPTER => 'À accepter',
        self::STATUT_A_PLANIFIER => 'À planifier',
        self::STATUT_PLANIFIE => 'Planifié',
        self::STATUT_REALISE => 'Réalisé',
        self::STATUT_ANNULE => 'Annulé'
    );

    public static $statutsLibelles = array(
        self::STATUT_A_ACCEPTER => 'À accepter',
        self::STATUT_A_PLANIFIER => 'À planifier',
        self::STATUT_PLANIFIE => 'Planifié',
        self::STATUT_REALISE => 'Réalisé',
        self::STATUT_ANNULE => 'Annulé'
    );

    public static $typesPassageLibelles = array(
        self::TYPE_PASSAGE_CONTRAT => "Sous contrat",
        self::TYPE_PASSAGE_GARANTIE => "Sous garantie",
        self::TYPE_PASSAGE_CONTROLE => "Contrôle",
        self::TYPE_PASSAGE_HORS_CONTRAT => "Hors contrat - Facturant",
    );

    public static $applications = array(
        'En place',
        'Souillés',
        'Disparus',
        'Ecrasés',
        'Déplacés'
    );

    public static $typesInfestationLibelles = array(
        self::TYPE_INFESTATION_AUCUNE => "Aucune infestation",
        self::TYPE_INFESTATION_FAIBLE => "Faible",
        self::TYPE_INFESTATION_PRESENCE => "Présence moyenne",
        self::TYPE_INFESTATION_ELEVE => "Élevé",
    );

    protected $dm;
    protected $cm;
    protected $parameters;

    function __construct(DocumentManager $dm, ContratManager $cm, $parameters) {
        $this->dm = $dm;
        $this->cm = $cm;
        $this->parameters = $parameters;
    }

    public function getParameters() {
        return $this->parameters;
    }

    function create(Etablissement $etablissement, Contrat $contrat) {
        $passage = new Passage();

        $passage->setEtablissement($etablissement);

        foreach ($contrat->getPrestations() as $prestationContrat) {
            $prestation = clone $prestationContrat;
            $prestation->setNbPassages(0);
            $passage->addPrestation($prestation);
        }
        $previousPassage = null;
        if ($contrat->getPassagesEtablissementNode($etablissement)) {
            foreach ($contrat->getPassagesEtablissementNode($etablissement)->getPassagesSorted(true) as $p) {
                if (($p->getId() != $passage->getId()) && count($p->getTechniciens())) {
                    $previousPassage = $p;
                    break;
                }
            }
        }
        if ($previousPassage) {
            foreach ($previousPassage->getTechniciens() as $tech) {
                $passage->addTechnicien($tech);
            }
        } elseif ($contrat->getTechnicien()) {
            $passage->addTechnicien($contrat->getTechnicien());
        }
        foreach ($contrat->getProduits() as $produitContrat) {
            $produit = clone $produitContrat;
            $produit->setNbUtilisePassage(0);
            $passage->addProduit($produit);
        }
        $passage->setContrat($contrat);
        return $passage;
    }


    public function delete(Passage $passage){
      if (!($passage->isAPlanifie() || $passage->isAnnule())) {
        return;
      }
      $this->dm->remove($passage);
      $this->dm->flush();
    }

    public function getRepository() {

        return $this->dm->getRepository('AppBundle:Passage');
    }

    public function getNbPassagesToPlanPerMonth($secteur = EtablissementManager::SECTEUR_PARIS, $dateUntil = null) {
        if(is_null($dateUntil)) {
            $dateUntil = new \DateTime();
            $dateUntil->modify("last day of +2 month");
        }
        return $this->getRepository()->findNbPassagesToPlanPerMonthUntil($secteur, $dateUntil);
    }

    public function synchroniseProduitsWithConfiguration($passage){
      $configuration = $this->dm->getRepository('AppBundle:Configuration')->findConfiguration();

      foreach ($passage->getProduits() as $produit) {
        $identifiantProduit = $produit->getIdentifiant();
        if($identifiantProduit && !$produit->getNom()){
          $produitConf = $configuration->getProduitByIdentifiant($identifiantProduit);
          $produit->setNom($produitConf->getNom());
          $produit->setPrixHt($produitConf->getPrixHt());
          $produit->setPrixPrestation($produitConf->getPrixPrestation());
          $produit->setPrixVente($produitConf->getPrixVente());
          $produit->setConditionnement($produitConf->getConditionnement());
          $produit->setStatut($produitConf->getStatut());
          if($produitContrat = $passage->getContrat()->getProduit($identifiantProduit)){
          $produit->setNbTotalContrat($produitContrat->getNbTotalContrat());
          $produit->setNbPremierPassage($produitContrat->getNbPremierPassage());
          }
        }
      }
    }

    public function getInfestationLibelle($infestation){
      if(!$infestation || !isset(self::$typesInfestationLibelles[$infestation])){
        return "NC";
      }
      return self::$typesInfestationLibelles[$infestation];
    }

    public function getPassagesForCsv($passageIds){
      $pr = $this->dm->getRepository('AppBundle:Passage');
      $resultCsv = array();

      $passageEntete[] = "Date de prévision";
      $passageEntete[] = "Nom";
      $passageEntete[] = "Adresse";
      $passageEntete[] = "Type";
      $passageEntete[] = "Numéro Contrat";
      $passageEntete[] = "Prix estimatif unitaire du passage";
      $passageEntete[] = "Prix estimatif de la facture du passage";
      $passageEntete[] = "Restant à facturer dans le contrat";
      $prixUnitaireTotal = $restantTotalDesContrats = $prixFactureTotal = 0;
      $resultCsv[] = $passageEntete;
      foreach ($passageIds as $passageId) {
        $passageRow = array();
        $passage = $pr->findOneById($passageId);
        $contrat = $passage->getContrat();
        $prixUnitaire = ($contrat->getNbPassages())? round($contrat->getPrixHt() / $contrat->getNbPassages(),2) : 0;
        $prixUnitaireTotal += $prixUnitaire;



        $restantAuContrat = $contrat->getPrixRestant();
        $restantTotalDesContrats += $restantAuContrat;

        $prixFacture = 0;
        if($passage->getMouvementDeclenchable()){
          $mvt = $contrat->generateMouvement($passage);
          if($mvt){
            $prixFacture = $mvt->getPrixUnitaire();
          }
        }
        $prixFactureTotal+= $prixFacture;

        $passageRow[] = $passage->getDatePrevision()->format('Y-m-d');
        $passageRow[] = $passage->getEtablissementInfos()->getNom();
        $passageRow[] = $passage->getEtablissementInfos()->getAdresse()->getLibelleComplet();
        $passageRow[] = $passage->getEtablissementInfos()->getType();
        $passageRow[] = $contrat->getNumeroArchive();
        $passageRow[] = $prixUnitaire;
        $passageRow[] = $prixFacture;
        $passageRow[] = $restantAuContrat;
        $resultCsv[] = $passageRow;
      }
      $totaux = array();
      $totaux[] = "TOTAL";
      $totaux[] = "";
      $totaux[] = "";
      $totaux[] = "";
      $totaux[] = "";
      $totaux[] = $prixUnitaireTotal;
      $totaux[] = $prixFactureTotal;
      $totaux[] = $restantTotalDesContrats;
      $resultCsv[] = $totaux;
      return $resultCsv;
    }
}