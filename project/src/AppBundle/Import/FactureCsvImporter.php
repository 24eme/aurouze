<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Import;

/**
 * Description of EtablissementCsvImport
 *
 * @author mathurin
 */
use AppBundle\Document\Mouvement;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Manager\ContratManager;
use AppBundle\Manager\FactureManager;
use AppBundle\Manager\SocieteManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Behat\Transliterator\Transliterator;
use AppBundle\Import\CsvFile;
use Symfony\Component\Console\Helper\ProgressBar;
use AppBundle\Document\Facture;
use AppBundle\Document\LigneFacturable;

class FactureCsvImporter {

    protected $dm;
    protected $sm;
    protected $fm;
    protected $cm;

    protected $facturesRedressees = array();
    protected $contratsNbFactures = array();

    const CSV_FACTURE_ID = 0;
    const CSV_NUMERO_FACTURE = 1;
    const CSV_IS_AVOIR = 2;
    const CSV_REF_AVOIR = 3;

    const CSV_REF_ADRESSE = 5;
    const CSV_SOCIETE_ID = 6;
    const CSV_CONTRAT_ID = 7;

    const CSV_TVA_REDUITE = 21;
    const CSV_DESCRIPTION = 12;
    const CSV_FACTURE_LIGNE_ID = 27;
    const CSV_FACTURE_LIGNE_PASSAGE = 28;
    const CSV_FACTURE_LIGNE_PRODUIT = 29;
    const CSV_FACTURE_LIGNE_LIBELLE = 30;
    const CSV_FACTURE_LIGNE_PUHT = 31;
    const CSV_FACTURE_LIGNE_QTE = 32;
    const CSV_FACTURE_TVA = 20;
    const CSV_FACTURE_LIGNE_TVA = 33;
    const CSV_DATE_FACTURATION = 8;
    const CSV_DATE_LIMITE_REGLEMENT = 9;
    const CSV_REGLEMENT_TYPE = 10;
    const CSV_VEROUILLE = 12;
    const CSV_NOM = 13;
    const CSV_NOM_DESTINATAIRE = 14;
    const CSV_ADRESSE_1 = 15;
    const CSV_ADRESSE_2 = 16;
    const CSV_CODE_POSTAL = 17;
    const CSV_COMMUNE = 18;

    public function __construct(DocumentManager $dm, FactureManager $fm, SocieteManager $sm, ContratManager $cm) {
        $this->dm = $dm;
        $this->fm = $fm;
        $this->sm = $sm;
        $this->cm = $cm;
    }

    public function import($file, OutputInterface $output) {
        $csvFile = new CsvFile($file);

        $progress = new ProgressBar($output, 100);
        $progress->start();

        $csv = $csvFile->getCsv();

        $i = 0;
        $cptTotal = 0;

        $lignes = array();
        $currentIdFacture = null;

        foreach ($csv as $data) {
            if(is_null($currentIdFacture)) {
                $currentIdFacture = $data[self::CSV_FACTURE_ID];
            }

            if($currentIdFacture == $data[self::CSV_FACTURE_ID]) {

                $lignes[] = $data;
                continue;
            }

            $facture = $this->importFacture($lignes, $output);

            $i++;
            $cptTotal++;
            if ($cptTotal % (count($csv) / 100) == 0) {
                $progress->advance();
            }

            if ($i >= 1000) {
                $this->dm->flush();
                $this->dm->clear();
                $i = 0;
            }

            $lignes = array();
            $currentIdFacture = $data[self::CSV_FACTURE_ID];
            $lignes[] = $data;
        }

        $this->importFacture($lignes, $output);

        $this->dm->flush();
        $this->dm->clear();
        $progress->finish();

        foreach($this->facturesRedressees as $idAvoir => $identifiantFacture) {
            $facture = $this->fm->getRepository()->findOneById($identifiantFacture);
            if(!$facture) {
                $output->writeln(sprintf("<error>La facture %s de l'avoir %s n'a pas été trouvé</error>", $identifiantFacture, $idAvoir));
                continue;
            }
            $facture->setAvoir($idAvoir);
        }
        $this->dm->flush();
        $this->dm->clear();

        foreach($this->contratsNbFactures as $idContrat => $nbFactures) {
            $contrat = $this->cm->getRepository()->findOneById($idContrat);
            $contrat->setNbFactures($nbFactures);
        }

        $this->dm->flush();
        $this->dm->clear();
    }

