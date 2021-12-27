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
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Manager\EtablissementManager as EtablissementManager;
use AppBundle\Document\ContactCoordonnee;

class SocieteCsvImporter extends CsvFile {

    protected $dm;

    const CSV_ID_SOCIETE = 0;
    const CSV_ID_ADRESSE_HISTORIQUE = 1;
    const CSV_TYPE_ADRESSE = 2;
    const CSV_RAISON_SOCIALE = 3;
    const CSV_ADRESSE_LIBELLE_1 = 4;
    const CSV_ADRESSE_SOCIETE_1 = 5;
    const CSV_ADRESSE_SOCIETE_2 = 6;
    const CSV_CP = 7;
    const CSV_VILLE = 8;
    const CSV_PAYS = 9;
    const CSV_TEL_FIXE = 10;
    const CSV_TEL_MOBILE = 11;
    const CSV_FAX = 12;
    const CSV_SITE_WEB = 13;
    const CSV_EMAIL = 14;
    const CSV_CODE_COMPTABLE = 15;
    const CSV_SIRET = 16;
    const CSV_NUM_ACCISES = 17;
    const CSV_CODE_APE = 18;
    const CSV_TYPE_SOCIETE = 19;
    const CSV_SOUS_TRAITANT = 20;
    const CSV_ADRESSE_COMMENTAIRE = 21;
    const CSV_COMMENTAIRE = 22;

    public function __construct(DocumentManager $dm) {
        $this->dm = $dm;
    }

    public function import($file, OutputInterface $output) {
        $csvFile = new CsvFile($file);

        $csv = $csvFile->getCsv();
        $cpt = 0;
        foreach ($csv as $data) {

            $societe = $this->createFromImport($data);
            if(!$societe) {

                continue;
            }
            $this->dm->persist($societe);
            if ($cpt > 1000) {
                $this->dm->flush();
                $this->dm->clear();
                gc_collect_cycles();
                $cpt = 0;
            }
            $cpt++;
        }
        $this->dm->flush();
    }

    public function createFromImport($ligne) {
        if(!$ligne[self::CSV_ID_SOCIETE]) {

            return;
        }

        $societe = new Societe();

        $societe->setIdentifiantReprise(trim($ligne[self::CSV_ID_SOCIETE]));

        if($ligne[self::CSV_ID_ADRESSE_HISTORIQUE]) {
            $societe->setIdentifiantAdresseReprise($ligne[self::CSV_ID_ADRESSE_HISTORIQUE]);
        }

        $raisonSociale = $this->createRaisonSociale($ligne[self::CSV_RAISON_SOCIALE],$ligne[self::CSV_ADRESSE_LIBELLE_1]);

        $societe->setRaisonSociale($raisonSociale);
        $societe->setCodeComptable(trim($ligne[self::CSV_CODE_COMPTABLE]));
        $societe->setSiret(trim($ligne[self::CSV_SIRET]));
        $societe->setCommentaire(null);
        $ligne[self::CSV_COMMENTAIRE] = str_replace('"', "", $ligne[self::CSV_COMMENTAIRE]);
        $ligne[self::CSV_ADRESSE_COMMENTAIRE] = str_replace('"', "", $ligne[self::CSV_COMMENTAIRE]);
        if($ligne[self::CSV_TYPE_ADRESSE] != "1") {
            $ligne[self::CSV_ADRESSE_COMMENTAIRE] = null;
        }
        if(trim($ligne[self::CSV_COMMENTAIRE])) {
            $societe->setCommentaire($ligne[self::CSV_COMMENTAIRE]."\n");
        }
        if(trim($ligne[self::CSV_ADRESSE_COMMENTAIRE]) && $ligne[self::CSV_COMMENTAIRE] != $ligne[self::CSV_ADRESSE_COMMENTAIRE]) {
            $societe->setCommentaire($societe->getCommentaire()."\n".$ligne[self::CSV_ADRESSE_COMMENTAIRE]);
        }
        $societe->setSousTraitant(boolval($ligne[self::CSV_SOUS_TRAITANT]));

        $adresse = new Adresse();

        $adresseStr = $this->createAdresse($ligne[self::CSV_RAISON_SOCIALE],$ligne[self::CSV_ADRESSE_LIBELLE_1],$ligne[self::CSV_ADRESSE_SOCIETE_1],$ligne[self::CSV_ADRESSE_SOCIETE_2]);

        $adresse->setAdresse($adresseStr);
        $adresse->setCodePostal($ligne[self::CSV_CP]);
        $adresse->setCommune($ligne[self::CSV_VILLE]);

        $societe->setAdresse($adresse);

        $contactCoordonnee = new ContactCoordonnee();
        $contactCoordonnee->setTelephoneFixe(trim($ligne[self::CSV_TEL_FIXE]));
        $contactCoordonnee->setTelephoneMobile(trim($ligne[self::CSV_TEL_MOBILE]));
        $contactCoordonnee->setFax(trim($ligne[self::CSV_FAX]));
        $contactCoordonnee->setSiteInternet(trim($ligne[self::CSV_SITE_WEB]));
        $contactCoordonnee->setEmail(trim($ligne[self::CSV_EMAIL]));


         $societe->setContactCoordonnee($contactCoordonnee);
        if ($ligne[self::CSV_TYPE_SOCIETE] == "") {
            $societe->setType(EtablissementManager::TYPE_ETB_NON_SPECIFIE);
        } else {

            $types_etablissements = EtablissementManager::$type_libelles;
            $types_etb_keys = array_keys($types_etablissements);

            if((intval($ligne[self::CSV_TYPE_SOCIETE])-1) >= 0) {
                $societe->setType($types_etb_keys[intval($ligne[self::CSV_TYPE_SOCIETE])-1]);
            }
        }
        return $societe;
    }

    private function createRaisonSociale($rs,$adresseChamp1){
      if(trim(strtoupper($rs)) == trim(strtoupper($adresseChamp1)) || !trim(strtoupper($adresseChamp1))){
        return $this->removeTitre($rs);
      }
      return $this->removeTitre($rs)." - ".$this->removeTitre($adresseChamp1);
    }

    private function removeTitre($nom_ou_rs){
      $nom_ou_rs = preg_replace('/^MONSIEUR\ (.+)/', 'M. $1', $nom_ou_rs);
      $nom_ou_rs = preg_replace('/^MADAME\ (.+)/', 'Mme. $1', $nom_ou_rs);
      $nom_ou_rs = preg_replace('/^MADEMOISELLE\ (.+)/', 'Mlle. $1', $nom_ou_rs);

      return trim($nom_ou_rs);
    }

    private function createAdresse($nom_ou_rs,$adresse_libelle,$adresse_complement1,$adresse_complement2){
      $adresseStr = "";
      if(trim($adresse_libelle) && trim(strtoupper($nom_ou_rs)) != trim(strtoupper($adresse_libelle))){
        $adresseStr.= trim($adresse_libelle).", ";
      }
      if(trim($adresse_complement1) && trim(strtoupper($nom_ou_rs)) != trim(strtoupper($adresse_complement1))){
        $adresseStr.= trim($adresse_complement1).", ";
      }
      if(trim($adresse_complement2) && trim(strtoupper($nom_ou_rs)) != trim(strtoupper($adresse_complement2))){
        $adresseStr.= trim($adresse_complement2).", ";
      }
      $adresseStr = preg_replace('/(.+),\ $/', '$1', $adresseStr);
      $adresseStr = preg_replace('/(.+),\ $/', '$1', $adresseStr);
      $adresseStr = preg_replace('/(.+),\ $/', '$1', $adresseStr);

      return $adresseStr;
    }

}
