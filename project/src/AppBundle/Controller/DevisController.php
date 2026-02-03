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
use Behat\Transliterator\Transliterator;
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

        $devisAPlanifier = $devisManager->getRepository('AppBundle:Devis')->findBy(
            ['statut' => PassageManager::STATUT_A_PLANIFIER], ['dateEmission' => 'asc']
        );

        return $this->render('devis/index.html.twig', array('devisAPlanifier' => $devisAPlanifier));
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

        $factureManager = $this->get('facture.manager');

        $factures = [];

        foreach ($devis as $devisFacture) {
            $factures[$devisFacture->getId()] = $factureManager->getRepository()->findOneBy(['numeroDevis' => $devisFacture->getNumeroDevis()]);;
        }

        return $this->render('devis/societe.html.twig',
            compact('societe', 'devis', 'factures')
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
            if($this->container->getParameter("commercial_seine_et_marne")){
              $devis->setZone($this->container->getParameter("commercial_seine_et_marne"));
            }
            $dm->persist($devis);
            $dm->flush();

            if ($form->get('edit')->isClicked()) {
                return $this->redirectToRoute('devis_modification', array('id' => $devis->getId()));
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
     * @Route("/{id}/edit", name="devis_modification")
     */
    public function editAction(Request $request, Devis $devis)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('configuration.manager');
        $appConf = $this->container->getParameter('application');

        $produitsSuggestion = $this->getProduitsSuggestion($cm->getConfiguration()->getProduits());

        $factureManager = $this->get('facture.manager');

        $facture = current($factureManager->getRepository('AppBundle:Facture')->findBy(['numeroDevis' => $devis->getNumeroDevis()]));

        $form = $this->createForm(new DevisType($dm, $cm, $devis->getSociete(), $appConf['commercial']), $devis, array(
            'action' => "",
            'method' => 'POST',
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($devis->getRendezvous()) {
                $devis->getRendezVous()->removeAllParticipants();
                foreach($devis->getTechniciens() as $technicien) {
                    $devis->getRendezVous()->addParticipant($technicien);
                }
            }
            $devis->update();
            if($this->container->getParameter("commercial_seine_et_marne")){
              $devis->setZone($this->container->getParameter("commercial_seine_et_marne"));
            }
            $dm->persist($devis);
            $dm->flush();

            if ($form->get('edit')->isClicked()) {
                return $this->redirectToRoute('devis_modification', array('id' => $devis->getId()));
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
            'facture' => $facture,
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
        $df = ($this->container->getParameter('date_facturation'))? new \DateTime($this->container->getParameter('date_facturation')) : new \DateTime();
        $facture->setDateFacturation($df);
        $dm->persist($facture);
        $devis->setIdentifiantFacture($facture->getId());
        $dm->flush();

        if($request->get('service')) {
            return $this->redirect($request->get('service'));
        }

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

    private function createRapportVisitePdf(Devis $devis){
        $createRapportVisitePdf = new \stdClass();
        $dm = $this->get('doctrine_mongodb')->getManager();
        $fm = $this->get('facture.manager');

        $createRapportVisitePdf->html = $this->renderView('devis/pdfRapport.html.twig', array(
            'devis' => $devis,
            'parameters' => $fm->getParameters(),
        ));

        $createRapportVisitePdf->filename = sprintf("Livraison_rapport_%s_%s.pdf", $devis->getDateDebut()->format("Y-m-d_H:i"), strtoupper(Transliterator::urlize($devis->getEtablissement())));

        return $createRapportVisitePdf;
    }

    /**
     * @Route("/devis/pdf-rapport-download/{id}", name="devis_pdf_rapport_download")
     * @ParamConverter("devis", class="AppBundle:Devis")
     */
    public function pdfRapportDownloadAction(Request $request, Devis $devis) {
        $rapportVisitePdf = $this->createRapportVisitePdf($devis);
        if ($request->get('output') == 'html') {

            return new Response($rapportVisitePdf->html, 200);
        }

        if (! shell_exec(sprintf("which %s", escapeshellarg('pdftk')))) {
            throw new \LogicException('missing pdftk binary');
        }

        $tmpfile = $this->container->getParameter('kernel.cache_dir').'/PDFRAPPORT_'.$devis->getId().uniqid();
        $this->get('knp_snappy.pdf')->generateFromHtml($rapportVisitePdf->html,$tmpfile,$this->getPdfGenerationOptions());

        return new Response(file_get_contents($tmpfile), 200, array(
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $rapportVisitePdf->filename . '"'
            )
        );

        if($request->get('service')) {
            return $this->redirect($request->get('service'));
        }

    }

    /**
     * @Route("/devis/devis-transmission-email/{id}", name="devis_transmission_email")
     * @ParamConverter("devis", class="AppBundle:Devis")
     */
    public function transmissionEmailAction(Request $request, Devis $devis) {

        $rapportVisitePdf = $this->createRapportVisitePdf($devis);
        if ($request->get('output') == 'html') {

            return new Response($rapportVisitePdf->html, 200);
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $pm = $this->get('passage.manager');
        $parameters = $pm->getParameters();
        $appname = $this->container->getParameter('instanceapp');

        if(!$parameters['coordonnees'] || !$parameters['coordonnees']['email'] || !$parameters['coordonnees']['nom']){
            throw new Exception("Le paramétrage pour le mail d'envoie n'est pas correct.");
        }

        $fromEmail = $parameters['coordonnees']['email'];
        $fromName = $parameters['coordonnees']['nom'];

        $replyEmail = $parameters['coordonnees']['replyEmail'];

        $suject = "[".ucfirst($appname)."] - Rapport de Livraison du ".$devis->getDateDebut()->format("d/m/Y")." à ".$devis->getDateDebut()->format("H\hi");
        $body = $this->renderView(
            'devis/rapportEmail.html.twig',
            array('devis' => $devis)
        );

        $email = $devis->getEmailTransmission() ? $devis->getEmailTransmission() : $devis->getEtablissement()->getEmail();
        $message = \Swift_Message::newInstance()
            ->setSubject($suject)
            ->setFrom(array($fromEmail => $fromName))
            ->setTo(explode(";",$email))
            ->setReplyTo($replyEmail)
            ->setBody($body,'text/plain');

        if ($devis->getSecondEmailTransmission()) {
            $emailsTransmissions = explode(";",$email);
            $secondEmailsTransmission = explode(";",$devis->getSecondEmailTransmission());
            $to = array_merge($emailsTransmissions,$secondEmailsTransmission);
            $message->setTo($to);
        }

        $attachment = \Swift_Attachment::newInstance($this->get('knp_snappy.pdf')->getOutputFromHtml($rapportVisitePdf->html, $this->getPdfGenerationOptions()), $rapportVisitePdf->filename, 'application/pdf');

        $message->attach($attachment);

        try {
            $this->get('mailer')->send($message);
            $devis->setPdfNonEnvoye(false);
            $dm->flush();
        }

        catch(Exception $e) {
            var_dump('NO mailer config'); exit;
        }

        if($request->get('service')) {

            return $this->redirect($request->get('service'));
        }

        return $this->redirectToRoute('passage_etablissement', array('id' => $passage->getEtablissement()->getId()));
    }

}
