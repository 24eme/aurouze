<?php

namespace AppBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use AppBundle\Document\Paiements;

class PaiementsManager {

    protected $dm;
    protected $fm;
    protected $parameters;

    const TYPE_REGLEMENT_FACTURE = 'FACTURE';
    const TYPE_REGLEMENT_ACCOMPTE_COMMANDE = 'ACCOMPTE_COMMANDE';
    const TYPE_REGLEMENT_REGULARISATION = 'REGULARISATION';
    const TYPE_REGLEMENT_REGULARISATION_HUISSIER  = 'REGULARISATION_HUISSIER';
    const TYPE_REGLEMENT_REGULARISATION_AVOIR = 'REGULARISATION_AVOIR';
    const TYPE_REGLEMENT_PERTE = 'PERTE';
    const TYPE_REGLEMENT_GAIN = 'GAIN';
    const MOYEN_PAIEMENT_CHEQUE = 'CHEQUE';
    const MOYEN_PAIEMENT_VIREMENT = 'VIREMENT';
    const MOYEN_PAIEMENT_PRELEVEMENT_BANQUAIRE  = 'PRELEVEMENT_BANQUAIRE';
    const MOYEN_PAIEMENT_ESPECE = 'ESPECE';
    const MOYEN_PAIEMENT_TRAITE = 'TRAITE';
    const MOYEN_PAIEMENT_CB = 'CB';
    const MOYEN_PAIEMENT_REGULARISATION_COMPTABLE = 'REGUL_COMPTA';
    const MOYEN_PAIEMENT_VIREMENT_REMBOURSEMENT = 'REMBOURSEMENT_VIREMENT';
    const MOYEN_PAIEMENT_CHEQUE_REMBOURSEMENT = 'REMBOURSEMENT_CHEQUE';

    const EXPORT_DATE_PAIEMENT = 0;
    const EXPORT_CODE_COMPTABLE_TRONQ = 1;
    const EXPORT_VR_PRIX = 2;
    const EXPORT_FACTURE_NUM_RAISON_SOCIALE = 3;
    const EXPORT_PRIX = 4;
    const EXPORT_EMPTY = 5;
    const EXPORT_CODE_COMPTABLE = 6;
    const EXPORT_FACTURE_NUM = 7;
    const EXPORT_CLIENT_RAISON_SOCIALE = 8;
    const EXPORT_TVA_7 = 9;
    const EXPORT_TVA_196 = 10;
    const EXPORT_TVA_10 = 11;
    const EXPORT_TVA_20 = 12;
    const EXPORT_MODE_REGLEMENT = 13;
    const EXPORT_TYPE_REGLEMENT = 14;
    const EXPORT_NUMERO_PIECE_BANQUE = 15;
    const EXPORT_LIBELLE_PIECE_BANQUE = 16;
    const EXPORT_TYPE_PIECE_BANQUE = 17;
    const EXPORT_MONTANT_PIECE_BANQUE = 18;
    const EXPORT_MONTANT_CHEQUE = 19;
    const EXPORT_TYPE_RELANCE = 20;
    const EXPORT_DATE_RELANCE = 21;

    const TYPE_EXPORT_FACTURES = "factures";
    const TYPE_EXPORT_PAIEMENTS = "paiements";
    const TYPE_EXPORT_STATS = "stats";
    const TYPE_EXPORT_PCA = "pca";
    const TYPE_EXPORT_CONTRATS = "contrats";
    const TYPE_EXPORT_DETAIL_CA = "detailca";
    const TYPE_EXPORT_DETAIL_CA_PRESTA = "detailcapresta";

