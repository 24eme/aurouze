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
use AppBundle\Document\Contrat;
use AppBundle\Document\CompteInfos;
use AppBundle\Document\Produit;
use AppBundle\Document\Compte;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Manager\ContratManager;
use AppBundle\Manager\PassageManager;
use AppBundle\Manager\EtablissementManager;
use AppBundle\Manager\CompteManager;
use AppBundle\Manager\SocieteManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Behat\Transliterator\Transliterator;
use AppBundle\Import\CsvFile;
use Symfony\Component\Console\Helper\ProgressBar;

class ContratCsvImporter {

    protected $dm;
    protected $cm;
    protected $pm;
    protected $em;
    protected $sm;
    protected $um;
    protected $societes;

    //const CSV_ID_CONTRAT = 0;
    const CSV_ID_SOCIETE = 0;
    const CSV_ADRESSE_NUMERO = 4;
    const CSV_ADRESSE_RUE = 5;
    const CSV_ADRESSE_NOM = 6;
    const CSV_ADRESSE_CODE_POSTAL = 7;
    const CSV_ADRESSE_COMMUNE = 8;
    const CSV_ID_COMMERCIAL = 2;
    const CSV_ID_TECHNICIEN = 3;
    const CSV_TYPE_CONTRAT = 4;
    const CSV_TYPE_PRESTATION = 5;
    const CSV_NOMENCLATURE = 6;
    const CSV_DATE_CREATION = 7;
    const CSV_DATE_ACCEPTATION = 8;
    const CSV_DATE_DEBUT = 9;
    const CSV_DUREE = 10;
    const CSV_GARANTIE = 11;

    const CSV_PRIXHT = 12;
    const CSV_ARCHIVAGE = 13;
    const CSV_TVA_REDUITE = 14;
    const CSV_DATE_RESILIATION = 15;
    const CSV_ID_SOCIETEOLDADRESSEID = 16;
    const CSV_PRODUITS = 17;
    const CSV_NOM_COMMERCIAL = 18;
    const CSV_NOM_TECHNICIEN = 19;

    public function __construct(DocumentManager $dm, ContratManager $cm, PassageManager $pm, EtablissementManager $em, SocieteManager $sm, CompteManager $um) {
        $this->dm = $dm;
        $this->cm = $cm;
        $this->pm = $pm;
        $this->em = $em;
        $this->sm = $sm;
        $this->um = $um;
        $this->societes = $this->sm->getRepository()->findAll();
    }

