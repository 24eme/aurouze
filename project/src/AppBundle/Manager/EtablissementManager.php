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
use AppBundle\Document\Societe as Societe;
use AppBundle\Import\EtablissementCsvImport as EtablissementCSVImport;
use AppBundle\Tool\OSMAdresses;

class EtablissementManager {

    protected $dm;
    protected $osmAdresse;

    const SECTEUR_PARIS = "PARIS";
    const SECTEUR_SEINE_ET_MARNE = "SEINE_ET_MARNE";
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

    public static $type_libelles = array(
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
    public static $type_icon = array(
        self::TYPE_ETB_BOULANGERIE => "cake",
        self::TYPE_ETB_RESTAURANT => "local-dining",
        self::TYPE_ETB_ADMINISTRATION => "description",
        self::TYPE_ETB_MAIRIE => "account-balance",
        self::TYPE_ETB_ENTREPRISE_PRIVEE => "store",
        self::TYPE_ETB_PARTICULIER => "person",
        self::TYPE_ETB_FERME => "spa",
        self::TYPE_ETB_SYNDIC => "home",
        self::TYPE_ETB_COMMERCE => "local-grocery-store",
        self::TYPE_ETB_CAFE_BRASSERIE => "local-cafe",
        self::TYPE_ETB_AUTRE => "place",
        self::TYPE_ETB_HOTEL => "local-hotel",
        self::TYPE_ETB_NON_SPECIFIE => "do-not-disturb");
    public static $secteurs_departements = array(
        self::SECTEUR_PARIS => array('75'),
        self::SECTEUR_SEINE_ET_MARNE => array('77', '95', '89', '91', '45', '28')
    );
    public static $secteurs = array(
        self::SECTEUR_PARIS => "Aurouze Paris",
        self::SECTEUR_SEINE_ET_MARNE => "Aurouze Seine et Marne"
    );

    function __construct(DocumentManager $dm, OSMAdresses $osmAdresse) {
        $this->dm = $dm;
        $this->osmAdresse = $osmAdresse;
    }

    public function getRepository() {

        return $this->dm->getRepository('AppBundle:Etablissement');
    }

    public function getNextNumeroEtablissement(Societe $societe) {
        $allEtablissementsIdentifiants = $this->dm->getRepository('AppBundle:Etablissement')->findAllPostfixByIdentifiantSociete($societe);

        if (!count($allEtablissementsIdentifiants)) {
            return sprintf("%03d", 1);
        }

        return sprintf("%03d", max($allEtablissementsIdentifiants) + 1);
    }

    public function getOSMAdresse() {
        return $this->osmAdresse;
    }

    public function getAutreSecteurNom($secteur) {
        return self::$secteurs[$this->getAutreSecteur($secteur)];
    }

    public function getAutreSecteur($secteur) {
        $autreSecteur = null;
        if ($secteur == EtablissementManager::SECTEUR_PARIS) {
            $autreSecteur = EtablissementManager::SECTEUR_SEINE_ET_MARNE;
        } elseif ($secteur == EtablissementManager::SECTEUR_SEINE_ET_MARNE) {
            $autreSecteur = EtablissementManager::SECTEUR_PARIS;
        }
        return $autreSecteur;
    }

}