    public static $types_reglements_libelles = array(
        self::TYPE_REGLEMENT_FACTURE => "Règlement de facture",
        self::TYPE_REGLEMENT_ACCOMPTE_COMMANDE => "Acompte à la commande",
        self::TYPE_REGLEMENT_REGULARISATION => "Règlement de régularisation",
        self::TYPE_REGLEMENT_REGULARISATION_HUISSIER => "Commission Huissier",
        self::TYPE_REGLEMENT_REGULARISATION_AVOIR => "Régularisation par avoir",
        self::TYPE_REGLEMENT_PERTE => "Perte",
        self::TYPE_REGLEMENT_GAIN => "Gain");
    public static $nouveau_types_reglements_libelles = array(
        self::TYPE_REGLEMENT_FACTURE => "Règlement de facture",
        self::TYPE_REGLEMENT_ACCOMPTE_COMMANDE => "Acompte à la commande",
        self::TYPE_REGLEMENT_REGULARISATION_HUISSIER => "Régularisation de commission de huissier",
        self::TYPE_REGLEMENT_REGULARISATION => "Règlement de régularisation");
    public static $types_reglements_index = array(
        "1" => self::TYPE_REGLEMENT_FACTURE,
        "2" => self::TYPE_REGLEMENT_ACCOMPTE_COMMANDE,
        "3" => self::TYPE_REGLEMENT_REGULARISATION,
        "4" => self::TYPE_REGLEMENT_REGULARISATION_AVOIR,
        "5" => self::TYPE_REGLEMENT_PERTE,
        "6" => self::TYPE_REGLEMENT_GAIN);
    public static $moyens_paiement_libelles = array(
        self::MOYEN_PAIEMENT_CHEQUE => "Chèque",
        self::MOYEN_PAIEMENT_VIREMENT => "Virement",
        self::MOYEN_PAIEMENT_PRELEVEMENT_BANQUAIRE => "Prélèvement bancaire",
        self::MOYEN_PAIEMENT_ESPECE => "Espèces",
        self::MOYEN_PAIEMENT_TRAITE => "Traite",
        self::MOYEN_PAIEMENT_CB => "Carte Bleue",
        self::MOYEN_PAIEMENT_REGULARISATION_COMPTABLE => "Régularisation comptable",
        self::MOYEN_PAIEMENT_VIREMENT_REMBOURSEMENT => "Virement de remboursement",
        self::MOYEN_PAIEMENT_CHEQUE_REMBOURSEMENT => "Chèque de remboursement"
    );
    public static $moyens_paiement_index = array(
        "1" => self::MOYEN_PAIEMENT_CHEQUE,
        "2" => self::MOYEN_PAIEMENT_VIREMENT,
        "3" => self::MOYEN_PAIEMENT_ESPECE,
        "4" => self::MOYEN_PAIEMENT_TRAITE,
        "5" => self::MOYEN_PAIEMENT_CB,
        "6" => self::MOYEN_PAIEMENT_REGULARISATION_COMPTABLE,
        "7" => self::MOYEN_PAIEMENT_PRELEVEMENT_BANQUAIRE
        );
        public static $export_paiement_libelle = array(
            self::EXPORT_DATE_PAIEMENT => "Date de la pièce de banque",
            self::EXPORT_CODE_COMPTABLE => "Code Client tronqué à 8 caractères",
            self::EXPORT_VR_PRIX => "",
            self::EXPORT_FACTURE_NUM_RAISON_SOCIALE => "Numéro facture - Raison Sociale",
            self::EXPORT_PRIX => "Montant du règlement",
            self::EXPORT_EMPTY => "",
            self::EXPORT_CODE_COMPTABLE => "Code Comptable",
            self::EXPORT_FACTURE_NUM => "N° Facture",
            self::EXPORT_CLIENT_RAISON_SOCIALE => "Client",
            self::EXPORT_TVA_7 => "TVA 7%",
            self::EXPORT_TVA_196 => "TVA 19,6%",
            self::EXPORT_TVA_10 => "TVA 10%",
            self::EXPORT_TVA_20 => "TVA 20%",
            self::EXPORT_MODE_REGLEMENT => "Mode de règlement",
            self::EXPORT_TYPE_REGLEMENT=> "Type de règlement",
            self::EXPORT_NUMERO_PIECE_BANQUE => "N° pièce de Banque",
            self::EXPORT_LIBELLE_PIECE_BANQUE => "Libellé de la pièce de banque",
            self::EXPORT_TYPE_PIECE_BANQUE => "Type de la pièce de banque",
            self::EXPORT_MONTANT_PIECE_BANQUE => "Montant de la pièce de banque",
            self::EXPORT_MONTANT_CHEQUE => "Montant de remise de chèque",
            self::EXPORT_TYPE_RELANCE => "Type de la dernière relance",
            self::EXPORT_DATE_RELANCE => "Date de dernière relance");

        public static $export_prelevement_libelle = array(
            self::EXPORT_DATE_PAIEMENT => "Date prélèvement",
            self::EXPORT_CODE_COMPTABLE => "Code Comptable",
            self::EXPORT_CLIENT_RAISON_SOCIALE => "Client",
            self::EXPORT_FACTURE_NUM => "N° Facture",
            self::EXPORT_PRIX => "Montant",
            self::EXPORT_MODE_REGLEMENT => "Mode de règlement",
            self::EXPORT_TYPE_REGLEMENT=> "Type de règlement"
        );