    public function findSociete($data) {
        $replaceWord = array(" ", "mme.", "m.", "mr", "mlle", "mme", "madame", "monsieur", "mademoiselle", ".", "-", "'", '"', 'earl', 'sarl', ',', '&', 'sas', 'ets', 'sdc');
        $raisonSocialeData = str_replace($replaceWord, "", strtolower(preg_replace("/\(.+\)/", "", $data[self::CSV_ID_SOCIETE])));
        $adresseData = str_replace($replaceWord, "", strtolower($data[self::CSV_ADRESSE_NUMERO].$data[self::CSV_ADRESSE_RUE].$data[self::CSV_ADRESSE_NOM].$data[self::CSV_ADRESSE_CODE_POSTAL].$data[self::CSV_ADRESSE_COMMUNE]));
        $adresseShortData = str_replace($replaceWord, "", strtolower($data[self::CSV_ADRESSE_CODE_POSTAL].$data[self::CSV_ADRESSE_COMMUNE]));
        $societes = $this->societes;
        $societesFind = array();
        foreach($societes as $societe) {
            $codeComptableSociete = str_replace($replaceWord, "", strtolower(preg_replace("/\(.+\)/", "", $societe->getCodeComptable())));

            if($raisonSocialeData == $codeComptableSociete) {
                $societesFind[] = $societe;
            }
        }

        if(count($societesFind) == 1) {

            return current($societesFind);
        }

        if(count($societesFind) > 1) {
            $societes = $societesFind;
        }

        $societesFind = array();
        foreach($societes as $societe) {
            $raisonSocialeSociete = str_replace($replaceWord, "", strtolower(preg_replace("/\(.+\)/", "", $societe->getRaisonSociale())));

            if($raisonSocialeData == $raisonSocialeSociete) {
                $societesFind[] = $societe;
            }
        }

        if(count($societesFind) == 1) {

            return current($societesFind);
        }

        if(count($societesFind) > 1) {
            $societes = $societesFind;
        }

        $societesFind = array();
        foreach($societes as $societe) {
            if(trim($data[self::CSV_ADRESSE_CODE_POSTAL]) == trim($societe->getAdresse()->getCodePostal())) {
                $societesFind[] = $societe;
            }
        }

        if(count($societesFind) == 1) {

            return current($societesFind);
        }

        if(count($societesFind) > 1) {
            $societes = $societesFind;
        }

        $societesFind = array();
        foreach($societes as $societe) {
            $adresseShortSociete = str_replace($replaceWord, "", strtolower($societe->getAdresse()->getCodePostal().$societe->getAdresse()->getCommune()));

            if($adresseShortData == $adresseShortSociete) {
                $societesFind[] = $societe;
            }
        }

        if(count($societesFind) == 1) {

            return current($societesFind);
        }

        if(count($societesFind) > 1) {
            $societes = $societesFind;
        }

        $societesFind = array();
        foreach($societes as $societe) {
            $adresseSociete = str_replace($replaceWord, "", strtolower($societe->getAdresse()->getAdresse().$societe->getAdresse()->getCodePostal().$societe->getAdresse()->getCommune()));

            if($adresseData == $adresseSociete) {
                $societesFind[] = $societe;
            }
        }

        if(count($societesFind) == 1) {

            return current($societesFind);
        }

        return null;
    }

