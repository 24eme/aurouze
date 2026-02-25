<?php

namespace AppBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use AppBundle\Config\Config;
use AppBundle\Document\Facture;
use AppBundle\Document\Contrat;
use AppBundle\Document\Devis;
use AppBundle\Document\LigneFacturable;
use AppBundle\Document\Societe;
use AppBundle\Document\Adresse;
use AppBundle\Manager\MouvementManager;

class FactureManager {

    protected $dm;
    protected $mm;
    protected $parameters;
    protected $config;

    const DEFAUT_FREQUENCE_JOURS = 10;
    const FREQUENCE_PERSO = "PERSO";

    const EXPORT_DATE = 0 ;
    const EXPORT_JOURNAL= 1;
    const EXPORT_COMPTE= 2;
    const EXPORT_PIECE= 3;
    const EXPORT_LIBELLE= 4;
    const EXPORT_DEBIT= 5;
    const EXPORT_CREDIT= 6;
    const EXPORT_MONNAIE= 7;

    const EXPORT_SOCIETE_DATE = 0 ;
    const EXPORT_SOCIETE_PIECE = 1;
    const EXPORT_SOCIETE_TYPE = 2;
    const EXPORT_SOCIETE_ECHEANCE = 3;
    const EXPORT_SOCIETE_DEBIT = 4;
    const EXPORT_SOCIETE_CREDIT = 5;
    const EXPORT_SOCIETE_MOYEN_REGLEMENT = 6;



    const EXPORT_LIGNE_GENERALE = 'generale';
    const EXPORT_LIGNE_TVA = 'tva';
    const EXPORT_LIGNE_HT = 'ht';

    const CODE_TVA_20 = "44571200";
    const CODE_TVA_10 = "44571010";

    const CODE_TVA_196 = "44571190";
    const CODE_TVA_07 = "44571070";

    const CODE_HT_20_PRODUIT = "70732000";
    const CODE_HT_20 = "70612000";
    const CODE_HT_10 = "70631000";

    const CODE_HT_196_PRODUIT = "7061000";
    const CODE_HT_196 = "7061000";
    const CODE_HT_07 = "7063000";


    const EXPORT_STATS_REPRESENTANT = 0 ;
    const EXPORT_STATS_RECONDUCTION_PREC = 1 ;
    const EXPORT_STATS_RECONDUCTION = 2 ;
    const EXPORT_STATS_PONCTUEL_PREC = 3 ;
    const EXPORT_STATS_PONCTUEL = 4 ;
    const EXPORT_STATS_RENOUVELABLE_PREC = 6 ;
    const EXPORT_STATS_RENOUVELABLE = 7;
    const EXPORT_STATS_PRODUIT_PREC = 8;
    const EXPORT_STATS_PRODUIT= 9;
    const EXPORT_STATS_TOTAL_PREC = 10;
    const EXPORT_STATS_TOTAL = 11;


    const EXPORT_RETARD_DATE = 0;
    const EXPORT_RETARD_CLIENT = 1;
    const EXPORT_RETARD_ADRESSE_SOCIETE = 2;
    const EXPORT_RETARD_MAIL = 3;
    const EXPORT_RETARD_NUMERO_FIXE = 4;
    const EXPORT_RETARD_NUMERO_PORTABLE = 5;
    const EXPORT_RETARD_NUMERO_FACTURE = 6;
    const EXPORT_RETARD_NUMERO_CONTRAT = 7;
    const EXPORT_RETARD_MONTANT_TOTAL = 8;
    const EXPORT_RETARD_MONTANT_PAYE =  9;
    const EXPORT_RETARD_NB_RELANCES = 10;


    const AUCUNE_RELANCE = "Aucune relance effectuée";
    const RELANCE_RAPPEL = "1ère relance";
    const RELANCE_RAPPEL2 = "2ème relance";
    const RELANCE_DEMEURE_AR = "Mise en demeure avec AR";

    public static $types_nb_relance = array(
      self::AUCUNE_RELANCE, self::RELANCE_RAPPEL, self::RELANCE_RAPPEL2, self::RELANCE_DEMEURE_AR
    );

public static $export_factures_societe_libelle = array(
      self::EXPORT_SOCIETE_DATE => "Date",
       self::EXPORT_SOCIETE_PIECE=> "Pièce",
       self::EXPORT_SOCIETE_TYPE => "Type de Règlement",
       self::EXPORT_SOCIETE_ECHEANCE => "Echéance",
       self::EXPORT_SOCIETE_DEBIT => "Débit",
       self::EXPORT_SOCIETE_CREDIT => "Crédit",
       self::EXPORT_SOCIETE_MOYEN_REGLEMENT => "Mode de réglement"
    );

public static $export_factures_libelle = array(
  self::EXPORT_DATE => "Date",
   self::EXPORT_JOURNAL=> "Journal",
   self::EXPORT_COMPTE => "Compte",
   self::EXPORT_PIECE => "Pièce",
   self::EXPORT_LIBELLE => "Libellé",
   self::EXPORT_DEBIT => "Débit",
   self::EXPORT_CREDIT => "Crédit",
  self::EXPORT_MONNAIE => "Monnaie"
);

public static $export_stats_libelle = array(
  self::EXPORT_STATS_REPRESENTANT => "Représentant",
   self::EXPORT_STATS_RECONDUCTION_PREC => "Reconduction T {X-1}",
   self::EXPORT_STATS_RECONDUCTION => "Reconduction T {X}",
   self::EXPORT_STATS_PONCTUEL_PREC => "Ponctuel {X-1}",
   self::EXPORT_STATS_PONCTUEL => "Ponctuel {X}",
   self::EXPORT_STATS_RENOUVELABLE_PREC => "Renouv Prop {X-1}",
   self::EXPORT_STATS_RENOUVELABLE => "Renouv Prop {X}",
  self::EXPORT_STATS_PRODUIT_PREC => "Produits {X-1}",
  self::EXPORT_STATS_PRODUIT => "Produits {X}",
  self::EXPORT_STATS_TOTAL_PREC => "Total {X-1}",
  self::EXPORT_STATS_TOTAL => "Total {X}"
);
public static $export_factures_en_retards = array(
  self::EXPORT_RETARD_DATE => "Date",
  self::EXPORT_RETARD_CLIENT =>"Client",
  self::EXPORT_RETARD_ADRESSE_SOCIETE => "Adresse Société",
  self::EXPORT_RETARD_MAIL => "Mail Client",
  self::EXPORT_RETARD_NUMERO_FIXE => "Numéro Fixe Client",
  self::EXPORT_RETARD_NUMERO_PORTABLE => "Numéro Portable Client",
  self::EXPORT_RETARD_NUMERO_FACTURE => "Numéro Facture",
  self::EXPORT_RETARD_NUMERO_CONTRAT => "Numéro Contrat",
  self::EXPORT_RETARD_MONTANT_TOTAL => "Montant total",
  self::EXPORT_RETARD_MONTANT_PAYE => "Montant payé",
  self::EXPORT_RETARD_NB_RELANCES => "Nombre de relances"
);

