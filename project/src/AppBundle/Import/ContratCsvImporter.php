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
use AppBundle\Document\UserInfos;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Manager\ContratManager;
use AppBundle\Manager\PassageManager;
use AppBundle\Manager\EtablissementManager;
use AppBundle\Manager\UserManager;
use AppBundle\Manager\SocieteManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Import\CsvFile;
use Symfony\Component\Console\Helper\ProgressBar;

class ContratCsvImporter {

    protected $dm;
    protected $cm;
    protected $pm;
    protected $em;
    protected $sm;
    protected $um;

    const CSV_ID_CONTRAT = 0;
    const CSV_ID_ETABLISSEMENT = 1;
    const CSV_ID_SOCIETE = 2;
    const CSV_ID_COMMERCIAL = 3;
    const CSV_ID_TECHNICIEN = 4;
    const CSV_TYPE_CONTRAT = 5;
    const CSV_TYPE_PRESTATION = 6;
    const CSV_NOMENCLATURE = 7;
    const CSV_DATE_CREATION = 8;
    const CSV_DATE_DEBUT = 9;
    const CSV_DUREE = 10;
    const CSV_GARANTIE = 11;
    const CSV_PRIXHT = 12;
    const CSV_ARCHIVAGE = 13;

    public function __construct(DocumentManager $dm, ContratManager $cm, PassageManager $pm, EtablissementManager $em, SocieteManager $sm, UserManager $um) {
        $this->dm = $dm;
        $this->cm = $cm;
        $this->pm = $pm;
        $this->em = $em;
        $this->sm = $sm;
        $this->um = $um;
    }

    public function import($file, OutputInterface $output) {
        $csvFile = new CsvFile($file);


        $progress = new ProgressBar($output, 100);
        $progress->start();

        $csv = $csvFile->getCsv();

        $i = 0;
        $cptTotal = 0;
        foreach ($csv as $data) {
            if ($data[self::CSV_ID_ETABLISSEMENT] == "000000") {
                continue;
            }
            $societe = $this->sm->getRepository()->findOneBy(array('identifiantReprise' => $data[self::CSV_ID_SOCIETE]));

            if (!$societe) {
                $output->writeln(sprintf("<error>La societe %s n'existe pas</error>", $data[self::CSV_ID_SOCIETE]));
                continue;
            }

            $contrat = $this->cm->getRepository()->findOneBy(array('identifiantReprise', $data[self::CSV_ID_CONTRAT]));

            if(!$contrat) {
                $contrat = new Contrat();
            }

            $contrat->setDateCreation(new \DateTime($data[self::CSV_DATE_CREATION]));
            $contrat->setSociete($societe);

            if ($data[self::CSV_DATE_DEBUT]) {
                $contrat->setDateDebut(new \DateTime($data[self::CSV_DATE_DEBUT]));
            }

            if (!preg_match("/^[0-9+]+$/", $data[self::CSV_DUREE])) {
                $output->writeln(sprintf("<error>La durée du contrat %s n'est pas correct : %s</error>", $data[self::CSV_ID_CONTRAT], $data[self::CSV_DUREE]));
                continue;
            }

            $contrat->setDuree($data[self::CSV_DUREE]);
            $contrat->setDureeGarantie($data[self::CSV_GARANTIE]);
            $contrat->setDateDebut(new \DateTime($data[self::CSV_DATE_DEBUT]));
            $dateFin = clone $contrat->getDateDebut();
            $dateFin->modify("+ " . $contrat->getDuree() . " month");
            $contrat->setDateFin($dateFin);
            $contrat->setNomenclature(str_replace('\n', "\n", $data[self::CSV_NOMENCLATURE]));
            $contrat->setPrixHt($data[self::CSV_PRIXHT]);
            $contrat->setIdentifiantReprise($data[self::CSV_ID_CONTRAT]);
            $contrat->setNumeroArchive($data[self::CSV_ARCHIVAGE]);

            $this->dm->persist($contrat);
            $i++;
            $cptTotal++;
            if ($cptTotal % (count($csv) / 100) == 0) {
                $progress->advance();
            }

            if ($i >= 1000) {
                $this->dm->flush();
                $i = 0;
            }
        }

        $this->dm->flush();
        $progress->finish();
    }

}
