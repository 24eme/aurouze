<?php
namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
*/
class FactureLigne {

    /**
     * @MongoDB\String
     */
    protected $libelle;

    /**
     * @MongoDB\String
     */
    protected $description;

    /**
     * @MongoDB\Float
     */
    protected $prixUnitaire;

    /**
     * @MongoDB\Float
     */
    protected $tauxTaxe;

    /**
     * @MongoDB\Float
     */
    protected $quantite;

    /**
     * @MongoDB\Float
     */
    protected $montantHT;

    /**
     * @MongoDB\Float
     */
    protected $montantTaxe;

    /**
     * @MongoDB\ReferenceOne()
     */
    protected $origineDocument;

    /**
     * @MongoDB\String
     */
    protected $origineMouvement;

    public function update() {
        $this->montantHT = round($this->getQuantite() * $this->getPrixUnitaire(), 2);
        $this->montantTaxe = round($this->getMontantHT() * $this->getTauxTaxe(), 2);
    }

    public function isOrigineContrat() {

        return $this->getOrigineDocument() && $this->getOrigineDocument() instanceof \AppBundle\Document\Contrat;
    }

    public function pullFromMouvement(Mouvement $mouvement) {
        $this->setLibelle($mouvement->getLibelle());
        $this->setQuantite($mouvement->getQuantite());
        $this->setPrixUnitaire($mouvement->getPrixUnitaire());
        $this->setTauxTaxe($mouvement->getTauxTaxe());
        $this->setOrigineDocument($mouvement->getDocument());
        $this->setOrigineMouvement($mouvement->getIdentifiant());
    }

    public function facturerMouvement() {
        if(!$this->getOrigineDocument()) {

            return null;
        }

        $this->getMouvement()->facturer();
    }

    public function getMouvement() {
        if(!$this->getOrigineDocument()) {

            return null;
        }
		$mvts = $this->getOrigineDocument()->getMouvements();
		foreach ($mvts as $mvt) {
			if ($mvt->getIdentifiant() == $this->getOrigineMouvement()) {
				return $mvt;
			}
		}
        throw new \Exception("Mouvement non trouvé");
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

    /**
     * Set prixUnitaire
     *
     * @param float $prixUnitaire
     * @return self
     */
    public function setPrixUnitaire($prixUnitaire)
    {
        $this->prixUnitaire = $prixUnitaire;
        return $this;
    }

    /**
     * Get prixUnitaire
     *
     * @return float $prixUnitaire
     */
    public function getPrixUnitaire()
    {
        return $this->prixUnitaire;
    }

    /**
     * Set quantite
     *
     * @param float $quantite
     * @return self
     */
    public function setQuantite($quantite)
    {
        $this->quantite = $quantite;
        return $this;
    }

    /**
     * Get quantite
     *
     * @return float $quantite
     */
    public function getQuantite()
    {
        return $this->quantite;
    }

    /**
     * Set tauxTaxe
     *
     * @param float $tauxTaxe
     * @return self
     */
    public function setTauxTaxe($tauxTaxe)
    {
        $this->tauxTaxe = $tauxTaxe;
        return $this;
    }

    /**
     * Get tauxTaxe
     *
     * @return float $tauxTaxe
     */
    public function getTauxTaxe()
    {
        return $this->tauxTaxe;
    }

    /**
     * Set montantHT
     *
     * @param float $montantHT
     * @return self
     */
    public function setMontantHT($montantHT)
    {
        $this->montantHT = $montantHT;
        return $this;
    }

    /**
     * Get montantHT
     *
     * @return float $montantHT
     */
    public function getMontantHT()
    {
        return $this->montantHT;
    }

    /**
     * Set montantTaxe
     *
     * @param float $montantTaxe
     * @return self
     */
    public function setMontantTaxe($montantTaxe)
    {
        $this->montantTaxe = $montantTaxe;
        return $this;
    }

    /**
     * Get montantTaxe
     *
     * @return float $montantTaxe
     */
    public function getMontantTaxe()
    {
        return $this->montantTaxe;
    }


    /**
     * Set origineDocument
     *
     * @param $origineDocument
     * @return self
     */
    public function setOrigineDocument($origineDocument)
    {
        $this->origineDocument = $origineDocument;
        return $this;
    }

    /**
     * Get origineDocument
     *
     * @return $origineDocument
     */
    public function getOrigineDocument()
    {
        return $this->origineDocument;
    }

    /**
     * Set origineMouvement
     *
     * @param string $origineMouvement
     * @return self
     */
    public function setOrigineMouvement($origineMouvement)
    {
        $this->origineMouvement = $origineMouvement;
        return $this;
    }

    /**
     * Get origineMouvement
     *
     * @return string $origineMouvement
     */
    public function getOrigineMouvement()
    {
        return $this->origineMouvement;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }
}