    public static $frequences = array(
        self::FREQUENCE_PERSO => 'Échéance personnalisée',
    );

    function __construct(DocumentManager $dm, MouvementManager $mm, Config $config) {
        $this->dm = $dm;
        $this->mm = $mm;
        $this->config = $config;
    }

    public function getParameters() {
        return $this->config->get('facture');
    }

    public function getRepository() {

        return $this->dm->getRepository('AppBundle:Facture');
    }

    public function findBySociete(Societe $societe) {

        return $this->getRepository()->findBy(array('societe' => $societe->getId()), array('dateFacturation' => 'desc', 'numeroFacture' => 'desc'));
    }

    public function createVierge(Societe $societe, $contrat = null) {
        $facture = new Facture();
        $facture->setSociete($societe);
        $facture->setDateEmission(new \DateTime());

        $this->updateEmetteur($facture, $contrat);

        return $facture;
    }

    public function getZone($facture, $contrat = null) {
      $zone = ContratManager::ZONE_PARIS;
      $commercial_SEINE_ET_MARNE = $this->config->get("commercial_seine_et_marne");
      if (
          ($facture->getCommercial() && preg_match("/".$commercial_SEINE_ET_MARNE."/", $facture->getCommercial()->getNom()))
          || ($facture->getContrat() && $facture->getContrat()->getCommercial() && preg_match("/".$commercial_SEINE_ET_MARNE."/", $facture->getContrat()->getCommercial()->getNom()))
          || ($contrat && $contrat->getZone() == ContratManager::ZONE_SEINE_ET_MARNE)
      ) {

        $zone = ContratManager::ZONE_SEINE_ET_MARNE;
      }

      return $zone;
  }

    public function updateEmetteur($facture, $contrat = null) {
      $parameters = $this->getParameters();

      $commercial_SEINE_ET_MARNE = $this->config->get("commercial_seine_et_marne");
      $emetteur = 'emetteur';
      if ($this->getZone($facture, $contrat) === ContratManager::ZONE_SEINE_ET_MARNE) {

          if (array_key_exists('emetteur_SEINE_ET_MARNE', $parameters) === false) {

              throw new \Exception(sprintf("Contrat : %s ou Facture : %s a une zone 77 alors que la région n'est pas configuré",
              ($contrat) ? $contrat->_id : '', $facture->_id
            ));

          }
          $emetteur = 'emetteur_SEINE_ET_MARNE';


      }

      $facture->getEmetteur()->setNom($parameters[$emetteur]['nom']);
      $facture->getEmetteur()->setAdresse($parameters[$emetteur]['adresse']);
      $facture->getEmetteur()->setCodePostal($parameters[$emetteur]['code_postal']);
      $facture->getEmetteur()->setCommune($parameters[$emetteur]['commune']);
      $facture->getEmetteur()->setTelephone($parameters[$emetteur]['telephone']);
      $facture->getEmetteur()->setFax($parameters[$emetteur]['fax']);
      $facture->getEmetteur()->setEmail($parameters[$emetteur]['email']);
    }

    public function createFromDevis(Devis $devis, $planification = true)
    {
        if(!$devis->getPdfNonEnvoye() && $planification){
          return null;
        }

        $facture = new Facture();
        $facture->setSociete($devis->getSociete());
        $facture->setCommercial($devis->getCommercial());
        $facture->setEmetteur($devis->getEmetteur());
        $facture->setDestinataire($devis->getDestinataire());

        $facture->setDateEmission(new \DateTime());
        $facture->setDateFacturation(new \DateTime());

        $facture->setMontantHT($devis->getMontantHT());
        $facture->setMontantTTC($devis->getMontantTTC());
        $facture->setMontantTaxe($devis->getMontantTaxe());

        $facture->setNumeroDevis($devis->getNumeroDevis());
        foreach ($devis->getLignes() as $ligne) {
          $facture->addLigne($ligne);
        }

        $facture->setDateLimitePaiement($devis->getDateLimitePaiement());

        $facture->update();

        return $facture;
    }

    public function create(Societe $societe, $mouvements, $dateFacturation) {
        $facture = new Facture();
        $facture->setSociete($societe);
        $facture->setDateFacturation($dateFacturation);
        $facture->setDateEmission(new \DateTime());
        $parameters = $this->getParameters();
        $facture->getEmetteur()->setNom($parameters['emetteur']['nom']);
        $facture->getEmetteur()->setAdresse($parameters['emetteur']['adresse']);
        $facture->getEmetteur()->setCodePostal($parameters['emetteur']['code_postal']);
        $facture->getEmetteur()->setCommune($parameters['emetteur']['commune']);
        $facture->getEmetteur()->setTelephone($parameters['emetteur']['telephone']);
        $facture->getEmetteur()->setFax($parameters['emetteur']['fax']);
        $facture->getEmetteur()->setEmail($parameters['emetteur']['email']);

        foreach($mouvements as $mouvement) {
            if(!$mouvement->isFacturable() || $mouvement->isFacture()) {
                continue;
            }
            $ligne = new LigneFacturable();
            $ligne->pullFromMouvement($mouvement);
            $facture->addLigne($ligne);
        }

        $facture->update();
        $facture->updateRestantAPayer();
        $facture->facturerMouvements();

        return $facture;
    }

