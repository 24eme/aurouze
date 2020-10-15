<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\Coordonnees;

/**
 * @MongoDB\EmbeddedDocument
*/
class ContactCoordonnee {

    /**
     * @MongoDB\Field(type="string")
     */
    protected $telephoneFixe;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $telephoneMobile;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $fax;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $email;

    /**
     * @MongoDB\Field(type="string")
     */

     protected $emailFacturation;

     /**
      * @MongoDB\Field(type="string")
      */


    protected $siteInternet;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $libelle;

    public function isSameThan(ContactCoordonnee $contactCoordonnee) {
        if
        (
            ($this->getTelephoneFixe() == $contactCoordonnee->getTelephoneFixe() || !$this->getTelephoneFixe()) &&
            ($this->getTelephoneMobile() == $contactCoordonnee->getTelephoneMobile() || !$this->getTelephoneMobile()) &&
            ($this->getFax() == $contactCoordonnee->getFax() || !$this->getFax()) &&
            ($this->getEmail() == $contactCoordonnee->getEmail() || !$this->getEmail()) &&
            ($this->getEmailFacturation() == $contactCoordonnee->getEmailFacturation() || !$this->getEmailFacturation()) &&
            ($this->getSiteInternet() == $contactCoordonnee->getSiteInternet() || !$this->getSiteInternet()) &&
            ($this->getLibelle() == $contactCoordonnee->getLibelle() || !$this->getLibelle())
        )
        {

            return true;
        }

        return false;
    }

    public function copyFrom(ContactCoordonnee $contactCoordonnee) {
        $this->setTelephoneFixe($contactCoordonnee->getTelephoneFixe());
        $this->setTelephoneMobile($contactCoordonnee->getTelephoneMobile());
        $this->setFax($contactCoordonnee->getFax());
        $this->setEmail($contactCoordonnee->getEmail());
        $this->setEmailFacturation($contactCoordonnee->getEmailFacturation());
        $this->setSiteInternet($contactCoordonnee->getSiteInternet());
        $this->setLibelle($contactCoordonnee->getLibelle());
    }

    /**
     * Set telephoneFixe
     *
     * @param string $telephoneFixe
     * @return self
     */
    public function setTelephoneFixe($telephoneFixe)
    {
        $this->telephoneFixe = $telephoneFixe;
        return $this;
    }

    /**
     * Get telephoneFixe
     *
     * @return string $telephoneFixe
     */
    public function getTelephoneFixe()
    {
        return $this->telephoneFixe;
    }

    /**
     * Set telephoneMobile
     *
     * @param string $telephoneMobile
     * @return self
     */
    public function setTelephoneMobile($telephoneMobile)
    {
        $this->telephoneMobile = $telephoneMobile;
        return $this;
    }

    /**
     * Get telephoneMobile
     *
     * @return string $telephoneMobile
     */
    public function getTelephoneMobile()
    {
        return $this->telephoneMobile;
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
     * Set emailFacturation
     *
     * @param string $emailFacturation
     * @return self
     */
    public function setEmailFacturation($emailFacturation)
    {
        $this->emailFacturation = $emailFacturation;
        return $this;
    }

    /**
     * Get $emailFacturation
     *
     * @return string $emailFacturation
     */
    public function getEmailFacturation()
    {
        return $this->emailFacturation;
    }

    /**
     * Set siteInternet
     *
     * @param string $siteInternet
     * @return self
     */
    public function setSiteInternet($siteInternet)
    {
        $this->siteInternet = $siteInternet;
        return $this;
    }

    /**
     * Get siteInternet
     *
     * @return string $siteInternet
     */
    public function getSiteInternet()
    {
        return $this->siteInternet;
    }

    /**
     * Set libelle
     *
     * @param string $libelle
     * @return self
     */
    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;
        return $this;
    }

    /**
     * Get libelle
     *
     * @return string $libelle
     */
    public function getLibelle()
    {
        return $this->libelle;
    }

}
