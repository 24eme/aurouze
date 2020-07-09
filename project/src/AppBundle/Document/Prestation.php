<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Behat\Transliterator\Transliterator;

/**
 * @MongoDB\EmbeddedDocument
 */
class Prestation {

    /**
     * @MongoDB\Field(type="string")
     */
    protected $identifiant;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $nom;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $nomCourt;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $nbPassages;


    /**
     * Set nom
     *
     * @param string $nom
     * @return self
     */
    public function setNom($nom) {
        $this->nom = $nom;
        $this->setNomCourt($nom);
        return $this;
    }

    /**
     * Get nom
     *
     * @return string $nom
     */
    public function getNom() {
        return $this->nom;
    }

    /**
     * Set nbPassages
     *
     * @param string $nbPassages
     * @return self
     */
    public function setNbPassages($nbPassages) {
        $this->nbPassages = $nbPassages;
        return $this;
    }

    /**
     * Get nbPassages
     *
     * @return string $nbPassages
     */
    public function getNbPassages() {
        return $this->nbPassages;
    }

    public function getNomToString() {

        return $this->getNom();
    }

    public function getNomSimplifieToString() {
      $nom_libelles = explode('-', $this->getIdentifiant());
      $mot_rongeur = array('DERATISATION', 'RONGEURS');
      $mot_puce = array('RAMPANTS');
      $mot_moustique = array('VOLANTS');
      $mot_cafard = array('BLATTES');
      $mot_punaise = array('PUNAISES');
      $mot_chenille = array('CHENILLES');
      $mot_pigeon = array('DEPIGEONNAGE');
      $mot_bois = array('TRAITEMENT', 'BOIS');
      $mot_travaux = array('TRAVAUX', 'DIVERS');
      $mot_DEIV = array('MAINTENANCE', 'D.E.I.V');
      $mot_desinfection = array('DESINFECTION','ASSAINISSEMENT');
      if($this->isPictoForLibelles($nom_libelles, $mot_rongeur)){
          if($this->isPictoForLibelles($nom_libelles,array('RATS'))){
              return "rats";
          }
          if($this->isPictoForLibelles($nom_libelles,array('SOURIS'))){
              return "souris";
          }
          if($this->isPictoForLibelles($nom_libelles,array('LOIRS'))){
              return "loirs";
          }
          if($this->isPictoForLibelles($nom_libelles,array('SURMULOTS'))){
              return "surmulots";
          }
      }elseif($this->isPictoForLibelles($nom_libelles, $mot_cafard)){
          return 'cafard';
      }elseif($this->isPictoForLibelles($nom_libelles, $mot_punaise)){
          return 'punaise';
      }elseif($this->isPictoForLibelles($nom_libelles, $mot_moustique)){
          return 'moustique';
      }elseif($this->isPictoForLibelles($nom_libelles, $mot_chenille)){
          return 'chenille';
      }elseif($this->isPictoForLibelles($nom_libelles, $mot_puce)){
          return 'puce';
      }elseif($this->isPictoForLibelles($nom_libelles, $mot_pigeon)){
          return 'pigeon';
      }elseif($this->isPictoForLibelles($nom_libelles, $mot_bois)){
          return 'traitement bois';
      }elseif($this->isPictoForLibelles($nom_libelles, $mot_travaux)){
          return 'travaux';
      }elseif($this->isPictoForLibelles($nom_libelles, $mot_DEIV)){
          return 'd.e.i.v';
      }elseif($this->isPictoForLibelles($nom_libelles, $mot_desinfection)){
          return 'déinfection assainissement';
      }

      return "";
    }


    public function getWordToPicto() {
        $nom_libelles = explode('-', $this->getIdentifiant());
        $mot_rongeur = array('DERATISATION', 'RONGEURS');
        $mot_puce = array('RAMPANTS');
        $mot_moustique = array('VOLANTS');
        $mot_cafard = array('BLATTES', 'PUNAISES');
        $mot_chenille = array('CHENILLES');
        $mot_pigeon = array('DEPIGEONNAGE');
        $mot_bois = array('TRAITEMENT', 'BOIS');
        $mot_travaux = array('TRAVAUX', 'DIVERS', 'TRAVAUX DIVERS');
        $mot_DEIV = array('MAINTENANCE', 'D.E.I.V');
        $mot_desinfection = array('DESINFECTION','ASSAINISSEMENT');
        if($this->isPictoForLibelles($nom_libelles, $mot_rongeur)){
            $type_rongeur = "";
            if($this->isPictoForLibelles($nom_libelles,array('RATS'))){
                $type_rongeur = "rats-color";
            }
            if($this->isPictoForLibelles($nom_libelles,array('SOURIS'))){
                $type_rongeur = "souris-color";
            }
            if($this->isPictoForLibelles($nom_libelles,array('LOIRS'))){
                    $type_rongeur = "loirs-color";
            }
            if($this->isPictoForLibelles($nom_libelles,array('SURMULOTS'))){
                    $type_rongeur = "surmulots-color";
            }

            return 'rongeur '.$type_rongeur;
        }elseif($this->isPictoForLibelles($nom_libelles, $mot_moustique)){
            return 'moustique';
        }elseif($this->isPictoForLibelles($nom_libelles, $mot_cafard)){
            return 'cafard';
        }elseif($this->isPictoForLibelles($nom_libelles, $mot_chenille)){
            return 'chenille';
        }elseif($this->isPictoForLibelles($nom_libelles, $mot_puce)){
            return 'puce';
        }elseif($this->isPictoForLibelles($nom_libelles, $mot_pigeon)){
            return 'pigeon';
        }elseif($this->isPictoForLibelles($nom_libelles, $mot_bois)){
            return 'spa mdi';
        }elseif($this->isPictoForLibelles($nom_libelles, $mot_travaux)){
            return 'build mdi';
        }elseif($this->isPictoForLibelles($nom_libelles, $mot_DEIV)){
            return 'settings-input-component mdi';
        }elseif($this->isPictoForLibelles($nom_libelles, $mot_desinfection)){
            return 'delete mdi';
        }


        return false;
    }

    private function echapLibelles($nom_libelles, $mot_inutiles) {
        $str = "";
        foreach ($nom_libelles as $libelle) {
            if (trim($libelle) && !in_array(trim($libelle), $mot_inutiles)) {
                $str .= ' ' . trim($libelle);
            }
        }
        return trim($str);
    }

    private function isPictoForLibelles($nom_libelles, $mot_picto) {

        foreach ($nom_libelles as $libelle) {
            if (trim($libelle) && in_array(trim($libelle), $mot_picto)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Set identifiant
     *
     * @param string $identifiant
     * @return self
     */
    public function setIdentifiant($identifiant)
    {
        $this->identifiant = $identifiant;
        return $this;
    }

    /**
     * Get identifiant
     *
     * @return string $identifiant
     */
    public function getIdentifiant()
    {
        return $this->identifiant;
    }



    public function __toString()
    {
    	return $this->getNom();
    }

    /**
     * Set nomCourt
     *
     * @param string $nomCourt
     * @return self
     */
    public function setNomCourt($nomCourt)
    {
        $this->nomCourt = $nomCourt;
        return $this;
    }

    /**
     * Get nomCourt
     *
     * @return string $nomCourt
     */
    public function getNomCourt()
    {
        return $this->nomCourt;
    }
}