    public function createFacturesManuelles(Societe $societe, $contrat = null ) {
        if ($contrat->getFacturationManuelle()){
            $montantTotalHT = $contrat->getPrixHt();

            $nbFactures = $contrat->getNbFactures();

            $commercial = $contrat->getCommercial();

            $startDate = $contrat->getDateDebut();
            $now = new \DateTime();
            if($startDate < $now) {
                $startDate = $now;
            }
            $endDate = $contrat->getDateFin();

            $dateFacturation = clone $startDate;

            $period= $startDate->diff($endDate)->format('%a');
            $interval = $period / $nbFactures;

            $currentDate = clone $startDate;

            for($i = 0; $i < $nbFactures; $i++) {
                $facture = $this->createVierge($societe, $contrat);
                $facture->setDateFacturation($dateFacturation);
                $facture->setCommercial($commercial);
                $facture->setDateEmission($facture->getDateFacturation());
                $facture->setDateLimitePaiement($facture->calculDateLimitePaiement());

                $factureLigne = new LigneFacturable();
                $factureLigne->setPrixUnitaire($montantTotalHT / $nbFactures);
                $factureLigne->setTauxTaxe(0.2);
                $factureLigne->setQuantite(1);

                $factureLigne->setMontantHT($factureLigne->getPrixUnitaire());
                $factureLigne->setOrigineDocument($contrat);
                $factureLigne->setMontantTaxe($factureLigne->getTauxTaxe() * $factureLigne->getMontantHT());

                $numeroFacture = $i + 1;
                $factureLigne->setLibelle(sprintf("Facture %s/%s - Proposition n° %s du %s au %s", $numeroFacture, $nbFactures, $contrat->getNumeroArchive(), $contrat->getDateDebut()->format('d/m/Y'), $contrat->getDateFin()->format('d/m/Y')));
                if($contrat->getTvaReduite()){
                    $factureLigne->setTauxTaxe(0.1);
                }

                $facture->addLigne($factureLigne);

                $montantHT = $factureLigne->getMontantHT();
                $facture->setMontantHT($factureLigne->getMontantHT());
                $facture->setMontantTaxe($factureLigne->getMontantTaxe());
                $facture->setMontantTTC($facture->getMontantHT() + $facture->getMontantTaxe());

                $this->dm->persist($facture);
                $this->dm->flush();

                $dateFacturation->modify('+ ' . round($interval) . " days");

                if($facture->getNumeroFacture()){
                    $facture->removeNumeroFacture();
                    $this->dm->persist($facture);
                    $this->dm->flush();
                }
            }
            return $facture;
        }
    }

    public function getMouvementsBySociete(Societe $societe) {

        return $this->mm->getMouvementsBySociete($societe, true, false);
    }

    public function getMouvements() {

        return $this->mm->getMouvements(true, false);
    }

    public function getSolde(Societe $societe, $factureIds = null) {

        return round($this->getTotalPaye($societe, $factureIds) - $this->getTotalFacture($societe, $factureIds),2);
    }

    public function getTotalFacture(Societe $societe, $factureIds = null) {

        return round($this->getRepository()->getMontantFacture($societe, $factureIds), 2);
    }

    public function getTotalPaye(Societe $societe, $factureIds = null) {

        return round($this->dm->getRepository('AppBundle:Paiements')->getMontantPaye($societe, $factureIds), 2);
    }

    public function getResteTropPercu(Societe $societe, $factureIds = null){
        return ($this->dm->getRepository('AppBundle:Facture')->getMontantTropPaye($societe, $factureIds)) - (round($this->dm->getRepository('AppBundle:Facture')->getMontantFacturePayeeAvecTropPercu($societe, $factureIds),2));
    }

