<?php

namespace AppBundle\Model;

use AppBundle\Document\LigneFacturable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait FacturableControllerTrait
{
    /**
     * @Route("/{document}/pdf", name="facturable_pdf")
     */
    public function pdfAction(Request $request, $document)
    {
        $type = strtolower(strtok($document, '-'));
        $manager = $this->get($type.'.manager');
        $repository = $manager->getRepository('AppBundle:'.ucfirst($type));
        $document = $repository->findOneById($document);

        if (! $document instanceof FacturableInterface) {
            throw new \Exception($type." n'est pas de type FacturableInterface");
        }

        $pages = [];

        $nbLigneMaxPourPageVierge = 50;
        $nbLigneMaxPourDernierePage = 30;
        $nbPage = 1;
        $nbMaxCharByLigne = 60;
        $nbCurrentLigne = 0;
        $nbCurrentPage = 1;
        $nbLigneParLigneFacture = [];
        $nbLigneParPage = [1 => $nbLigneMaxPourDernierePage];

        foreach ($document->getLignes() as $key => $ligne) {
            $nbCurrentLigne += 2;
            if($ligne->getReferenceClient()) {
                $nbCurrentLigne += 1;
            }

            if($ligne->isOrigineContrat()) {
                $nbCurrentLigne += 4;
                $nbCurrentLigne += count($ligne->getOrigineDocument()->getPrestations());
                $nbCurrentLigne += count($ligne->getOrigineDocument()->getContratPassages());
            }

            $nbLigneParLigneFacture[$key] = $nbCurrentLigne;

            if($nbCurrentPage == $nbPage && $nbCurrentLigne > $nbLigneMaxPourDernierePage) {
                $nbLigneParPage[$nbCurrentPage] = $nbLigneMaxPourDernierePage;
                $nbPage += 1;
                $nbLigneParPage[$nbPage] = $nbLigneMaxPourDernierePage;
            }

            if($nbCurrentPage < $nbPage && $nbCurrentLigne > $nbLigneMaxPourPageVierge) {
                $nbLigneParPage[$nbCurrentPage] = $nbLigneMaxPourPageVierge;
                $nbCurrentPage += 1;
                $nbCurrentLigne = 0;
            }

        }

        $nbCurrentPage = 1;
        $nbCurrentLigne = 0;
        foreach($document->getLignes() as $key => $ligneFacture) {

            $ligne = $this->buildLignePDFFacture($ligneFacture);

            // La ligne ne tient pas sur une page complète
            if(($nbLigneParLigneFacture[$key]) > $nbLigneParPage[$nbCurrentPage]) {
                $nbLignes2Keep = (int)(0.8 * $nbLigneParPage[$nbCurrentPage]);
                $lignesSplitted = $this->splitLigne($ligne, $nbLignes2Keep);
                $pages[$nbCurrentPage][] = $lignesSplitted[0];
                $pages[$nbCurrentPage+1][] = $lignesSplitted[1];
                $nbCurrentLigne = 0;
                $nbCurrentPage += 1;
                continue;
            }

            // La ligne tient sur la page
            if(($nbCurrentLigne + $nbLigneParLigneFacture[$key]) < $nbLigneParPage[$nbCurrentPage]) {

                $pages[$nbCurrentPage][] = $ligne;
                continue;
            }

            // La ligne ne tient plus sur la page
            if(($nbCurrentLigne + $nbLigneParLigneFacture[$key]) > $nbLigneParPage[$nbCurrentPage]) {

                $nbCurrentLigne = 0;
                $nbCurrentPage += 1;
                $pages[$nbCurrentPage][] = $ligne;
                continue;
            }
        }

        $html = $this->renderView($type.'/pdf.html.twig', array(
            $type => $document,
            'pages' => $pages,
            'parameters' => $manager->getParameters(),
        ));

        if ($request->get('output') == 'html') {

            return new Response($html, 200);
        }

        $suffix = ($document->getNumero()) ? 'N'.$document->getNumero()
                                           : 'brouillon';

        $filename = implode('_', [
            $type,
            $document->getSociete()->getIdentifiant(),
            $document->getDateEmission()->format('Ymd'),
            $document->getNumero(),
            $suffix
        ]);
        $filename .= '.pdf';

        return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($html, $this->getPdfGenerationOptions()), 200, array(
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                )
        );
    }

    /**
     * Construit une ligne de facture pour les pdf
     *
     * @param LigneFacturable $ligne La ligne de facture
     * @return array $ligne
     */
    public function buildLignePDFFacture(LigneFacturable $ligneFacture)
    {
        $ligne = array();
        $ligne['libelle'] = $ligneFacture->getLibelle();
        $ligne['quantite'] = $ligneFacture->getQuantite();
        $ligne['prixUnitaire'] = $ligneFacture->getPrixUnitaire();
        $ligne['montantHT'] = $ligneFacture->getMontantHT();
        $ligne['referenceClient'] = $ligneFacture->getReferenceClient();
        if($ligneFacture->isOrigineContrat()) {
            $ligne['details'] = array();

            $keyPrestation = "Prestation";
            if (count($ligneFacture->getOrigineDocument()->getPrestations()) > 1) { $keyPrestation .= "s"; }
            foreach($ligneFacture->getOrigineDocument()->getPrestations() as $prestation) {
                $ligne['details'][$keyPrestation][] = $prestation->getNom();
            }

            $keyPassage = "Lieu";
            if(count($ligneFacture->getOrigineDocument()->getContratPassages()) > 1) { $keyPassage .= "x"; }
            $keyPassage .= " d'application";
            if(count($ligneFacture->getOrigineDocument()->getContratPassages()) > 1) { $keyPassage .= "s"; }
            foreach($ligneFacture->getOrigineDocument()->getContratPassages() as $passage) {
               $lignePassage = $passage->getEtablissement()->getNom(false).", ";
               if($passage->getEtablissement()->getAdresse()->getAdresse()){ $lignePassage .= $passage->getEtablissement()->getAdresse()->getAdresse().", "; }
               $lignePassage .= $passage->getEtablissement()->getAdresse()->getCodePostal()." ".$passage->getEtablissement()->getAdresse()->getCommune();
               $ligne['details'][$keyPassage][] = $lignePassage;
            }
        }

        if($ligneFacture->getDescription()) {
            $ligne["details"]["description"] = $ligneFacture->getDescription();
        }

        return $ligne;
    }

    public function splitLigne($ligne, $nbLignes2Keep) {
        $ligneSplitted = array();

        $ligneSplitted["libelle"] = $ligne['libelle']." (Suite)";
        foreach($ligne["details"] as $key => $details) {
            if(!preg_match("/^Lieu/", $key)) {
                continue;
            }
            $nb = 0;
            $keySplitted = $key." (Suite)";
            $ligneSplitted["details"] = array();
            $ligneSplitted["details"][$keySplitted] = array();
            foreach($details as $keyLieu => $lieu) {
                $nb += 1;
                if($nb <= $nbLignes2Keep) {
                    continue;
                }
                $ligneSplitted["details"][$keySplitted][] = $lieu;
                unset($ligne["details"][$key][$keyLieu]);
            }
            $ligne["details"][$key][] = "(Suite de la liste sur la page suivante)";
        }

        return array($ligne, $ligneSplitted);
    }

    public function getPdfGenerationOptions() {
        return array('disable-smart-shrinking' => null, 'encoding' => 'utf-8', 'margin-left' => 3, 'margin-right' => 3, 'margin-top' => 4, 'margin-bottom' => 4);
    }

}
