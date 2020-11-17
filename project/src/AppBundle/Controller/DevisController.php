<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Manager\DevisManager;
use AppBundle\Manager\PassageManager;
use AppBundle\Document\Devis;
use AppBundle\Document\Societe;
use AppBundle\Document\RendezVous;
use AppBundle\Type\DevisType;
use AppBundle\Model\FacturableControllerTrait;

/**
 * Devis controller.
 *
 * @Route("/devis")
 */
class DevisController extends Controller
{
    use FacturableControllerTrait;

    /**
     * @Route("/", name="devis")
     */
    public function indexAction()
    {
        $devisManager = $this->get('devis.manager');

        $devisEnAttenteAcceptation = $devisManager->getRepository('AppBundle:Devis')->findBy(
            ['statut' => PassageManager::STATUT_A_ACCEPTER], ['dateEmission' => 'desc']
        );

        $devisAPlanifier = $devisManager->getRepository('AppBundle:Devis')->findBy(
            ['statut' => PassageManager::STATUT_A_PLANIFIER], ['dateEmission' => 'desc']
        );

        return $this->render('devis/index.html.twig', array('devisEnAttenteAcceptation' => $devisEnAttenteAcceptation, 'devisAPlanifier' => $devisAPlanifier));
    }

    /**
     * @Route("/societe/{id}", name="devis_societe")
     *
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function societeAction(Societe $societe)
    {
        $devisManager = $this->get('devis.manager');

        $devis = $devisManager->findBySociete($societe);

        return $this->render('devis/societe.html.twig',
            compact('societe', 'devis')
        );
    }

    /**
     * @Route("/societe/{societe}/creation-devis", name="devis_creation")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function creationAction(Request $request, Societe $societe) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('configuration.manager');
        $devism = $this->get('devis.manager');

        $devis = $devism->createVierge($societe);
        $devis->setSociete($societe);
        $devis->setDateEmission(new \DateTime());

        $appConf = $this->container->getParameter('application');
        $commercial = $dm->getRepository('AppBundle:Compte')->findOneByIdentifiant($appConf['commercial']);
        if ($commercial === null) {
            throw new \LogicException("Il n'y a pas de commercial dans la config.");
        }
        $devis->setCommercial($commercial);

        $produitsSuggestion = $this->getProduitsSuggestion($cm->getConfiguration()->getProduits());

        $form = $this->createForm(new DevisType($dm, $cm, $societe,  $appConf['commercial']), $devis, array(
            'action' => $this->generateUrl('devis_creation', ['societe' => $societe->getId()]),
            'method' => 'POST',
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $devis->update();

            $dm->persist($devis);
            $dm->flush();

            if ($form->get('edit')->isClicked()) {
                return $this->redirectToRoute('devis_societe', array('id' => $societe->getId()));
            } elseif ($form->get('plan')->isClicked()) {
                return $this->redirectToRoute('calendar', array(
                    'planifiable' => $devis->getId(),
                    'date' => $devis->getDatePrevision()->format('d-m-Y'),
                    'id' => $devis->getEtablissement()->getId(),
                    'technicien' => $devis->getTechniciens()[0]->getId()
                ));
            }
        }

        return $this->render('devis/modification.html.twig', array('form' => $form->createView(), 'produitsSuggestion' => $produitsSuggestion, 'societe' => $societe, 'devis' => $devis));
    }

    /**
     * @Route("/societe/{societe}/devis/{id}/annulation", name="devis_annulation")
     * @ParamConverter("societe", class="AppBundle:Societe")
     * @ParamConverter("devis", class="AppBundle:Devis")
     */
    public function suppressionAction(Request $request, Societe $societe, Devis $devis) {
        $devis->setStatut(PassageManager::STATUT_ANNULE);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->flush();

        return $this->redirectToRoute('devis_societe', array('id' => $societe->getId()));
    }

    /**
     * @Route("/{id}/edit", name="devis_modification")
     */
    public function editAction(Request $request, Devis $devis)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('configuration.manager');
        $appConf = $this->container->getParameter('application');

        $produitsSuggestion = $this->getProduitsSuggestion($cm->getConfiguration()->getProduits());

        $factureManager = $this->get('facture.manager');

        $hasFacture = count($factureManager->getRepository('AppBundle:Facture')->findBy(['numeroDevis' => $devis->getNumeroDevis()]));

        $form = $this->createForm(new DevisType($dm, $cm, $devis->getSociete(), $appConf['commercial']), $devis, array(
            'action' => "",
            'method' => 'POST',
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $devis->update();
            $dm->persist($devis);
            $dm->flush();

            if ($form->get('edit')->isClicked()) {
                return $this->redirectToRoute('devis_societe', array('id' => $devis->getSociete()->getId()));
            } elseif ($form->get('plan')->isClicked()) {
                return $this->redirectToRoute('calendar', array(
                    'planifiable' => $devis->getId(),
                    'id' => $devis->getEtablissement()->getId(),
                    'technicien' => $devis->getTechniciens()->first()->getId(),
                    'date' => $devis->getDatePrevision()->format('d-m-Y')
                ));
            }
        }

        return $this->render('devis/modification.html.twig', [
            'hasFacture' => $hasFacture,
            'devis' => $devis,
            'form' => $form->createView(),
            'produitsSuggestion' => $produitsSuggestion
        ]);
    }

    /**
     * @Route("/{id}/create-facture", name="devis_create-facture")
     */
    public function createFactureAction(Request $request, Devis $devis)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $fm = $this->get('facture.manager');
        $appConf = $this->container->getParameter('application');
        $planification = boolval($request->get('planification',true));
        $facture = $fm->createFromDevis($devis, $planification);
        $devis->setPdfNonEnvoye(false);
        $dm->persist($facture);
        $devis->setIdentifiantFacture($facture->getId());
        $dm->flush();
        return $this->redirectToRoute('facture_societe', array('id' => $facture->getSociete()->getId()));
    }

    /**
     * @Route("/{id}/send", name="devis_pdf_envoi")
     */
    public function sendPdfAction(Devis $devis)
    {
    }

    private function getProduitsSuggestion($produits)
    {
        $produitsSuggestion = [];

        foreach ($produits as $produit) {
            $produitsSuggestion[] = array("libelle" => $produit->getNom(), "conditionnement" => $produit->getConditionnement(), "identifiant" => $produit->getIdentifiant(), "prix" => $produit->getPrixVente());
        }

        return $produitsSuggestion;
    }

    /**
     * @Route("/visualisation/{id}", name="devis_visualisation")
     * @ParamConverter("devis", class="AppBundle:Devis")
     */
    public function visualisationAction(Request $request, Devis $devis)
    {
        if ($devis->getRendezVous()) {
            return $this->redirectToRoute('calendarRead', array('id' => $devis->getRendezVous()->getId(), 'service' => $request->get('service')));
        }

        return $this->forward('AppBundle:Calendar:calendarRead', array('planifiable' => $devis->getId(), 'service' => $request->get('service')));
    }
}