    public function getStatsForCsv($dateDebut = null, $dateFin = null, $commercialFiltre = null){
      if(!$dateDebut){
        $dateDebut = new \DateTime();
        $dateFin = new \DateTime();
        $dateFin->modify("+1month");
      }
      $ca_stats = array();
      $ca_stats["ENTETE_TITRE"] = array("Exports statistiques du ".$dateDebut->format("d/m/Y")." au ".$dateFin->format("d/m/Y"));
      $ca_stats['ENTETE'] = self::$export_stats_libelle;
      foreach ($ca_stats['ENTETE'] as $key => $value) {
        if(preg_match('/{X-1}/',$value)){
          $ca_stats['ENTETE'][$key] =  str_replace("{X-1}","".$dateDebut->format("m")."/".($dateDebut->format("Y")-1),$value);
        }elseif(preg_match('/{X}/',$value)){
          $ca_stats['ENTETE'][$key] =  str_replace("{X}","".$dateDebut->format("m/Y"),$value);
        }
      }

      $facturesObjs = $this->getRepository()->exportOneMonthByDate($dateDebut,$dateFin);


      $this->fillCaStatsArray($ca_stats,$facturesObjs, false, $commercialFiltre);


      $dateDebutMoinsOneYear = \DateTime::createFromFormat('Y-m-d H:i:s', $dateDebut->format('Y-m-d')." 00:00:00");
      $dateDebutMoinsOneYear->modify("-1 year");
      $dateDebutMoinsOneYear->modify('first day of this month');


      $dateFinMoinsOneYear = \DateTime::createFromFormat('Y-m-d H:i:s', $dateFin->format('Y-m-d')." 23:59:59");
      $dateFinMoinsOneYear->modify('first day of this month');
      $dateFinMoinsOneYear->modify("-1 year");
      $dateFinMoinsOneYear->modify('last day of this month');
      $facturesLastYearObjs = $this->getRepository()->exportOneMonthByDate($dateDebutMoinsOneYear,$dateFinMoinsOneYear);

      $this->fillCaStatsArray($ca_stats,$facturesLastYearObjs,true, $commercialFiltre);

      //Calcul des Totaux
    foreach ($ca_stats as $commercial => $stat_row) {
      if(($commercial != 'ENTETE') && ($commercial != 'ENTETE_TITRE') && ($commercial != '')){
        $ca_stats[$commercial][self::EXPORT_STATS_TOTAL] =  $ca_stats[$commercial][self::EXPORT_STATS_RECONDUCTION] + $ca_stats[$commercial][self::EXPORT_STATS_PONCTUEL] + $ca_stats[$commercial][self::EXPORT_STATS_RENOUVELABLE] + $ca_stats[$commercial][self::EXPORT_STATS_PRODUIT];
        $ca_stats[$commercial][self::EXPORT_STATS_TOTAL_PREC] =  $ca_stats[$commercial][self::EXPORT_STATS_RECONDUCTION_PREC] + $ca_stats[$commercial][self::EXPORT_STATS_PONCTUEL_PREC] + $ca_stats[$commercial][self::EXPORT_STATS_RENOUVELABLE_PREC] + $ca_stats[$commercial][self::EXPORT_STATS_PRODUIT_PREC];
      }
    }

    foreach (self::$export_stats_libelle as $key_stat => $libelle_stat) {
      $total = 0.0;
      foreach ($ca_stats as $commercial => $stat) {
        if($key_stat > 0 && ($commercial != 'ENTETE') && ($commercial != 'ENTETE_TITRE') && ($commercial != '')){
          if(!array_key_exists($key_stat,$ca_stats[$commercial])){
            $ca_stats[$commercial][$key_stat] = "0";
          }
          $total+= $ca_stats[$commercial][$key_stat];
        }
        if($commercial == "0"){
          $ca_stats[$commercial][self::EXPORT_STATS_REPRESENTANT] = "TOTAL";
        }
      }
      $ca_stats['PAS DE CONTRAT'][$key_stat] = $total;
      $ca_stats['PAS DE CONTRAT'][0] = "TOTAL";
    }
    foreach ($ca_stats as $commercial => $stats) {
      ksort($stats);
      foreach ($stats as $key => $stat) {
        if($key && is_numeric($ca_stats[$commercial][$key])){
        $ca_stats[$commercial][$key] = number_format($ca_stats[$commercial][$key], 2, ',', '');
        }
      }
    }

    $results = array();

    foreach ($ca_stats as $commercial => $stat_row) {
      if($commercial != 'ENTETE'){
        $results[$commercial] = $stat_row;
      }
    }
    ksort($results);
    return array_merge(array('ENTETE_TITRE' => $ca_stats['ENTETE_TITRE']) , array('ENTETE' => $ca_stats['ENTETE']),$results);
  }

  private function fillCaStatsArray(&$ca_stats,$facturesObjs,$last_year = false, $commercialFiltre = null){

    foreach ($facturesObjs as $facture) {
        if(!$facture->getContrat()){
        if(!array_key_exists('PAS DE CONTRAT',$ca_stats)){
        $ca_stats['PAS DE CONTRAT'] = array();
          foreach (array_keys(self::$export_stats_libelle) as $stats_index) {
            $ca_stats['PAS DE CONTRAT'][$stats_index] = 0.0;
          }
        }
            $commercial = ($facture->getCommercial())? $facture->getCommercial()->getId() : 'PAS DE CONTRAT';

            if(!is_null($commercialFiltre) && $commercial != $commercialFiltre) {

                continue;
            }

            if(!array_key_exists($commercial,$ca_stats)){
                foreach (array_keys(self::$export_stats_libelle) as $stats_index) {
                    $ca_stats[$commercial][$stats_index] = 0.0;
                }
            }

          if($last_year){
            $ca_stats[$commercial][self::EXPORT_STATS_PRODUIT_PREC] += $facture->getMontantHT();
          }else{
              $ca_stats[$commercial][self::EXPORT_STATS_PRODUIT] += $facture->getMontantHT();
          }
      }else{

        $commercial = ($facture->getContrat()->getCommercial()) ? $facture->getContrat()->getCommercial()->getId() : "VIDE";

        if(!is_null($commercialFiltre) && $commercial != $commercialFiltre) {
            continue;
        }

        if(!array_key_exists($commercial,$ca_stats)){
          foreach (array_keys(self::$export_stats_libelle) as $stats_index) {
            $ca_stats[$commercial][$stats_index] = 0.0;
          }
        }

      if($facture->getContrat()->isTypeReconductionTacite()){
        if($last_year){
          $ca_stats[$commercial][self::EXPORT_STATS_RECONDUCTION_PREC] += $facture->getMontantHT();
        }else{
          $ca_stats[$commercial][self::EXPORT_STATS_RECONDUCTION] += $facture->getMontantHT();
        }
      }elseif($facture->getContrat()->isTypePonctuel()){
        if($last_year){
          $ca_stats[$commercial][self::EXPORT_STATS_PONCTUEL_PREC] += $facture->getMontantHT();
        }else{
            $ca_stats[$commercial][self::EXPORT_STATS_PONCTUEL] += $facture->getMontantHT();
         }
      }elseif($facture->getContrat()->isTypeRenouvelableSurProposition()){
        if($last_year){
            $ca_stats[$commercial][self::EXPORT_STATS_RENOUVELABLE_PREC] += $facture->getMontantHT();
        }else{
          $ca_stats[$commercial][self::EXPORT_STATS_RENOUVELABLE] += $facture->getMontantHT();
           }
      }elseif($facture->getContrat()->isAnnule()){
        if($last_year){
          $ca_stats[$commercial][self::EXPORT_STATS_RECONDUCTION_PREC] += $facture->getMontantHT();
        }else{
          $ca_stats[$commercial][self::EXPORT_STATS_RECONDUCTION] += $facture->getMontantHT();
        }
      }
    }

        if(!$ca_stats[$commercial][self::EXPORT_STATS_REPRESENTANT] && $this->dm->getRepository('AppBundle:Compte')->findOneById($commercial)) {
            $ca_stats[$commercial][self::EXPORT_STATS_REPRESENTANT] =  $this->dm->getRepository('AppBundle:Compte')->findOneById($commercial)->getIdentite();
        } elseif(!$ca_stats[$commercial][self::EXPORT_STATS_REPRESENTANT]) {
            $ca_stats[$commercial][self::EXPORT_STATS_REPRESENTANT] = $commercial;
        }

    }
  }