    public static $types_exports = array(self::TYPE_EXPORT_FACTURES => array("libelle" =>  "Export des factures","picto" =>  "glyphicon glyphicon-eur", "pdf" =>  false),
    self::TYPE_EXPORT_PAIEMENTS => array("libelle" =>  "Export des paiements", "picto" =>  "glyphicon glyphicon-th-list", "pdf" =>  false),
    self::TYPE_EXPORT_STATS  => array("libelle" =>  "Export Chiffre d'affaire", "picto" =>  "glyphicon glyphicon-stats", "pdf" =>  true),
    self::TYPE_EXPORT_CONTRATS  => array("libelle" =>  "Export Contrats", "picto" =>  "glyphicon glyphicon-list-alt", "pdf" =>  true),
    self::TYPE_EXPORT_PCA  => array("libelle" =>  "Export PCA", "picto" =>  "glyphicon glyphicon-send", "pdf" =>  false),
    self::TYPE_EXPORT_DETAIL_CA  => array("libelle" =>  "Détail CA par commercial", "picto" =>  "glyphicon glyphicon-user", "pdf" =>  true),
    self::TYPE_EXPORT_DETAIL_CA_PRESTA  => array("libelle" =>  "Détail CA par prestation", "picto" =>  "glyphicon glyphicon-tree-conifer", "pdf" =>  true),
    );


    function __construct(DocumentManager $dm, FactureManager $fm, $parameters) {
        $this->dm = $dm;
        $this->fm = $fm;
        $this->parameters = $parameters;
    }

    public function getRepository() {

        return $this->dm->getRepository('AppBundle:Paiements');
    }

    public function getParameters() {
        return $this->parameters;
    }

    public function createByDateCreation(\DateTime $dateCreation) {
        $paiements = new Paiements($this->dm);
        $paiements->setDateCreation($dateCreation);
        return $paiements;
    }

    public function getPaiementsPrelevementsForCsv($paiementsObjs){
        $paiementsArray = array();
        $paiementsArray[] = self::$export_prelevement_libelle;

        foreach ($paiementsObjs as $paiements) {
            foreach ($paiements->getPaiement() as $paiement) {
              $paiementDate = $paiement->getDatePaiement();
              $paiementArr = array();
              $paiementArr[self::EXPORT_DATE_PAIEMENT] = $paiementDate->format('d/m/Y');
              $paiementArr[self::EXPORT_CODE_COMPTABLE] = str_replace('AHRB_', '', $paiement->getFacture()->getSociete()->getCodeComptable());
              $paiementArr[self::EXPORT_CLIENT_RAISON_SOCIALE] = $paiement->getFacture()->getSociete()->getRaisonSociale();
              $paiementArr[self::EXPORT_FACTURE_NUM] = $paiement->getFacture()->getNumeroFacture();
              $paiementArr[self::EXPORT_PRIX] = number_format($paiement->getMontant(), 2, ",", "");
              if(isset(self::$moyens_paiement_libelles[$paiement->getMoyenPaiement()])) {
                  $paiementArr[self::EXPORT_MODE_REGLEMENT] = self::$moyens_paiement_libelles[$paiement->getMoyenPaiement()];
              } else {
                  $paiementArr[self::EXPORT_MODE_REGLEMENT] = "";
              }
              if(isset(self::$types_reglements_libelles[$paiement->getTypeReglement()])) {
                $paiementArr[self::EXPORT_TYPE_REGLEMENT] = self::$types_reglements_libelles[$paiement->getTypeReglement()];
              } else {
                $paiementArr[self::EXPORT_TYPE_REGLEMENT] = "";
              }
              $paiementsArray[] = $paiementArr;
          }
      }
      return $paiementsArray;
    }