    public function import($file, OutputInterface $output) {
        $csvFile = new CsvFile($file,1,true);

        // $progress = new ProgressBar($output, 100);
        // $progress->start();

        $csv = $csvFile->getCsv();
        //$configuration = $this->dm->getRepository('AppBundle:Configuration')->findConfiguration();
        //$produitsArray = $configuration->getProduitsArray();


        $i = 0;
        $cptTotal = 0;
        foreach ($csv as $data) {
            $societe = $this->findSociete($data);
            if(!$societe) {
                $output->writeln(sprintf("<error>La societe %s n'a pas été trouvé</error>", $data[self::CSV_ID_SOCIETE]));
                continue;
            }
            //echo $data[self::CSV_ID_SOCIETE].":".count($societes)."\n";
            continue;

            if(trim($data[self::CSV_ID_SOCIETEOLDADRESSEID])){
              $societe = $this->sm->getRepository()->findOneBy(array('identifiantAdresseReprise' => $data[self::CSV_ID_SOCIETEOLDADRESSEID]));
            }

            $contrat = $this->cm->getRepository()->findOneBy(array('identifiantReprise', $data[self::CSV_ID_CONTRAT]));

            if (!$contrat) {
                $contrat = new Contrat();
            } else {
                $output->writeln(sprintf("<error>Le contrat : %s existe déjà en base?</error>", $data[self::CSV_ID_CONTRAT]));
            }

            $contrat->setDateCreation(new \DateTime($data[self::CSV_DATE_CREATION]));
            $contrat->setDateCreationAuto(new \DateTime($data[self::CSV_DATE_CREATION]));
            $contrat->setSociete($societe);
            $contrat->setTvaReduite(boolval($data[self::CSV_TVA_REDUITE]));
            if(isset(ContratManager::$types_contrat_import_index[$data[self::CSV_TYPE_CONTRAT]])){
              $type_contrat = ContratManager::$types_contrat_import_index[$data[self::CSV_TYPE_CONTRAT]];
            }else{
              $output->writeln(sprintf("\n<comment> %s : Le type de contrat %s n'existe pas dans la liste des types de contrats :[ %s ]</comment>",$data[self::CSV_ID_CONTRAT], $data[self::CSV_TYPE_CONTRAT], implode(",",ContratManager::$types_contrat_import_index)));
              $type_contrat = ContratManager::TYPE_CONTRAT_PONCTUEL;
            }
            $contrat->setTypeContrat($type_contrat);
            $contrat->setReconduit(false);

            if ($data[self::CSV_DATE_DEBUT]) {
                $contrat->setDateDebut(new \DateTime($data[self::CSV_DATE_DEBUT]));
            }

            if($data[self::CSV_DATE_ACCEPTATION]){
                $contrat->setDateAcceptation(new \DateTime($data[self::CSV_DATE_ACCEPTATION]));
                $contrat->setStatut(ContratManager::STATUT_EN_COURS);
            }else{
                $contrat->setStatut(ContratManager::STATUT_EN_ATTENTE_ACCEPTATION);
            }

            if (!preg_match("/^[0-9+]+$/", $data[self::CSV_DUREE])) {
                $output->writeln(sprintf("\n<comment>La durée du contrat %s n'est pas correct : %s</comment>", $data[self::CSV_ID_CONTRAT], $data[self::CSV_DUREE]));
                $contrat->setDuree(1);

            }else{
              $contrat->setDuree($data[self::CSV_DUREE]);
            }

            $contrat->setDureeGarantie($data[self::CSV_GARANTIE]);
            if($contrat->getDateDebut()){
              $dateFin = clone $contrat->getDateDebut();
              $dateFin->modify("+ " . $contrat->getDuree() . " month");
              $contrat->setDateFin($dateFin);
            }
            $contrat->setNomenclature(str_replace('#', "\n", $data[self::CSV_NOMENCLATURE]));
            $contrat->setPrixHt($data[self::CSV_PRIXHT]);
            $contrat->setIdentifiantReprise($data[self::CSV_ID_CONTRAT]);
            if(is_integer($data[self::CSV_ARCHIVAGE])) {
                $contrat->setNumeroArchive(0);
            }
            $contrat->setNumeroArchive($data[self::CSV_ARCHIVAGE]);

            if ($data[self::CSV_ID_COMMERCIAL]) {
                $commercial = $this->um->getRepository()->findOneByIdentifiantReprise($data[self::CSV_ID_COMMERCIAL]);
                if ($commercial) {
                    $contrat->setCommercial($commercial);
                }
            }

            if ($data[self::CSV_ID_TECHNICIEN]) {
                $technicien = $this->um->getRepository()->findOneByIdentifiantReprise($data[self::CSV_ID_TECHNICIEN]);
               if ($technicien) {
                    $contrat->setTechnicien($technicien);
                }
            }

            if ($data[self::CSV_DATE_RESILIATION]) {
                $contrat->setDateResiliation(new \DateTime($data[self::CSV_DATE_RESILIATION]));
                $contrat->setStatut(ContratManager::STATUT_RESILIE);
            }

            $produits = explode('#', $data[self::CSV_PRODUITS]);
            foreach ($produits as $produitStr) {
                if ($produitStr) {
                    $produitdetail = explode('~', $produitStr);
                    $produitQte = 0;
                    $produitLib = $produitdetail[0];
                    if (count($produitdetail) > 1) {
                        $produitQte = $produitdetail[1];
                    }
                    if ($produitLib) {
                        $produitToAdd = clone $produitsArray[strtoupper(Transliterator::urlize($produitLib))];
                        $produitToAdd->setNbTotalContrat(0);
                        $produitToAdd->setNbTotalContrat($produitQte);
                        $contrat->addProduit($produitToAdd);
                    }
                }
            }
            $this->dm->persist($contrat);
            $i++;
            $cptTotal++;
            if ($cptTotal % (count($csv) / 100) == 0) {
                $progress->advance();
            }
            if ($i >= 1000) {
                $this->dm->flush();
                $this->dm->clear();
                gc_collect_cycles();
                $i = 0;
          }
        }

        $this->dm->flush();
        //$progress->finish();
    }

}