    public function getFacturesForCsv($dateDebut = null,$dateFin = null) {
        if(!$dateDebut){
          $dateDebut = new \DateTime();
          $dateFin = new \DateTime();
          $dateFin->modify("+1 month");
        }
        $facturesObjs = $this->getRepository()->exportOneMonthByDate($dateDebut,$dateFin);

        $facturesArray = array();
        $facturesArray[] = self::$export_factures_libelle;

        foreach ($facturesObjs as $facture) {
              $facturesArray[] =  $this->buildLigneFacturable($facture,self::EXPORT_LIGNE_GENERALE);
              $facturesArray[] =  $this->buildLigneFacturable($facture,self::EXPORT_LIGNE_TVA);
              $facturesArray[] =  $this->buildLigneFacturable($facture,self::EXPORT_LIGNE_HT);
        }
        return $facturesArray;
    }

    public function getFacturesPrelevementsForCsv() {
    	$clients = $this->dm->getRepository('AppBundle:Societe')->getSocieteIdsWithIban();
        return $this->getRepository()->exportByPrelevements($clients);
    }


    public function getDetailCaFromFactures($dateDebut = null, $dateFin = null, $commercial = null){
        if(!$dateDebut){
          $dateDebut = new \DateTime();
          $dateFin = new \DateTime();
          $dateFin->modify("+1 month");
        }
        $factures = $this->getRepository()->exportOneMonthByDate($dateDebut,$dateFin);
        $csv = array();
        $cpt = 0;
        $csv["AAAaaa_0_0000000000"] = array("Commercial","Client", "Numéro facture", "Type Contrat", "Presta.","Date Facturation", "Montant facturé HT", "Prix integral Contrat HT");
        foreach ($factures as $facture) {
            if($facture->getContrat()){
                $c = $facture->getContrat();
                $commercialContrat = $c->getCommercial();
                if($commercial && ($commercial != $commercialContrat->getId())) {
                    continue;
                }
                $identite = $this->dm->getRepository('AppBundle:Compte')->findOneById($commercialContrat->getId())->getIdentite();
                $arr_ligne = array();
                $resiliation = 0;
                $key = $identite."_".$facture->getDateFacturation()->format('Ymd')."_".$facture->getNumeroFacture();
                $keyTotal = $identite."_9_9999999999_TOTAL";
                $arr_ligne[] = $identite;
                $arr_ligne[] = $facture->getSociete()->getRaisonSociale();
                $arr_ligne[] = $facture->getNumeroFacture();
                $arr_ligne[] = $c->getTypeContratLibelle();
                $arr_ligne[] = implode(', ', $facture->getContrat()->getUniquePrestations());

                $arr_ligne[] = $facture->getDateFacturation()->format('d/m/Y');
                $arr_ligne[] = number_format($facture->getMontantHT(), 2, ',', '');

                $prix_ht = ($c)? $c->getPrixHT() : $facture->getMontantHT();
                $arr_ligne[] = number_format($prix_ht, 2, ',', '');

                $csv[$key] = $arr_ligne;

                $csv[$keyTotal][0] = $identite;
                $csv[$keyTotal][1] = "TOTAL";
                $csv[$keyTotal][2] = "";
                $csv[$keyTotal][3] = "";
                $csv[$keyTotal][4] = "";
                $csv[$keyTotal][5] = "";
                $csv[$keyTotal][6] = (isset($csv[$keyTotal][6]))? number_format(str_replace(',', '.', $csv[$keyTotal][6]) + $facture->getMontantHT(), 2, ',', '') : number_format($facture->getMontantHT(), 2, ',', '');
                $csv[$keyTotal][7] = (isset($csv[$keyTotal][7]))? number_format(str_replace(',', '.', $csv[$keyTotal][7]) + $prix_ht, 2, ',', '') : number_format($facture->getContrat()->getPrixHT(), 2, ',', '');
            }
        }
        ksort($csv);
        return $csv;

    }

    public function getDetailCaFromFacturesByPrestation($dateDebut = null, $dateFin = null, $prestation = null){
        if(!$dateDebut){
          $dateDebut = new \DateTime();
          $dateFin = new \DateTime();
          $dateFin->modify("+1 month");
        }
        $factures = $this->getRepository()->exportOneMonthByDate($dateDebut,$dateFin);
        $csv = array();
        $cpt = 0;
        $csv["AAAaaa_0_0000000000"] = array("Prestation","Client", "Numéro facture", "Type Contrat", "Presta.","Date Facturation", "Montant facturé HT", "Prix integral Contrat HT");
        $unicite = array();
        foreach ($factures as $facture) {
            if($facture->getContrat()){
                $c = $facture->getContrat();
                $prestationsContrat = $c->getPrestations();
                $p = null;
                foreach ($prestationsContrat as $presta) {
                  if($prestation == $presta->getIdentifiant()){
                    $p = $presta;
                  }
                }
                if($prestation && !$p) {
                  continue;
                }
                foreach ($prestationsContrat as $presta) {
                  $resiliation = 0;
                  $p_id = $presta->getIdentifiant();
                  $categorie = self::getCategoriePrestationFromId($p_id);
                  $keyFacture = $categorie.$facture->getId();

                  if($c && !isset($unicite[$categorie.$c->getId()])) {
                      $prixContrat = $c->getPrixHT();
                      $unicite[$categorie.$c->getId()] = true;
                  } elseif($c && isset($unicite[$categorie.$c->getId()])) {
                      $prixContrat = 0;
                  } else {
                      $prixContrat = $facture->getMontantHT();
                  }

                  if($keyFacture && isset($unicite[$keyFacture])) {
                      continue;
                  }

                  $keyTotal = $categorie."_9_9999999999_TOTAL";

                  $csv[$keyTotal][0] = $categorie;
                  $csv[$keyTotal][1] = "TOTAL";
                  $csv[$keyTotal][2] = "";
                  $csv[$keyTotal][3] = "";
                  $csv[$keyTotal][4] = "";
                  $csv[$keyTotal][5] = "";
                  $csv[$keyTotal][6] = (isset($csv[$keyTotal][6]))? number_format(str_replace(',', '.', $csv[$keyTotal][6]) + $facture->getMontantHT(), 2, ',', '') : number_format($facture->getMontantHT(), 2, ',', '');
                  $csv[$keyTotal][7] = (isset($csv[$keyTotal][7]))? number_format(str_replace(',', '.', $csv[$keyTotal][7]) + $prixContrat, 2, ',', '') : number_format($prixContrat, 2, ',', '');

                  if($keyFacture) {
                      $unicite[$keyFacture] = true;
                  }
                }
            }

        }
        ksort($csv);
        return $csv;

    }

