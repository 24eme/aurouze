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
use AppBundle\Document\Etablissement;
use AppBundle\Document\Prestation;
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
    const CSV_NOM = 1;
    const CSV_ID_CONTRAT = 2;
    const CSV_NOMENCLATURE = 3;
    const CSV_ADRESSE_NUMERO = 4;
    const CSV_ADRESSE_RUE = 5;
    const CSV_ADRESSE_NOM = 6;
    const CSV_ADRESSE_CODE_POSTAL = 7;
    const CSV_ADRESSE_COMMUNE = 8;
    const CSV_PRESTATION = 9;
    const CSV_PRIXHT = 10;
    const CSV_PRIXHT_UNITAIRE = 11;
    const CSV_TVA = 12;
    const CSV_NB_PASSAGES = 13;
    const CSV_PASSAGE_JANVIER = 14;

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

        $societeMere = $this->dm->getRepository('AppBundle:Societe')->findOneByRaisonSociale("AHRB");
        $commercial = new Compte($societeMere);
        $commercial->setNom("Commercial");
        $commercial->setPrenom("Commercial");
        $this->dm->persist($commercial);
        $this->dm->flush();

        $csv = $csvFile->getCsv();

        $i = 0;
        $cptTotal = 0;
        $etablissements = array();
        foreach ($csv as $data) {
            $societe = $this->findSociete($data);
            if(!$societe) {
                $output->writeln(sprintf("<error>La societe %s n'a pas été trouvé</error>", $data[self::CSV_ID_SOCIETE]));
                continue;
            }
            $keyEtablissement = $data[self::CSV_NOM].trim($data[self::CSV_ADRESSE_NUMERO])." ".trim($data[self::CSV_ADRESSE_RUE])." ".trim($data[self::CSV_ADRESSE_NOM]).trim($data[self::CSV_ADRESSE_CODE_POSTAL]);
            if(!isset($etablissements[$keyEtablissement])) {
                $etablissement = new Etablissement();
                $etablissement->setSociete($societe);
                $etablissement->setNom($data[self::CSV_NOM]);
                $etablissement->setAdresse($etablissement->getAdresse());
                $etablissement->getAdresse()->setAdresse(trim($data[self::CSV_ADRESSE_NUMERO])." ".trim($data[self::CSV_ADRESSE_RUE])." ".trim($data[self::CSV_ADRESSE_NOM]));
                $etablissement->getAdresse()->setCodePostal(trim($data[self::CSV_ADRESSE_CODE_POSTAL]));
                $etablissement->getAdresse()->setCommune(trim($data[self::CSV_ADRESSE_COMMUNE]));
                $etablissements[$keyEtablissement] = $etablissement;
                $this->dm->persist($etablissement);
            } else {
                $etablissement = $etablissements[$keyEtablissement];
            }

            $contrat = new Contrat();
            $contrat->setSociete($societe);
            $contrat->addEtablissement($etablissement);
            $contrat->setDateAcceptation(new \DateTime('2022-01-01'));
            $contrat->setDateCreation(new \DateTime('2022-01-01'));
            $contrat->setDateCreationAuto(new \DateTime('2022-01-01'));
            $contrat->setDateDebut(new \DateTime('2022-01-01'));
            $contrat->setDuree(12);
            $dateFin = clone $contrat->getDateDebut();
            $dateFin->modify("+ " . $contrat->getDuree() . " month - 1 second");
            $contrat->setDateFin($dateFin);
            $contrat->setStatut(ContratManager::STATUT_EN_COURS);
            $contrat->setTypeContrat(ContratManager::TYPE_CONTRAT_RECONDUCTION_TACITE);
            $contrat->setNomenclature(str_replace('#', "\n", $data[self::CSV_NOMENCLATURE]));
            $contrat->setPrixHt($data[self::CSV_PRIXHT_UNITAIRE] * $data[self::CSV_NB_PASSAGES]);
            if(!$contrat->getPrixHt()) {
                $contrat->setPrixHt($data[self::CSV_PRIXHT]*1);
            }
            if(round($data[self::CSV_PRIXHT_UNITAIRE] * $data[self::CSV_NB_PASSAGES],2) != $data[self::CSV_PRIXHT]*1) {
                $output->writeln(sprintf("Le prix unitaire et total ne concorde pas %s contre %s : contrat n°%s", $data[self::CSV_PRIXHT_UNITAIRE] * $data[self::CSV_NB_PASSAGES], $data[self::CSV_PRIXHT], $data[self::CSV_ID_CONTRAT]));
            }
            if(!$contrat->getPrixHt()) {
                $output->writeln(sprintf("<error>Pas de prix pour ce contrat : contrat n°%s</error>", $data[self::CSV_ID_CONTRAT]));
            }
            $contrat->setIdentifiantReprise($data[self::CSV_ID_CONTRAT]);
            $contrat->setNumeroArchive($data[self::CSV_ID_CONTRAT]);
            $contrat->setReconduit(false);
            $contrat->setNomenclature(str_replace('#', "\n", $data[self::CSV_NOMENCLATURE]));
            if($data[self::CSV_TVA] == 10) {
                $contrat->setTvaReduite(true);
            }
            $contrat->setNbFactures((int) $data[self::CSV_NB_PASSAGES] * 1);
            $contrat->setFrequencePaiement(ContratManager::FREQUENCE_30J);
            $prestation = new Prestation();
            $prestation->setIdentifiant($data[self::CSV_PRESTATION]);
            $prestation->setNom($data[self::CSV_PRESTATION]);
            $prestation->setNbPassages((int) $data[self::CSV_NB_PASSAGES] * 1);
            $contrat->addPrestation($prestation);

            $contrat->setNbPassages((int) $data[self::CSV_NB_PASSAGES] * 1);
            $contrat->setCommercial($commercial);

            $this->dm->persist($contrat);
            $this->dm->flush();
            $this->cm->generateAllPassagesForContrat($contrat);

            $datesPassages = array();
            for($i=0; $i <= 11;$i++) {
                if(trim($data[self::CSV_PASSAGE_JANVIER + $i])) {
                    $datesPassages[] = new \DateTime("01-".($i+1)."-2022");
                }
            }
            foreach($contrat->getPassages($etablissement) as $passage) {
                if(!array_key_exists(0, $datesPassages)) {
                    $output->writeln(sprintf("<error>Il y a moins de date que de passages : contrat n°%s</error>", $data[self::CSV_ID_CONTRAT]));
                }
                $passage->setDatePrevision($datesPassages[0]);
                $passage->setDateDebut($passage->getDatePrevision());
                unset($datesPassages[0]);
                $datesPassages = array_values($datesPassages);
            }

            if(count($datesPassages) > 0) {
                $output->writeln(sprintf("<error>Il y a plus de dates que de passages : contrat n°%s</error>", $data[self::CSV_ID_CONTRAT]));
            }
            $this->dm->flush();

            /*if ($data[self::CSV_DATE_RESILIATION]) {
                $contrat->setDateResiliation(new \DateTime($data[self::CSV_DATE_RESILIATION]));
                $contrat->setStatut(ContratManager::STATUT_RESILIE);
            }*/
        }
        $this->dm->flush();
    }

}
