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
use AppBundle\Document\Societe as Societe;
use AppBundle\Document\Adresse as Adresse;
use AppBundle\Manager\EtablissementManager as EtablissementManager;

class SocieteCsvImport extends CsvFile {

    protected $dm;

    const CSV_ID_SOCIETE = 0;
    const CSV_SOUS_TRAITANT = 1;
    const CSV_RAISON_SOCIALE = 2;
    const CSV_COMMENTAIRE = 5;
    const CSV_TYPE_SOCIETE = 6;
    const CSV_CODE_COMPTABLE = 10;

    public function setManager($dm) {
        $this->dm = $dm;
    }

    public function import() {
        $this->errors = array();
        $csv = $this->getCsv();


        foreach ($csv as $data) {
            $societe = $this->createFromImport($data);
            $this->dm->persist($societe);
            $this->dm->flush();
        }
    }

    public function createFromImport($ligne) {

        $societe = new Societe();

        $societe->setIdentifiant(sprintf("%06d", $ligne[self::CSV_ID_SOCIETE]));

        $societe->setId();

        $societe->setRaisonSociale($ligne[self::CSV_RAISON_SOCIALE]);
        $societe->setCodeComptable($ligne[self::CSV_CODE_COMPTABLE]);
        $societe->setCommentaire($ligne[self::CSV_COMMENTAIRE]);
        $societe->setSousTraitant(!($ligne[self::CSV_SOUS_TRAITANT] - 1 ));
        if ($ligne[self::CSV_TYPE_SOCIETE] == "") {
            $societe->setTypeSociete(EtablissementManager::TYPE_ETB_NON_SPECIFIE);
        } else {

            $types_etablissements = EtablissementManager::$type_etablissements_libelles;
            $types_etablissements_values = array_values($types_etablissements);

            $type_etb_libelle = $types_etablissements_values[$ligne[self::CSV_TYPE_SOCIETE]];
            $types_etb_key = array_keys($types_etablissements, $type_etb_libelle);

            $societe->setTypeSociete($types_etb_key[0]);
        }

        return $societe;
    }

}