    public function getFacturesSocieteForCsv($societe, $dateDebut = null,$dateFin = null,$etablissement = null) {
        if(!$dateDebut){
          $dateDebut = new \DateTime();
          $dateFin = new \DateTime();
          $dateFin->modify("+1 month");
        }
        $facturesObjs = $this->getRepository()->exportBySocieteAndDate($societe, $dateDebut,$dateFin);
        $facturesArray = array();
        $facturesArray["00"] = new \stdClass();
        $facturesArray["00"]->facture = null;
        $facturesArray["00"]->row = array($societe->getRaisonSociale()." du ".$dateDebut->format("d/m/Y")." au ".$dateFin->format("d/m/Y"));


        $facturesArray["01"] = new \stdClass();
        $facturesArray["01"]->facture = null;
        $facturesArray["01"]->row = array();

        $facturesArray["02"] = new \stdClass();
        $facturesArray["02"]->facture = null;
        $facturesArray["02"]->row = self::$export_factures_societe_libelle;

        $debit = 0;
        $credit = 0;

        foreach ($facturesObjs as $facture) {
              if( ! $facture->getNumeroFacture()){
                continue;
              }
              if($etablissement && (!$facture->getContrat() || !in_array($etablissement->getId(), $facture->getContrat()->getEtablissementIds()))) {
                  continue;
              }
              if($facture->isAvoir() && $facture->getOrigineAvoir()){
                  $factureOrigine = $facture->getOrigineAvoir();
                  if (! array_key_exists($factureOrigine->getId(), $facturesArray)) {
                      $facturesArray[$factureOrigine->getId()] = new \stdClass();
                      $facturesArray[$factureOrigine->getId()]->facture = $factureOrigine;
                      $facturesArray[$factureOrigine->getId()]->row = $this->buildFactureSocieteLigne($factureOrigine);

                      $debit += str_replace(',', '.', $facturesArray[$factureOrigine->getId()]->row[self::EXPORT_SOCIETE_DEBIT]);
                      $credit += str_replace(',', '.', $facturesArray[$factureOrigine->getId()]->row[self::EXPORT_SOCIETE_CREDIT]);
                  }
              }
              $facturesArray[$facture->getId()] = new \stdClass();
              $facturesArray[$facture->getId()]->facture = $facture;
              $facturesArray[$facture->getId()]->row = $this->buildFactureSocieteLigne($facture);

              $debit += (float) str_replace(',', '.', $facturesArray[$facture->getId()]->row[self::EXPORT_SOCIETE_DEBIT]);
              if(strpos($facturesArray[$facture->getId()]->row[self::EXPORT_SOCIETE_CREDIT], '-') !== 0) {
                  $credit += (float) str_replace(',', '.', $facturesArray[$facture->getId()]->row[self::EXPORT_SOCIETE_CREDIT]);
              }
        }

        $facturesArray["za"] = new \stdClass();
        $facturesArray["za"]->facture = null;
        $facturesArray["za"]->row = array("","","","Total",$debit,$credit,"");
        $facturesArray["zz"] = new \stdClass();
        $facturesArray["zz"]->facture = null;
        $facturesArray["zz"]->row = array("","","","Restant à payer","", $debit-$credit,"");
        ksort($facturesArray);
        return $facturesArray;
    }

