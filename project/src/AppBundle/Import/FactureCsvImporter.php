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
use AppBundle\Manager\SocieteManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Import\CsvFile;
use AppBundle\Document\Facture;
use AppBundle\Document\LigneFacturable;

class FactureCsvImporter {

    protected $dm;
    protected $sm;
    protected $fm;

    const CSV_SOCIETE_ID = 3;
    const CSV_FACTURE_ID = 5;
    const CSV_DATE = 11;
    const CSV_LIBELLE = 22;
    const CSV_QUANTITE = 23;
    const CSV_PRIX_UNITAIRE = 34;
    const CSV_TAUX_TAXE = 36;
    const CSV_TEXT = 114;

    public function __construct(DocumentManager $dm, FactureManager $fm, SocieteManager $sm) {
        $this->dm = $dm;
        $this->fm = $fm;
        $this->sm = $sm;
    }

    public function import($file, OutputInterface $output) {
        $csvFile = new CsvFile($file);

        $csv = $csvFile->getCsv();

        $i = 0;

        $lignes = array();
        $currentIdFacture = null;

        foreach ($csv as $data) {
            if(is_null($currentIdFacture)) {
                $currentIdFacture = $data[self::CSV_FACTURE_ID];
            }

            if($data[self::CSV_QUANTITE]*1 == 0) {
                continue;
            }

            if($currentIdFacture == $data[self::CSV_FACTURE_ID]) {

                $lignes[] = $data;
                continue;
            }

            $facture = $this->importFacture($lignes, $output);

            $i++;

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
    }

    public function importFacture($lignes, $output) {
        if(!count($lignes)) {

            return;
        }

        $societe = $this->sm->getRepository()->findOneByIdentifiantReprise($lignes[0][self::CSV_SOCIETE_ID]);
        if (!$societe) {
            $output->writeln(sprintf("<comment>La societe n'a pas été trouvé %s </comment>", $lignes[0][self::CSV_SOCIETE_ID]));
            return;
        }

        $facture = $this->fm->createVierge($societe);
        $facture->setNumeroFacture($lignes[0][self::CSV_FACTURE_ID]);
        $facture->setIdentifiantReprise($lignes[0][self::CSV_FACTURE_ID]);
        $facture->setDateEmission(new \DateTime($lignes[0][self::CSV_DATE]));
        $facture->setDateFacturation(new \DateTime($lignes[0][self::CSV_DATE]));
        $facture->getDateLimitePaiement();

        foreach($lignes as $ligne) {
            $factureLigne = new LigneFacturable();
            $factureLigne->setLibelle($ligne[self::CSV_LIBELLE]);
            $factureLigne->setQuantite($ligne[self::CSV_QUANTITE]);
            $factureLigne->setPrixUnitaire($ligne[self::CSV_PRIX_UNITAIRE]);
            $factureLigne->setTauxTaxe($ligne[self::CSV_TAUX_TAXE]/100);
            if(isset($ligne[self::CSV_TEXT])) {
                $factureLigne->setLibelle($factureLigne->getLibelle()." - ".$ligne[self::CSV_TEXT]);
            }

            $facture->addLigne($factureLigne);
        }



        //$facture->setDescription(preg_replace('/^".*"$/', "", str_replace('#', "\n", $ligne[self::CSV_DESCRIPTION])));
        //$facture->facturerMouvements();
        /*if($facture->getDescription() && count($facture->getLignes()) == 1) {
            $facture->getLignes()->first()->setDescription($facture->getDescription());
        }*/

        $facture->update();

        try {
            $facture->getTva();
            $facture->setMontantTaxe(round($facture->getMontantHT() * $facture->getTva(), 2));
            $facture->setMontantTTC(round($facture->getMontantHT() + $facture->getMontantTaxe(), 2));
        } catch(\Exception $e) {
        }

        $facture->updateRestantAPayer();
        $this->dm->persist($facture);

        return $facture;
    }

}
