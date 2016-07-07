<?php

namespace AppBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use AppBundle\Document\Facture;
use AppBundle\Document\FactureLigne;
use AppBundle\Document\Societe;
use AppBundle\Document\Adresse;
use AppBundle\Manager\MouvementManager;

class FactureManager {

    protected $dm;
    protected $mm;
    protected $parameters;

    const DEFAUT_FREQUENCE_JOURS = 10;

    const EXPORT_DATE = 0 ;
    const EXPORT_JOURNAL= 1;
    const EXPORT_COMPTE= 2;
    const EXPORT_PIECE= 3;
    const EXPORT_LIBELLE= 4;
    const EXPORT_DEBIT= 5;
    const EXPORT_CREDIT= 6;
    const EXPORT_MONNAIE= 7;

    const EXPORT_LIGNE_GENERALE = 'generale';
    const EXPORT_LIGNE_TVA = 'tva';
    const EXPORT_LIGNE_HT = 'ht';

    const CODE_TVA_20 = "44571200";
    const CODE_TVA_10 = "44571010";

    const CODE_HT_20_PRODUIT = "70732000";
    const CODE_HT_20 = "70612000";
    const CODE_HT_10 = "70631000";


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
   self::EXPORT_STATS_RECONDUCTION_PREC => "Reconduction tacite (année dernière)",
   self::EXPORT_STATS_RECONDUCTION => "Reconduction tacite du mois",
   self::EXPORT_STATS_PONCTUEL_PREC => "Ponctuel (année dernière)",
   self::EXPORT_STATS_PONCTUEL => "Ponctuel du mois",
   self::EXPORT_STATS_RENOUVELABLE_PREC => "Renouvelable sur proposition (année dernière)",
   self::EXPORT_STATS_RENOUVELABLE => "Renouvelable sur proposition du mois",
  self::EXPORT_STATS_PRODUIT_PREC => "Produits (année dernière)",
  self::EXPORT_STATS_PRODUIT => "Produits du mois",
  self::EXPORT_STATS_TOTAL_PREC => "Total (année dernière)",
  self::EXPORT_STATS_TOTAL => "Total du mois"
);

    function __construct(DocumentManager $dm, MouvementManager $mm, $parameters) {
        $this->dm = $dm;
        $this->mm = $mm;
        $this->parameters = $parameters;
    }

    public function getParameters() {

        return $this->parameters;
    }

    public function getRepository() {

        return $this->dm->getRepository('AppBundle:Facture');
    }

    public function findBySociete(Societe $societe) {

        return $this->getRepository()->findBy(array('societe' => $societe->getId()), array('dateEmission' => 'desc'));
    }

    public function createVierge(Societe $societe) {
        $facture = new Facture();
        $facture->setSociete($societe);
        $facture->setDateEmission(new \DateTime());
        $facture->getEmetteur()->setNom($this->parameters['emetteur']['nom']);
        $facture->getEmetteur()->setAdresse($this->parameters['emetteur']['adresse']);
        $facture->getEmetteur()->setCodePostal($this->parameters['emetteur']['code_postal']);
        $facture->getEmetteur()->setCommune($this->parameters['emetteur']['commune']);
        $facture->getEmetteur()->setTelephone($this->parameters['emetteur']['telephone']);
        $facture->getEmetteur()->setFax($this->parameters['emetteur']['fax']);
        $facture->getEmetteur()->setEmail($this->parameters['emetteur']['email']);

        return $facture;
    }

    public function create(Societe $societe, $mouvements, $dateFacturation) {
        $facture = new Facture();
        $facture->setSociete($societe);
        $facture->setDateFacturation($dateFacturation);
        $facture->setDateEmission(new \DateTime());

        $facture->getEmetteur()->setNom($this->parameters['emetteur']['nom']);
        $facture->getEmetteur()->setAdresse($this->parameters['emetteur']['adresse']);
        $facture->getEmetteur()->setCodePostal($this->parameters['emetteur']['code_postal']);
        $facture->getEmetteur()->setCommune($this->parameters['emetteur']['commune']);
        $facture->getEmetteur()->setTelephone($this->parameters['emetteur']['telephone']);
        $facture->getEmetteur()->setFax($this->parameters['emetteur']['fax']);
        $facture->getEmetteur()->setEmail($this->parameters['emetteur']['email']);

        foreach($mouvements as $mouvement) {
            if(!$mouvement->isFacturable() || $mouvement->isFacture()) {
                continue;
            }
            $ligne = new FactureLigne();
            $ligne->pullFromMouvement($mouvement);
            $facture->addLigne($ligne);
        }

        $facture->update();
        $facture->facturerMouvements();

        return $facture;
    }

    public function getMouvementsBySociete(Societe $societe) {

        return $this->mm->getMouvementsBySociete($societe, true, false);
    }

    public function getMouvements() {

        return $this->mm->getMouvements(true, false);
    }

    public function getStatsForCommerciauxForCsv($dateDebut = null, $dateFin = null){
    if(!$dateDebut){
            $dateDebut = new \DateTime();
            $dateFin = new \DateTime();
            $dateFin->modify("+1month");
          }

          $facturesObjs = $this->getRepository()->exportOneMonthByDate($dateDebut,$dateFin);
          $csv = array();
          $cpt = 0;
          $csv["A_".$cpt] = array("commercial","numero contrat","numero facture","montant Ht");
          foreach ($facturesObjs as $facture) {
            if($facture->getContrat() && $facture->getContrat()->getCommercial()){
                $contrat = $facture->getContrat();
                $commercial = $facture->getContrat()->getCommercial();
                $identite = $this->dm->getRepository('AppBundle:Compte')->findOneById($commercial->getId())->getIdentite();
                $arr_ligne = array();
                $key = $identite."_".$cpt."_".$facture->getNumeroFacture();
                $arr_ligne[] = $identite;
                $arr_ligne[] = $facture->getContrat()->getNumeroArchive();
                $arr_ligne[] = $facture->getNumeroFacture();
                $arr_ligne[] = $facture->getMontantHt();
                $csv[$key] = $arr_ligne;
            }else{
              $arr_ligne = array();
              $key = "z_".$cpt."_". $facture->getNumeroFacture();
              $arr_ligne[] = "Pas de commercial";
              $arr_ligne[] = ($facture->getContrat())? $facture->getContrat()->getNumeroArchive() : "Pas de contrat";
              $arr_ligne[] = $facture->getNumeroFacture();
              $arr_ligne[] = $facture->getMontantHt();
              $csv[$key] = $arr_ligne;
            }
            $cpt++;
          }
          ksort($csv);
          return $csv;

    }

    public function getStatsForCsv($dateDebut = null, $dateFin = null){
      if(!$dateDebut){
        $dateDebut = new \DateTime();
        $dateFin = new \DateTime();
        $dateFin->modify("+1month");
      }

      $ca_stats = array();
      $ca_stats["ENTETE_TITRE"] = array("Exports statistiques du ".$dateDebut->format("d/m/Y")." au ".$dateFin->format("d/m/Y"),"","","","","","","","","","");
      $ca_stats['ENTETE'] = self::$export_stats_libelle;
      foreach ($ca_stats['ENTETE'] as $key => $value) {
        if(preg_match('/année dernière/',$value)){
          $ca_stats['ENTETE'][$key] =  str_replace("année dernière","année dernière : ".$dateDebut->format("m")."/".($dateDebut->format("Y")-1),$value);
        }elseif(preg_match('/du mois/',$value)){
          $ca_stats['ENTETE'][$key] =  str_replace("du mois","du mois : ".$dateDebut->format("m/Y"),$value);
        }
      }


      $facturesObjs = $this->getRepository()->exportOneMonthByDate($dateDebut,$dateFin);
      $this->fillCaStatsArray($ca_stats,$facturesObjs);

      $dateDebutMoinsOneYear = \DateTime::createFromFormat('Y-m-d', $dateDebut->format('Y-m-d'));
      $dateDebutMoinsOneYear->modify("-1 year");

      $dateFinMoinsOneYear = \DateTime::createFromFormat('Y-m-d', $dateFin->format('Y-m-d'));
      $dateFinMoinsOneYear->modify("-1 year");

      $facturesLastYearObjs = $this->getRepository()->exportOneMonthByDate($dateDebutMoinsOneYear,$dateFinMoinsOneYear);
      $this->fillCaStatsArray($ca_stats,$facturesLastYearObjs,true);

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
          $total+= $ca_stats[$commercial][$key_stat];
        }
        if($commercial == "0"){
          $ca_stats[$commercial][self::EXPORT_STATS_REPRESENTANT] = "TOTAL";
        }
      }
      $ca_stats['PAS DE CONTRAT'][$key_stat] = $total;
    }

    foreach ($ca_stats as $commercial => $stats) {
      ksort($stats);
      foreach ($stats as $key => $stat) {
        if($key && is_numeric($ca_stats[$commercial][$key])){
        $ca_stats[$commercial][$key] = number_format($ca_stats[$commercial][$key], 2, ',', ' ');
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

  private function fillCaStatsArray(&$ca_stats,$facturesObjs,$last_year = false){

    foreach ($facturesObjs as $facture) {
      if(!$facture->getContrat()){
          if(!array_key_exists('PAS DE CONTRAT',$ca_stats)){
          $ca_stats['PAS DE CONTRAT'] = array();
            foreach (array_keys(self::$export_stats_libelle) as $stats_index) {
              $ca_stats['PAS DE CONTRAT'][$stats_index] = 0.0;
            }
          }
          if($last_year){
            $ca_stats['PAS DE CONTRAT'][self::EXPORT_STATS_PRODUIT_PREC] += $facture->getMontantHT();
          }else{
              $ca_stats['PAS DE CONTRAT'][self::EXPORT_STATS_PRODUIT] += $facture->getMontantHT();
          }
          $ca_stats['PAS DE CONTRAT'][self::EXPORT_STATS_REPRESENTANT] = "TOTAL";
      }else{
        $commercial = ($facture->getContrat()->getCommercial())? $facture->getContrat()->getCommercial()->getId() : "VIDE";
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

      $ca_stats[$commercial][self::EXPORT_STATS_REPRESENTANT] = (!$ca_stats[$commercial][self::EXPORT_STATS_REPRESENTANT])? 'VIDE' : $this->dm->getRepository('AppBundle:Compte')->findOneById($commercial)->getIdentite();

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
              $facturesArray[] =  $this->buildFactureLigne($facture,self::EXPORT_LIGNE_GENERALE);
              $facturesArray[] =  $this->buildFactureLigne($facture,self::EXPORT_LIGNE_TVA);
              $facturesArray[] =  $this->buildFactureLigne($facture,self::EXPORT_LIGNE_HT);
        }
        return $facturesArray;
    }


    public function buildFactureLigne($facture,$typeLigne = self::EXPORT_LIGNE_GENERALE){
    $factureLigne = array();
    $factureLigne[self::EXPORT_DATE] = $facture->getDateFacturation()->format('d/m/Y');
    $factureLigne[self::EXPORT_JOURNAL] =  "VENTES" ;
    if($typeLigne == self::EXPORT_LIGNE_GENERALE){
        $factureLigne[self::EXPORT_COMPTE] = $facture->getSociete()->getCodeComptable();
        $factureLigne[self::EXPORT_DEBIT] = number_format(($facture->isAvoir())? "0" : $facture->getMontantTTC(), 2, ",", "");
        $factureLigne[self::EXPORT_CREDIT] = number_format(($facture->isAvoir())? $facture->getMontantTTC(): "0", 2, ",", "");
    }elseif($typeLigne == self::EXPORT_LIGNE_TVA){

      if($facture->getTva() == 0.2){
          $factureLigne[self::EXPORT_COMPTE] = self::CODE_TVA_20;
      }elseif($facture->getTva() == 0.1){
        $factureLigne[self::EXPORT_COMPTE] = self::CODE_TVA_10;
      }
      $factureLigne[self::EXPORT_DEBIT] = number_format(($facture->isAvoir())? $facture->getMontantTaxe() : "0", 2, ",", "");
      $factureLigne[self::EXPORT_CREDIT] =  number_format(($facture->isAvoir())? "0" :$facture->getMontantTaxe(), 2, ",", "");
    }elseif($typeLigne == self::EXPORT_LIGNE_HT){
      if($facture->getTva() == 0.2 && $facture->getContrat()){
          $factureLigne[self::EXPORT_COMPTE] = self::CODE_HT_20;
      } elseif($facture->getTva() == 0.2) {
          $factureLigne[self::EXPORT_COMPTE] = self::CODE_HT_20_PRODUIT;
      } elseif($facture->getTva() == 0.1){
        $factureLigne[self::EXPORT_COMPTE] = self::CODE_HT_10;
      }
      $factureLigne[self::EXPORT_DEBIT] = number_format(($facture->isAvoir())? $facture->getMontantHt() : "0", 2, ",", "");
      $factureLigne[self::EXPORT_CREDIT] =  number_format(($facture->isAvoir())? "0" : $facture->getMontantHt(), 2, ",", "");

    }
    $factureLigne[self::EXPORT_PIECE] =  $facture->getNumeroFacture();
    $factureLigne[self::EXPORT_LIBELLE] =  $facture->getSociete()->getRaisonSociale();
    $factureLigne[self::EXPORT_MONNAIE] =  "" ;
    ksort($factureLigne);
    return $factureLigne;
  }
}