    public function getPaiementsForCsv($dateDebut = null,$dateFin = null) {
        if(!$dateDebut){
          $dateDebut = new \DateTime();
          $dateFin = new \DateTime();
          $dateFin->modify("+1 month");
          }
        $paiementsObjs = $this->getRepository()->findByDatePaiementsDebutFin($dateDebut,$dateFin);

        $paiementsArray = array();
        $paiementsArray[] = self::$export_paiement_libelle;

        foreach ($paiementsObjs as $paiements) {
            foreach ($paiements->getPaiement() as $paiement) {
              $paiementDate = $paiement->getDatePaiement();
              if(($paiementDate  >= $dateDebut) && ($dateFin > $paiementDate)){
                $paiementArr = array();
                $paiementArr[self::EXPORT_DATE_PAIEMENT] = $paiementDate->format('d/m/Y');
                $paiementArr[self::EXPORT_CODE_COMPTABLE] = str_replace('AHRB_', '', $paiement->getFacture()->getSociete()->getCodeComptable());
                $paiementArr[self::EXPORT_VR_PRIX] = "";
                $paiementArr[self::EXPORT_FACTURE_NUM_RAISON_SOCIALE] = $paiement->getFacture()->getNumeroFacture()." ".$paiement->getFacture()->getSociete()->getRaisonSociale();
                $paiementArr[self::EXPORT_PRIX] = number_format($paiement->getMontant(), 2, ",", "");
                $paiementArr[self::EXPORT_EMPTY] = "";
                $paiementArr[self::EXPORT_FACTURE_NUM] = $paiement->getFacture()->getNumeroFacture();

                $paiementArr[self::EXPORT_CLIENT_RAISON_SOCIALE] = $paiement->getFacture()->getSociete()->getRaisonSociale();
                    $paiementArr[self::EXPORT_TVA_7] = "";
                    $paiementArr[self::EXPORT_TVA_196] = "";

                    foreach ($paiement->getFacture()->getLignes() as $ligneFacture) {
                        if ($ligneFacture->getTauxTaxe() == 0.1) {
                            $tauxTaxe = 0.1;
                            $paiementArr[self::EXPORT_TVA_10] = number_format($paiement->getMontantTaxe($tauxTaxe, $ligneFacture), 2, ",", "");
                        }
                        if (!isset($paiementArr[self::EXPORT_TVA_10])) {
                            $paiementArr[self::EXPORT_TVA_10] = "";
                        }

                        if ($ligneFacture->getTauxTaxe() == 0.2) {
                            $tauxTaxe = 0.2;
                            $paiementArr[self::EXPORT_TVA_20] = number_format($paiement->getMontantTaxe($tauxTaxe, $ligneFacture), 2, ",", "");
                        }
                        if (!isset($paiementArr[self::EXPORT_TVA_20])) {
                            $paiementArr[self::EXPORT_TVA_20] = "";
                        }
                    }

                    if(isset(self::$moyens_paiement_libelles[$paiement->getMoyenPaiement()])) {
                        $paiementArr[self::EXPORT_MODE_REGLEMENT] = self::$moyens_paiement_libelles[$paiement->getMoyenPaiement()];
                    } else {
                        $paiementArr[self::EXPORT_MODE_REGLEMENT] = "";
                    }
                    if(isset(self::$types_reglements_libelles[$paiement->getTypeReglement()])) {
                      $paiementArr[self::EXPORT_TYPE_REGLEMENT] = self::$types_reglements_libelles[$paiement->getTypeReglement()];
                    } else {
                      $paiementArr[self::EXPORT_TYPE_REGLEMENT] = "";
                    }

                      $paiementArr[self::EXPORT_NUMERO_PIECE_BANQUE] = "";
                      $paiementArr[self::EXPORT_LIBELLE_PIECE_BANQUE] = $paiement->getLibelle();
                      if(isset(self::$moyens_paiement_libelles[$paiement->getMoyenPaiement()])) {
                          $paiementArr[self::EXPORT_TYPE_PIECE_BANQUE] = self::$moyens_paiement_libelles[$paiement->getMoyenPaiement()];
                      } else {
                          $paiementArr[self::EXPORT_TYPE_PIECE_BANQUE] = "";
                      }
                      $paiementArr[self::EXPORT_MONTANT_PIECE_BANQUE] = number_format($paiements->getMontantTotalByOperation($paiement), 2, ",", "");
                      $paiementArr[self::EXPORT_MONTANT_CHEQUE] = ($paiement->getMoyenPaiement() == self::MOYEN_PAIEMENT_CHEQUE) ? number_format($paiement->getMontantByMoyenPaiement(self::MOYEN_PAIEMENT_CHEQUE), 2, ",", "") : "";

                      $paiementArr[self::EXPORT_TYPE_RELANCE] = ($paiement->getFacture()->getNbRelance())? FactureManager::$types_nb_relance[$paiement->getFacture()->getNbRelance()] :FactureManager::$types_nb_relance[0];
                      $paiementArr[self::EXPORT_DATE_RELANCE] = "";
                      if($paiement->getFacture()->getNbRelance()){
                        $relanceObj = $paiement->getFacture()->getRelanceObjNumero($paiement->getFacture()->getNbRelance());
                        if($relanceObj){
                          $paiementArr[self::EXPORT_DATE_RELANCE] = $relanceObj->getDateRelance()->format('d/m/Y');
                          $paiementArr[self::EXPORT_DATE_RELANCE] .= "  ";
                        }
                      }
                      $paiementArr[self::EXPORT_DATE_RELANCE].= $paiement->getFacture()->getRelanceCommentaire();


                $paiementsArray[] = $paiementArr;
              }
            }
        }
        return $paiementsArray;
    }

}