    public function buildFactureSocieteLigne($facture){
      $factureLigne = array();
      $credit = 0;
      foreach ($facture->getPaiements() as $paiements) {
            foreach ($paiements->getPaiement() as $paiement) {
                if ($paiement->getFacture()->getId() == $facture->getId()) {
                  if($paiement->getMontant()){
                        $credit += $paiement->getMontant();
                  }
                  $factureLigne[self::EXPORT_SOCIETE_DATE] = $facture->getDateFacturation()->format('d/m/Y');
                  $factureLigne[self::EXPORT_SOCIETE_PIECE] =  $facture->getNumeroFacture();
                  if($facture->isAvoir()){
                    $factureLigne[self::EXPORT_SOCIETE_TYPE] =  "Avoir";
                    if($facture->getOrigineAvoir()){
                      $factureLigne[self::EXPORT_SOCIETE_TYPE] .= " (facture ".$facture->getOrigineAvoir()->getNumeroFacture().')';
                    }
                  }else{
                    $factureLigne[self::EXPORT_SOCIETE_TYPE] = "Prestation Facture" ;
                  }
                  $factureLigne[self::EXPORT_SOCIETE_ECHEANCE] =  $facture->getDateLimitePaiement()->format('d/m/Y');
                  $factureLigne[self::EXPORT_SOCIETE_DEBIT] =  number_format($facture->getMontantTTC(), 2, ",", "");
                  $factureLigne[self::EXPORT_SOCIETE_CREDIT] =  number_format($credit , 2, ",", "");
                  if(count($facture->getPaiements()) > 1){
                      $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] = "";
                      foreach($facture->getPaiements() as $ps){
                          foreach($ps->getPaiement() as $p){
                              if ($p->getFacture()->getId() != $facture->getId()) {
                                  continue;
                              }
                              if($p->getMoyenPaiement() == PaiementsManager::MOYEN_PAIEMENT_PRELEVEMENT_BANQUAIRE && $p->getMontant() == 0) {
                                  $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] .= "\n Rejet de prélèvement bancaire\n(".$p->getDatePaiement()->format('d/m/Y').')';
                              } else {
                                  $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] .= "\n".$p->getMoyenPaiementLibelle()."\n(".$p->getDatePaiement()->format('d/m/Y').')';
                              }
                          }
                      }
                  }else{
                      if($paiement->getMoyenPaiement() == PaiementsManager::MOYEN_PAIEMENT_PRELEVEMENT_BANQUAIRE && $paiement->getMontant() == 0) {
                        $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] = "Rejet de prélèvement bancaire";
                      } elseif($facture->isAvoir() && $facture->getAvoirPartielRemboursementCheque()){
                        $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] =  $paiement->getMoyenPaiementLibelle();
                      }else{
                        $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] =  $paiement->getMoyenPaiementLibelle();
                      }

                      $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] .= "\n(".$paiement->getDatePaiement()->format('d/m/Y').')';
                  }
                  if($facture->getPayeeAvecTropPercu()){
                      $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] .= "\n et Soldée avec le trop perçu";
                  }
                }
            }
        }
        if(!count($facture->getPaiements())){
          $factureLigne[self::EXPORT_SOCIETE_DATE] = $facture->getDateFacturation()->format('d/m/Y');
          $factureLigne[self::EXPORT_SOCIETE_PIECE] =  $facture->getNumeroFacture();
          if($facture->isAvoir()){
            $factureLigne[self::EXPORT_SOCIETE_TYPE] =  "Avoir";
            if($facture->getOrigineAvoir()){
              $factureLigne[self::EXPORT_SOCIETE_TYPE] .= " (facture ".$facture->getOrigineAvoir()->getNumeroFacture().')';
            }
          }else{
            $factureLigne[self::EXPORT_SOCIETE_TYPE] = "Prestation Facture" ;
          }
          $factureLigne[self::EXPORT_SOCIETE_ECHEANCE] =  $facture->getDateLimitePaiement()->format('d/m/Y');
          $factureLigne[self::EXPORT_SOCIETE_DEBIT] =  number_format($facture->getMontantTTC(), 2, ",", "");
          $factureLigne[self::EXPORT_SOCIETE_CREDIT] =  "";
          if($facture->isAvoir()){
              $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] = "Aucun (Avoir)";
          } elseif($facture->getPayeeAvecTropPercu()){
              $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] = "Soldée avec le trop perçu";
          } elseif($facture->getAvoir()) {
              $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] = "A donné lieu à un avoir";
          }else{
              $factureLigne[self::EXPORT_SOCIETE_MOYEN_REGLEMENT] = ($facture->getAvoirPartielRemboursementCheque())? "Remboursement par chèque le ".$facture->getDateFacturation()->format('d/m/Y') : "";
          }
        }
      return $factureLigne;
    }

    public function buildLigneFacturable($facture,$typeLigne = self::EXPORT_LIGNE_GENERALE){
    $factureLigne = array();
    $factureLigne[self::EXPORT_DATE] = $facture->getDateFacturation()->format('d/m/Y');
    $factureLigne[self::EXPORT_JOURNAL] =  "VENTES" ;
    if($typeLigne == self::EXPORT_LIGNE_GENERALE){
        $factureLigne[self::EXPORT_COMPTE] = str_replace('AHRB_', '', $facture->getSociete()->getCodeComptable());
        $factureLigne[self::EXPORT_DEBIT] = number_format(($facture->isAvoir())? "0" : $facture->getMontantTTC(), 2, ",", "");
        $factureLigne[self::EXPORT_CREDIT] = number_format(($facture->isAvoir())? (-1*$facture->getMontantTTC()): "0", 2, ",", "");
    }elseif($typeLigne == self::EXPORT_LIGNE_TVA){

      if($facture->getTva() == 0.2){
          $factureLigne[self::EXPORT_COMPTE] = self::CODE_TVA_20;
      }elseif($facture->getTva() == 0.1){
        $factureLigne[self::EXPORT_COMPTE] = self::CODE_TVA_10;
      }elseif($facture->getTva() == 0.196){
        $factureLigne[self::EXPORT_COMPTE] = self::CODE_TVA_196;
      }elseif($facture->getTva() == 0.07){
        $factureLigne[self::EXPORT_COMPTE] = self::CODE_TVA_07;
      }

      $factureLigne[self::EXPORT_DEBIT] = number_format(($facture->isAvoir())? (-1*$facture->getMontantTaxe()) : "0", 2, ",", "");
      $factureLigne[self::EXPORT_CREDIT] =  number_format(($facture->isAvoir())? "0" :$facture->getMontantTaxe(), 2, ",", "");
    }elseif($typeLigne == self::EXPORT_LIGNE_HT){
      if($facture->getTva() == 0.2 && $facture->getContrat()){
          $factureLigne[self::EXPORT_COMPTE] = self::CODE_HT_20;
      } elseif($facture->getTva() == 0.2) {
          $factureLigne[self::EXPORT_COMPTE] = self::CODE_HT_20_PRODUIT;
      } elseif($facture->getTva() == 0.1){
        $factureLigne[self::EXPORT_COMPTE] = self::CODE_HT_10;
      } elseif($facture->getTva() == 0.196 && $facture->getContrat()){
        $factureLigne[self::EXPORT_COMPTE] = self::CODE_HT_196;
      } elseif($facture->getTva() == 0.196){
        $factureLigne[self::EXPORT_COMPTE] = self::CODE_HT_196_PRODUIT;
      } elseif($facture->getTva() == 0.07){
        $factureLigne[self::EXPORT_COMPTE] = self::CODE_HT_07;
      }
      $factureLigne[self::EXPORT_DEBIT] = number_format(($facture->isAvoir())? (-1*$facture->getMontantHt()) : "0", 2, ",", "");
      $factureLigne[self::EXPORT_CREDIT] =  number_format(($facture->isAvoir())? "0" : $facture->getMontantHt(), 2, ",", "");

    }
    $factureLigne[self::EXPORT_PIECE] =  $facture->getNumeroFacture();
    $factureLigne[self::EXPORT_LIBELLE] =  $facture->getSociete()->getRaisonSociale();
    $factureLigne[self::EXPORT_MONNAIE] =  "" ;
    ksort($factureLigne);
    return $factureLigne;
  }

    public function getRetardDePaiementBySociete(Societe $societe, $nbJourSeuil = 0){
      return $this->getRepository()->findRetardDePaiementBySociete($societe, $nbJourSeuil);
    }
    public function buildLigneRetard($facture,$typeLigne = self::EXPORT_LIGNE_GENERALE){
      $factureLigne = array();
      $factureLigne[self::EXPORT_RETARD_DATE] = $facture->getDateFacturation()->format('d/m/Y');
      $factureLigne[self::EXPORT_RETARD_CLIENT] = $facture->getSociete()->getRaisonSociale();

      $factureLigne[self::EXPORT_RETARD_ADRESSE_SOCIETE] = $facture->getSociete()->getAdresse()->getAdresse()." ".$facture->getSociete()->getAdresse()->getCodePostal()." ".$facture->getSociete()->getAdresse()->getCommune();

      if($facture->getSociete()->getContactCoordonnee()->getEmailFacturation()){
        $factureLigne[self::EXPORT_RETARD_MAIL] = $facture->getSociete()->getContactCoordonnee()->getEmailFacturation();
      }else{
        $factureLigne[self::EXPORT_RETARD_MAIL] = $facture->getSociete()->getContactCoordonnee()->getEmail();
      }
      $factureLigne[self::EXPORT_RETARD_NUMERO_FIXE] = $facture->getSociete()->getContactCoordonnee()->getTelephoneFixe();
      $factureLigne[self::EXPORT_RETARD_NUMERO_PORTABLE] = $facture->getSociete()->getContactCoordonnee()->getTelephoneMobile();

      $factureLigne[self::EXPORT_RETARD_NUMERO_FACTURE] =  $facture->getNumeroFacture();

      $factureLigne[self::EXPORT_RETARD_NUMERO_CONTRAT] = "";
      foreach($facture->getLignes() as $f){
        if($f->getOrigineDocument()){
          $factureLigne[self::EXPORT_RETARD_NUMERO_CONTRAT] .= $f->getOrigineDocument()->getNumeroArchive();  //voir s'il faut séparé les numéros de contrats.
        }
      }
      if($typeLigne == self::EXPORT_LIGNE_GENERALE){
          $factureLigne[self::EXPORT_RETARD_MONTANT_TOTAL] = number_format(($facture->isAvoir())? "0" : $facture->getMontantTTC(), 2, ",", "");
          $factureLigne[self::EXPORT_RETARD_MONTANT_PAYE] = number_format(($facture->isAvoir())? (-1*$facture->getMontantTTC()): "0", 2, ",", "");
      }

      $factureLigne[self::EXPORT_RETARD_NB_RELANCES] =  $facture->getNbRelance();
      ksort($factureLigne);
      return $factureLigne;
    }

    public function getRetardDePaiementCSV(){
      $facturesObjs = $this->getRepository()->findFactureRetardDePaiement();
      $facturesArray = array();
      $facturesArray[] = self::$export_factures_en_retards;

      foreach ($facturesObjs as $facture) {
            $facturesArray[] =  $this->buildLigneRetard($facture,self::EXPORT_LIGNE_GENERALE);
      }
      return $facturesArray;
    }


    public static function getCategoriePrestationFromId($idPrestation){
      if(preg_match("/^DESINSECTISATION/",$idPrestation)){
        return "PRESTATIONS 3D";
      }
      if(preg_match("/^DERATISATION/",$idPrestation)){
        return "PRESTATIONS 3D";
      }
      if(preg_match("/^DESINFECTION/",$idPrestation)){
        return "PRESTATIONS 3D";
      }
      if(preg_match("/^PUNAISE-VAPEUR/",$idPrestation)){
        return "PRESTATIONS 3D";
      }
      if(preg_match("/^POISSON-ARGENT/",$idPrestation)){
        return "PRESTATIONS 3D";
      }
      if(preg_match("/^DESTRUCTION-/",$idPrestation)){
        return "PRESTATIONS 3D";
      }



      if(preg_match("/^ASSAINISSEMENT-/",$idPrestation)){
        return "ASSAINISSEMENT";
      }

      if(preg_match("/^DEPIGEONNAGE-/",$idPrestation)){
        return "PIGEONS";
      }

      if(preg_match("/^TRAITEMENT-DES-BOIS-BOIS-SENTRI-TECH/",$idPrestation)){
        return "SENTRI TECH";
      }
      if(preg_match("/^TRAVAUX-DIVERS-SERVICE-MISE-EN-CONFORMITE-SENTRI-TECH/",$idPrestation)){
        return "SENTRI TECH";
      }

      if(preg_match("/^TRAITEMENT-DES-BOIS-/",$idPrestation)){
        return "BOIS";
      }

      if(preg_match("/^DEBOUCHAGE-VIDE-ORDURE-DIVERS/",$idPrestation)){
        return "VO";
      }

      if(preg_match("/^TRAVAUX-DIVERS-SERVICE-VENTE-DE-PRODUITS/",$idPrestation)){
        return "VENTE DE PRODUIT";
      }

      if(preg_match("/^CABLE-BIRD-WIRE-DOUBLE/",$idPrestation)){
        return "VENTE DE PRODUIT";
      }


      if(preg_match("/^TRAVAUX-DIVERS/",$idPrestation)){
        return "TRAVAUX DIVERS AUTRES";
      }
      return $idPrestation;

    }

}