    public function importFacture($lignes, $output) {
        if(!count($lignes)) {

            return;
        }

        $ligneFacture = $lignes[0];
        $facture = $this->fm->getRepository()->findOneByIdentifiantReprise($ligneFacture[self::CSV_FACTURE_ID]);
        if ($facture) {
            $output->writeln(sprintf("<comment>La facture %s existe déjà avec l'id %s </comment>", $ligneFacture[self::CSV_FACTURE_ID], $facture->getId()));
            return;
        }


        $mouvements = array();
        foreach($lignes as $ligne) {
            $tvaReduite = false;
            $societe = null;
            $contrat = $this->cm->getRepository()->findOneByIdentifiantReprise($ligne[self::CSV_CONTRAT_ID]);
            if((!$ligne[self::CSV_CONTRAT_ID] || !$contrat) && (trim($ligne[self::CSV_REF_ADRESSE] || trim($ligne[self::CSV_SOCIETE_ID])))){
                $societe = $this->sm->getRepository()->findOneBy(array('identifiantAdresseReprise' => $ligne[self::CSV_REF_ADRESSE]));
                if(!$societe && trim($ligne[self::CSV_SOCIETE_ID])){
                  $societe = $this->sm->getRepository()->findOneBy(array('identifiantReprise' => $ligne[self::CSV_SOCIETE_ID]));
                  if($societe){
                    $output->writeln(sprintf("<comment>Facture %s attachée à la société %s sans contrat </comment>", $ligne[self::CSV_FACTURE_ID],$societe->getId()));
                  }
                }
            }else{
              $societe = $contrat->getSociete();
            }
            if(!$societe){
              $output->writeln(sprintf("<error>La societe %s n'existe pas</error>", $ligneFacture[self::CSV_SOCIETE_ID]));
              return;
            }

            $coefficient = ($ligneFacture[self::CSV_IS_AVOIR]) ? -1 : 1;

            $mouvement = new Mouvement();
            $mouvement->setFacturable(boolval($ligneFacture[self::CSV_NUMERO_FACTURE]));
            $mouvement->setFacture(false);
            $mouvement->setPrixUnitaire($ligne[self::CSV_FACTURE_LIGNE_PUHT] * $coefficient);
            $mouvement->setQuantite($ligne[self::CSV_FACTURE_LIGNE_QTE]);

            if(!isset($ligne[self::CSV_FACTURE_TVA]) || !array_key_exists(self::CSV_FACTURE_TVA, $ligne)  || !$ligne[self::CSV_FACTURE_TVA]){
              $mouvement->setTauxTaxe(0.196);
              if((new \DateTime($ligneFacture[self::CSV_DATE_LIMITE_REGLEMENT]))->format("Ymd") > "20140101"){
                $mouvement->setTauxTaxe(0.2);
              }
            }elseif($ligne[self::CSV_FACTURE_TVA] == "100") {
              $tvaReduite = true;
              $mouvement->setTauxTaxe(0.055);
              if((new \DateTime($ligneFacture[self::CSV_DATE_LIMITE_REGLEMENT]))->format("Ymd") > "20140101"){
                $mouvement->setTauxTaxe(0.1);
              }
            } else{
              if($ligne[self::CSV_FACTURE_LIGNE_TVA] == 1) {
                  $tvaReduite = true;
                  $mouvement->setTauxTaxe(0.055);
              } elseif($ligne[self::CSV_FACTURE_LIGNE_TVA] == 2) {
                  $mouvement->setTauxTaxe(0.196);
              } elseif($ligne[self::CSV_FACTURE_LIGNE_TVA] == 3) {
                  $mouvement->setTauxTaxe(0.07);
              } elseif($ligne[self::CSV_FACTURE_LIGNE_TVA] == 4) {
                  $mouvement->setTauxTaxe(0.2);
              } elseif($ligne[self::CSV_FACTURE_LIGNE_TVA] == 5) {
                  $tvaReduite = true;
                  $mouvement->setTauxTaxe(0.1);
              }else{
                $mouvement->setTauxTaxe(0.196);
                if((new \DateTime($ligneFacture[self::CSV_DATE_LIMITE_REGLEMENT]))->format("Ymd") > "20140101"){
                  $mouvement->setTauxTaxe(0.2);
                }
                $output->writeln(sprintf("<comment>La ligne de facture %s a une TVA de %s determiné par default </comment>", $ligneFacture[self::CSV_FACTURE_ID],$mouvement->getTauxTaxe()));
              }
            }
            $mouvement->setIdentifiant($ligne[self::CSV_FACTURE_LIGNE_ID]);
            $mouvement->setSociete($societe);
            $mouvement->setLibelle(preg_replace('/^".*"$/', "", str_replace('#', "\n", $ligne[self::CSV_FACTURE_LIGNE_LIBELLE])));

            if ($ligne[self::CSV_FACTURE_LIGNE_PASSAGE]) {
                $passage = $this->dm->getRepository('AppBundle:Passage')->findOneByIdentifiantReprise($ligne[self::CSV_FACTURE_LIGNE_PASSAGE]);
                if (!$passage) {
                    if(($contrat && !$contrat->isEnAttenteAcceptation()) || !$contrat) {
                        $output->writeln(sprintf("<comment>Le passage d'identifiant de reprise %s n'est pas trouvé dans la base (%s)</comment>", $ligne[self::CSV_FACTURE_LIGNE_PASSAGE], $societe->getId()));
                    }
                } else {
                    $passage->setMouvementDeclenchable(true);
                    $passage->setMouvementDeclenche($mouvement->getFacturable());
                }
            }

            if($contrat) {
                $mouvement->setDocument($contrat);
                $contrat->setTvaReduite($tvaReduite);
            }

            if($contrat && !array_key_exists($contrat->getId(), $this->contratsNbFactures)) {
                $this->contratsNbFactures[$contrat->getId()] = 0;
            }

            if($contrat && !$ligneFacture[self::CSV_IS_AVOIR]) {
                $this->contratsNbFactures[$contrat->getId()] += 1;
            }

            if($contrat && $ligneFacture[self::CSV_IS_AVOIR]) {
                $this->contratsNbFactures[$contrat->getId()] -= 1;
            }

            if($mouvement->isFacturable() && $contrat) {
                $contrat->addMouvement($mouvement);

            }

            $mouvements[] = $mouvement;
        }

        if(!$ligneFacture[self::CSV_NUMERO_FACTURE]) {
            return;
        }

        $facture = $this->fm->create($societe, $mouvements, new \DateTime($ligneFacture[self::CSV_DATE_FACTURATION]));
        $facture->getDestinataire()->setNom($ligneFacture[self::CSV_NOM]);
        $adresse = "";
        if(trim($ligneFacture[self::CSV_NOM_DESTINATAIRE])) {
            $adresse .= $ligneFacture[self::CSV_NOM_DESTINATAIRE]."\n";
        }
        $adresse .= $ligneFacture[self::CSV_ADRESSE_1];
        if(trim($ligneFacture[self::CSV_ADRESSE_2])) {
            $adresse .= "\n".$ligneFacture[self::CSV_ADRESSE_2];
        }
        $facture->getDestinataire()->setAdresse($adresse);
        $facture->getDestinataire()->setCodePostal($ligneFacture[self::CSV_CODE_POSTAL]);
        $facture->getDestinataire()->setCommune($ligneFacture[self::CSV_COMMUNE]);

        $facture->setDateEmission(new \DateTime($ligneFacture[self::CSV_DATE_FACTURATION]));
        $facture->setDateLimitePaiement(new \DateTime($ligneFacture[self::CSV_DATE_LIMITE_REGLEMENT]));

        $facture->setIdentifiantReprise($ligneFacture[self::CSV_FACTURE_ID]);
        $facture->setNumeroFacture($ligneFacture[self::CSV_NUMERO_FACTURE]);
        $facture->setDescription(preg_replace('/^".*"$/', "", str_replace('#', "\n", $ligne[self::CSV_DESCRIPTION])));
        $facture->facturerMouvements();

        if($facture->getDescription() && count($facture->getLignes()) == 1) {
            $facture->getLignes()->first()->setDescription($facture->getDescription());
        }

        $this->dm->persist($facture);

        if($ligneFacture[self::CSV_REF_AVOIR]) {
            $this->facturesRedressees[$ligneFacture[self::CSV_REF_AVOIR]] = $facture->getId();
        }

        return $facture;
    }

}
