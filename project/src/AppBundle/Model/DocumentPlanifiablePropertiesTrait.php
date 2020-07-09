<?php

namespace AppBundle\Model;

use AppBundle\Document\Etablissement;
use AppBundle\Document\Compte;
use AppBundle\Document\RendezVous;

trait DocumentPlanifiablePropertiesTrait
{
    /**
     * @MongoDB\ReferenceOne(targetDocument="Etablissement", inversedBy="devis", simple=true)
     */
    protected $etablissement;

    /**
     * @MongoDB\ReferenceMany(targetDocument="Compte", inversedBy="techniciens", simple=true)
     */
    protected $techniciens;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateDebut;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateFin;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $datePrevision;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateRealise;

    /**
    * @MongoDB\ReferenceOne(targetDocument="RendezVous", simple=true, cascade={"remove"})
     */
    protected $rendezVous;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $emailTransmission;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $secondEmailTransmission;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $nomTransmission;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $commentaireInterne;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $saisieTechnicien;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $signatureBase64;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $statut;

    /**
     * @MongoDB\Field(type="bool")
     */
    protected $pdfNonEnvoye;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $dateAcceptation;


}
