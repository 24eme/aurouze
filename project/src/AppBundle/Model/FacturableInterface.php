<?php

namespace AppBundle\Model;

use AppBundle\Document\Societe;
use AppBundle\Document\Compte;
use AppBundle\Document\Soussigne;

interface FacturableInterface
{
    public function getSociete();
    public function setSociete(Societe $societe);

    public function getCommercial();
    public function setCommercial(Compte $commercial);

    public function getEmetteur();
    public function setEmetteur(Soussigne $emetteur);

    public function getDestinataire();
    public function setDestinataire(Soussigne $destinataire);

    public function getDateEmission();
    public function setDateEmission($date);

    public function getMontantHT();
    public function setMontantHT($montantHT);

    public function getMontantTTC();
    public function setMontantTTC($montantTTC);

    public function getDocumentType();
    public function getLignes();
    public function getNumero();
}
