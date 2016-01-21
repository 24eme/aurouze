<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EtablissementManager
 *
 * @author mathurin
 */

namespace AppBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use AppBundle\Document\Etablissement as Etablissement;
use AppBundle\Import\EtablissementCsvImport as EtablissementCSVImport;

class EtablissementManager {

    protected $dm;

    const TYPE_ETB_BOULANGERIE = "BOULANGERIE";
    const TYPE_ETB_RESTAURANT = "RESTAURANT";
    const TYPE_ETB_ADMINISTRATION = "ADMINISTRATION";
    const TYPE_ETB_MAIRIE = "MAIRIE";
    const TYPE_ETB_ENTREPRISE_PRIVEE = "ENTREPRISE_PRIVEE";
    const TYPE_ETB_PARTICULIER = "PARTICULIER";
    const TYPE_ETB_FERME = "FERME";
    const TYPE_ETB_SYNDIC = "SYNDIC";
    const TYPE_ETB_COMMERCE = "COMMERCE";
    const TYPE_ETB_CAFE_BRASSERIE = "CAFE_BRASSERIE";
    const TYPE_ETB_AUTRE = "AUTRE";
    const TYPE_ETB_HOTEL = "HOTEL";
    const TYPE_ETB_NON_SPECIFIE = "NON_SPECIFIE";

    public static $type_etablissements_libelles = array(
        self::TYPE_ETB_BOULANGERIE => "Boulangerie",
        self::TYPE_ETB_RESTAURANT => "Restaurant",
        self::TYPE_ETB_ADMINISTRATION => "Administration",
        self::TYPE_ETB_MAIRIE => "Mairie",
        self::TYPE_ETB_ENTREPRISE_PRIVEE => "Entreprise privée",
        self::TYPE_ETB_PARTICULIER => "Particulier",
        self::TYPE_ETB_FERME => "Ferme",
        self::TYPE_ETB_SYNDIC => "Syndic",
        self::TYPE_ETB_COMMERCE => "Commerce",
        self::TYPE_ETB_CAFE_BRASSERIE => "Café brasserie",
        self::TYPE_ETB_AUTRE => "Autre",
        self::TYPE_ETB_HOTEL => "Hôtel",
        self::TYPE_ETB_NON_SPECIFIE => "Non spécifié");

    function __construct(DocumentManager $dm) {
        $this->dm = $dm;
    }

    function create() {
        $etablissement = new Etablissement();
        $identifiant = $this->getNextIdentifiant();

        $etablissement->setIdentifiant($identifiant);
        $etablissement->setId();
        $etablissement->setNom("Test " . $etablissement->getIdentifiant());
        return $etablissement;
    }

    public function getNextIdentifiant() {
        $allEtablissementsIdentifiants = $this->dm->getRepository('AppBundle:Etablissement')->findAllEtablissementsIdentifiants();
        if (!count($allEtablissementsIdentifiants)) {
            return sprintf("%08d", 1);
        }
        return sprintf("%08d", max($allEtablissementsIdentifiants) + 1);
    }

    public function createFromImport($ligne) {

        $etablissement = new Etablissement();
        $etablissement->setIdentifiant(sprintf("%06d", $ligne[EtablissementCSVImport::CSV_ID]) . '01');
        $etablissement->setId();

        $etablissement->setRaisonSociale($ligne[EtablissementCSVImport::CSV_LIBELLE]);

        if ($ligne[EtablissementCSVImport::CSV_TYPE_ETABLISSEMENT] == "") {
            $etablissement->setTypeEtablissement(self::TYPE_ETB_NON_SPECIFIE);
        } else {

            $types_etablissements = self::$type_etablissements_libelles;
            $types_etablissements_values = array_values($types_etablissements);

            $type_etb_libelle = $types_etablissements_values[$ligne[EtablissementCSVImport::CSV_TYPE_ETABLISSEMENT]];
            $types_etb_key = array_keys($types_etablissements, $type_etb_libelle);

            $etablissement->setTypeEtablissement($types_etb_key[0]);
        }
        
        return $etablissement;
    }

}
