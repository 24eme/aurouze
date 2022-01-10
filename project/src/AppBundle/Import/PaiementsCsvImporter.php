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
use AppBundle\Manager\SocieteManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Behat\Transliterator\Transliterator;
use AppBundle\Document\Paiements;
use AppBundle\Document\Paiement;
use AppBundle\Import\CsvFile;

class PaiementsCsvImporter {

    protected $dm;
    protected $pm;
    protected $fm;
    protected $factures;
    protected $debug = false;

    const CSV_REGLEMENT_ID = 0;
    const CSV_SOCIETE_ID = 1;
    const CSV_PAIEMENT_DATE = 3;
    const CSV_LIBELLE = 5;
    const CSV_MONTANT = 6;
    const CSV_MOYEN_PAIEMENT = 8;

    const CSV_TYPE_REGLEMENT = 5;
    const CSV_REF_REMISE_CHEQUE = 0;
    const CSV_PAIEMENT_ID = 2;
    const CSV_FACTURE_ID = 3;

    public function __construct(DocumentManager $dm, PaiementsManager $pm, FactureManager $fm, SocieteManager $sm) {
        $this->dm = $dm;
        $this->pm = $pm;
        $this->fm = $fm;
        $this->sm = $sm;
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

            $societe = $this->sm->getRepository()->findOneByIdentifiantReprise($data[self::CSV_SOCIETE_ID]);
            if (!$societe) {
                $output->writeln(sprintf("<error>La societe n'a pas été trouvé %s </error>", $data[self::CSV_SOCIETE_ID]));
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

            $data[self::CSV_LIBELLE] = str_replace(array(" ET ", "->"), "/", $data[self::CSV_LIBELLE]);
            $numerosFacture = preg_split("|/|", preg_replace("|[^0-9/]*|", "", $data[self::CSV_LIBELLE]));
            $montant = $data[self::CSV_MONTANT]*1;
            $paiement = null;
            $facturesNonTrouves = array();
            foreach($numerosFacture as $numeroFacture) {
                $facture = $this->fm->getRepository()->findOneBy(array('numeroFacture' => $numeroFacture, 'societe' =>
                $societe->getId()));
                if(!$facture) {
                    $facturesNonTrouves[] = $numeroFacture;
                    continue;
                }
                $paiement = $this->createPaiement($data, $facture, $facture->getMontantTTC());
                $paiements->addPaiement($paiement);
                $montant -= $paiement->getMontant();
            }

            if($paiement && !count($facturesNonTrouves) && round($montant, 2) != 0) {
                //$output->writeln(sprintf("<comment>Le paiement est différent de celui des factures trouvés : %s</comment>", $data[self::CSV_REGLEMENT_ID].";".$data[self::CSV_LIBELLE].";".round($montant, 2)." € restant"));
                $paiement->setMontant(round($paiement->getMontant() + round($montant, 2), 2));
                $facture = $this->factures[$paiement->getFacture()->getId()];
                $facture->setMontantPaye($facture->getMontantPaye() + round($montant, 2));
                $facture->updateRestantAPayer();
                $montant = 0;
            }

            if(round($montant, 2) != 0) {
                $facture = $this->findFacture($societe, $data, round($montant, 2));
                if($facture) {
                    $paiement = $this->createPaiement($data, $facture, round($montant, 2));
                    $paiements->addPaiement($paiement);
                    $montant = 0;
                }
            }

            if(round($montant, 2) != 0 && count($facturesNonTrouves) > 0) {
                foreach($facturesNonTrouves as $numeroFacture) {
                    $facture = $this->fm->getRepository()->findOneBy(array('numeroFacture' => $numeroFacture));
                    if(!$facture) {
                        continue;
                    }
                    $fCache = $this->dm->getUnitOfWork()->tryGetById($facture->getId(), $this->pm->getRepository()->getClassMetadata());
                    if($fCache) {
                        $facture = $fCache;
                    }
                    $paiement = $this->createPaiement($data, $facture, $facture->getMontantTTC());
                    $paiements->addPaiement($paiement);
                    $montant -= $paiement->getMontant();
                }
            }

            if(round($montant, 2) != 0) {
                $factures = $this->fm->getRepository()->findBy(array('societe' => $societe->getId()));
                $facturePrecedente = null;
                $facture = null;
                foreach($factures as $facture) {
                    $datePaiement = new \DateTime($data[self::CSV_PAIEMENT_DATE]);
                    if($facture->getDateFacturation()->format('Y-m-d') > $datePaiement->format('Y-m-d')) {
                        break;
                    }
                    $facturePrecedente = $facture;
                }

                if($facturePrecedente) {
                    $facture = $facturePrecedente;
                }

                if($facture) {
                    $paiement = $this->createPaiement($data, $facture, round($montant, 2));
                    $paiements->addPaiement($paiement);
                    $montant = 0;
                }
            }

            if(round($montant, 2) != 0) {
                $output->writeln(sprintf("<comment>Facture non trouvé pour ce montant : %s</comment>", $data[self::CSV_REGLEMENT_ID].";".$data[self::CSV_PAIEMENT_DATE].";".$data[self::CSV_LIBELLE].";".round($montant, 2)." € restant"));
                echo implode(";", $data)."\n";
            }

            $i++;

            if ($i >= 1000) {
                $this->dm->flush();
                foreach($this->factures as $f) {
                    $f->updateMontantPaye();
                    $f->updateRestantAPayer();
                }
                $this->dm->flush();
                $this->dm->clear();
                $this->factures = array();
                $i = 0;
            }
        }
        $this->dm->flush();
        foreach($this->factures as $f) {
            $f->updateMontantPaye();
            $f->updateRestantAPayer();
        }
        $this->dm->flush();
        $this->dm->clear();

        foreach($this->sm->getRepository()->findAll() as $societe) {
            $solde = $this->fm->getSolde($societe);
            if($solde != 0) {

                continue;
            }
            $factures = $this->fm->getRepository()->findBy(array('societe' => $societe->getId()));
            foreach($factures as $facture) {
                if($facture->isCloture()) {
                    continue;
                }
                $facture->cloturer();
            }
        }
        $this->dm->flush();
        $this->dm->clear();
    }

    protected function findFacture($societe, $data, $montant) {
        $factures = $this->fm->getRepository()->findBy(array('societe' => $societe->getId()));
        foreach($factures as $facture) {
            $fCache = $this->dm->getUnitOfWork()->tryGetById($facture->getId(), $this->pm->getRepository()->getClassMetadata());
            if($fCache) {
                $facture = $fCache;
            }
            if($montant > $facture->getMontantAPayer()) {
                continue;
            }

            return $facture;
        }

        return null;
    }

    protected function createPaiement($data, $facture, $montant) {
        $fCache = $this->dm->getUnitOfWork()->tryGetById($facture->getId(), $this->pm->getRepository()->getClassMetadata());
        if($fCache) {
            $facture = $fCache;
        }

        $paiement = new Paiement();

        $paiement->setIdentifiantReprise($data[self::CSV_REGLEMENT_ID]);
        $paiement->setDatePaiement(new \DateTime($data[self::CSV_PAIEMENT_DATE]));
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
        $paiement->setFacture($facture);
        $paiement->setMontant($montant);
        $facture->setMontantPaye($facture->getMontantPaye() + $paiement->getMontant());
        $facture->updateRestantAPayer();

        $this->factures[$facture->getId()] = $facture;

        return $paiement;
    }
}
