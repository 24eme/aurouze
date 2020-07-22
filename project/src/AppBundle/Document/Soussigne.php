<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\Adresse;

/**
 * @MongoDB\EmbeddedDocument
*/
class Soussigne  {

    /**
     * @MongoDB\Field(type="string")
     */
    protected $nom;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $raisonSociale;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $adresse;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $codePostal;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $commune;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $codeComptable;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $tvaIntracommunautaire;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $telephone;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $fax;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $email;


    /**
     * Set nom
     *
     * @param string $nom
     * @return self
     */
    public function setNom($nom)
    {
        if(!$this->getRaisonSociale()) {
            $this->setRaisonSociale($nom);
        }
        $this->nom = $nom;
        return $this;
    }

    /**
     * Get nom
     *
     * @return string $nom
     */
    public function getNom()
    {
        return $this->nom;
    }

    public function getNomFormatee()
    {
        return str_replace(", ", "\n", $this->getNom());
    }

    /**
     * Set codeComptable
     *
     * @param string $codeComptable
     * @return self
     */
    public function setCodeComptable($codeComptable)
    {
        $this->codeComptable = $codeComptable;
        return $this;
    }

    /**
     * Get codeComptable
     *
     * @return string $codeComptable
     */
    public function getCodeComptable()
    {
        return $this->codeComptable;
    }

    /**
     * Set tvaIntracommunautaire
     *
     * @param string $tvaIntracommunautaire
     * @return self
     */
    public function setTvaIntracommunautaire($tvaIntracommunautaire)
    {
        $this->tvaIntracommunautaire = $tvaIntracommunautaire;
        return $this;
    }

    /**
     * Get tvaIntracommunautaire
     *
     * @return string $tvaIntracommunautaire
     */
    public function getTvaIntracommunautaire()
    {
        return $this->tvaIntracommunautaire;
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     * @return self
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
        return $this;
    }

    /**
     * Get telephone
     *
     * @return string $telephone
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set fax
     *
     * @param string $fax
     * @return self
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
        return $this;
    }

    /**
     * Get fax
     *
     * @return string $fax
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set adresse
     *
     * @param string $adresse
     * @return self
     */
    public function setAdresse($adresse)
    {
        $this->adresse = $adresse;
        return $this;
    }

    /**
     * Get adresse
     *
     * @return string $adresse
     */
    public function getAdresse()
    {
        return $this->adresse;
    }

    public function getAdresseFormatee()
    {
        return str_replace(", ", "\n", $this->getAdresse());
    }

    /**
     * Set codePostal
     *
     * @param string $codePostal
     * @return self
     */
    public function setCodePostal($codePostal)
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    /**
     * Get codePostal
     *
     * @return string $codePostal
     */
    public function getCodePostal()
    {
        return $this->codePostal;
    }

    /**
     * Set commune
     *
     * @param string $commune
     * @return self
     */
    public function setCommune($commune)
    {
        $this->commune = $commune;
        return $this;
    }

    /**
     * Get commune
     *
     * @return string $commune
     */
    public function getCommune()
    {
        return $this->commune;
    }

    /**
     * Set raisonSociale
     *
     * @param string $raisonSociale
     * @return self
     */
    public function setRaisonSociale($raisonSociale)
    {
        $this->raisonSociale = $raisonSociale;
        return $this;
    }

    /**
     * Get raisonSociale
     *
     * @return string $raisonSociale
     */
    public function getRaisonSociale()
    {
        if(!$this->raisonSociale) {

            return $this->getNom();
        }

        return $this->raisonSociale;
    }
}
