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
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Manager\FactureManager;
use AppBundle\Manager\PaiementsManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Behat\Transliterator\Transliterator;
use AppBundle\Document\Paiements;
use AppBundle\Document\Paiement;
use AppBundle\Import\CsvFile;

class PaiementsCsvImporter {

    protected $dm;
    protected $pm;
    protected $fm;
    protected $debug = false;

    const CSV_REGLEMENT_ID = 0;
    const CSV_PAIEMENT_DATE = 3;
    const CSV_LIBELLE = 5;
    const CSV_MONTANT = 6;
    const CSV_MOYEN_PAIEMENT = 8;

    const CSV_TYPE_REGLEMENT = 5;
    const CSV_REF_REMISE_CHEQUE = 0;
    const CSV_PAIEMENT_ID = 2;
    const CSV_FACTURE_ID = 3;

    public function __construct(DocumentManager $dm, PaiementsManager $pm, FactureManager $fm) {
        $this->dm = $dm;
        $this->pm = $pm;
        $this->fm = $fm;
    }


    public function import($file, OutputInterface $output) {
        $csvFile = new CsvFile($file);

        $csv = $csvFile->getCsv();

        $i = 0;
        $factures = array();
        foreach ($csv as $data) {
            if(!preg_match("/^[0-9]+$/", $data[self::CSV_REGLEMENT_ID])) {
                continue;
            }
            $id = 'PAIEMENTS-' . (new \DateTime($data[self::CSV_PAIEMENT_DATE]))->format('Ymd');
            $paiements = $this->pm->getRepository()->findOneById($id);
            if (!$paiements) {
                $paiements = $this->dm->getUnitOfWork()->tryGetById($id, $this->pm->getRepository()->getClassMetadata());
            }
            if (!$paiements) {
                $paiements = $this->pm->createByDateCreation(new \DateTime($data[self::CSV_PAIEMENT_DATE]));
                $this->dm->persist($paiements);
            }

            $paiement = new Paiement();

            $paiement->setIdentifiantReprise($data[self::CSV_REGLEMENT_ID]);
            $paiement->setDatePaiement(new \DateTime($data[self::CSV_PAIEMENT_DATE]));
            $paiement->setMontant($data[self::CSV_MONTANT]);
            $paiement->setLibelle($data[self::CSV_LIBELLE]);
            $moyens = array(
                "1" => PaiementsManager::MOYEN_PAIEMENT_CHEQUE,
                "2" => PaiementsManager::MOYEN_PAIEMENT_VIREMENT,
                "3" => PaiementsManager::MOYEN_PAIEMENT_TRAITE,
                "5" => PaiementsManager::MOYEN_PAIEMENT_CHEQUE
            );
            $paiement->setMoyenPaiement($moyens[$data[self::CSV_MOYEN_PAIEMENT]]);
            $paiement->setTypeReglement(PaiementsManager::TYPE_REGLEMENT_FACTURE);
            $paiement->setVersementComptable(true);

            $facture = $this->fm->getRepository()->findOneBy(array('numeroFacture' => preg_replace("/[^[0-9]]*/", "", $data[self::CSV_LIBELLE])));

            if($facture) {
                $paiement->setFacture($facture);
                $factures[$facture->getId()] = $facture;
            }

            $paiements->addPaiement($paiement);

            $i++;

            if ($i >= 1000) {
                $this->dm->flush();
                $this->dm->clear();
                foreach($factures as $f) {
                    $f->updateMontantPaye();
                    $f->updateRestantAPayer();
                }
                $this->dm->flush();
                $this->dm->clear();
                $factures = array();
                $i = 0;
            }
        }
        $this->dm->flush();
        $this->dm->clear();
        foreach($factures as $f) {
            $f->updateMontantPaye();
            $f->updateRestantAPayer();
        }
        $this->dm->flush();
        $this->dm->clear();
    }

}
