<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Document\Etablissement;
use AppBundle\Document\Societe;
use AppBundle\Document\Contrat;
use AppBundle\Type\ContratChoiceType;
use AppBundle\Type\ContratType;
use AppBundle\Type\ContratMarkdownType;
use AppBundle\Type\ContratGeneratorType;
use AppBundle\Type\ContratAcceptationType;
use AppBundle\Type\SocieteChoiceType;
use AppBundle\Manager\ContratManager;
use AppBundle\Manager\PassageManager;
use Knp\Snappy\Pdf;
use AppBundle\Type\ContratAnnulationType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use AppBundle\Type\ReconductionFiltresType;
use AppBundle\Type\ReconductionType;

class ContratController extends Controller {

    /**
     * @Route("/contrat", name="contrat")
     */
    public function indexAction(Request $request) {

        $dm = $this->get('doctrine_mongodb')->getManager();

        $contrats = $this->get('contrat.manager')->getRepository()->findLast();
        return $this->render('contrat/index.html.twig', array('contrats' => $contrats));
    }

    /**
     * @Route("/contrat/societe-choix", name="contrat_societe_choice")
     */
    public function societeChoiceAction(Request $request) {
        $formData = $request->get('societe_choice');
        return $this->redirectToRoute('contrats_societe', array('id' => $formData['societes']));
    }

    /**
     * @Route("/contrat/{id}/societe", name="contrats_societe")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function societeAction(Request $request, Societe $societe) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $contrats = $this->get('contrat.manager')->getRepository()->findBy(array('societe' => $societe->getId()), array('dateDebut' => 'DESC'));
        usort($contrats, array("AppBundle\Document\Contrat", "cmpContrat"));

        return $this->render('contrat/societe.html.twig', array('societe' => $societe, 'contrats' => $contrats));
    }

    /**
     * @Route("/contrat/{id}/creation", name="contrat_creation")
     * @ParamConverter("societe", class="AppBundle:Societe")
     */
    public function creationAction(Request $request, Societe $societe) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $contrat = $this->get('contrat.manager')->createBySociete($societe);

        return $this->modificationAction($request, $contrat);
    }

    /**
     * @Route("/contrat/{id}/creation/etablissement", name="contrat_creation_etablissement")
     * @ParamConverter("etablissement", class="AppBundle:Etablissement")
     */
    public function creationFromEtablissementAction(Request $request, Etablissement $etablissement) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $contrat = $this->get('contrat.manager')->createBySociete($etablissement->getSociete(), null, $etablissement);

        return $this->modificationAction($request, $contrat);
    }

    /**
     * @Route("/contrat/{id}/modification", name="contrat_modification")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function modificationAction(Request $request, Contrat $contrat) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $contratManager = new ContratManager($dm);

        $form = $this->createForm(new ContratType($this->container, $dm), $contrat, array(
            'action' => "",
            'method' => 'POST',
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contrat = $form->getData();
            if(!$contrat->getStatut() || $contrat->isBrouillon()) {
                $contrat->setStatut(ContratManager::STATUT_EN_ATTENTE_ACCEPTATION);
            }

            $contrat->updateObject();
            $contrat->updatePrestations($dm);
            $contrat->updateProduits($dm);

            if(!$contrat->getId()) {
                $dm->persist($contrat);
            }
            $dm->flush();
            return $this->redirectToRoute('contrat_acceptation', array('id' => $contrat->getId()));
        }
        return $this->render('contrat/modification.html.twig', array('contrat' => $contrat, 'form' => $form->createView(), 'societe' => $contrat->getSociete()));
    }

    /**
     * @Route("/contrat/{id}/acceptation", name="contrat_acceptation")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function acceptationAction(Request $request, Contrat $contrat) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $contratManager = new ContratManager($dm);
        $oldTechnicien = $contrat->getTechnicien();
        $isBrouillon = $request->get('brouillon');
        $form = $this->createForm(new ContratAcceptationType($dm, $contrat), $contrat, array(
            'action' => $this->generateUrl('contrat_acceptation', array('id' => $contrat->getId())),
            'method' => 'POST',
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $contrat = $form->getData();
            if(!$isBrouillon && (is_null($contrat->getDateAcceptation()) || is_null($contrat->getDateDebut()))) {
              throw new \Exception("Le contrat doit avoir date d'acceptation et date de début");
            }

            if ($contrat->isEnAttenteAcceptation() && !$isBrouillon && $contrat->getDateDebut()) {
                $contratManager->generateAllPassagesForContrat($contrat);
                $dateFin = clone $contrat->getDateDebut();
                $dateFin = $dateFin->modify("+" . $contrat->getDuree() . " month");
                $contrat->setDateFin($dateFin);
                $contrat->setStatut(ContratManager::STATUT_EN_COURS);
                $dm->persist($contrat);
                $dm->flush();
                return $this->redirectToRoute('contrat_visualisation', array('id' => $contrat->getId()));
            } elseif(!$isBrouillon) {
                if ((!$oldTechnicien) || $oldTechnicien->getId() != $contrat->getTechnicien()->getId()) {
                    $contrat->changeTechnicien($contrat->getTechnicien());
                }
                if ($contrat->getDateDebut()) {
                	$dateFinCalcule = \DateTime::createFromFormat('Y-m-d H:i:s',$contrat->getDateDebut()->format('Y-m-d')." 00:00:00");
                	$contrat->setDateFin($dateFinCalcule->modify("+" . $contrat->getDuree() . " month"));
                }
                $dm->persist($contrat);
                $dm->flush();
                return $this->redirectToRoute('contrat_visualisation', array('id' => $contrat->getId()));
            }

            $dm->persist($contrat);
            $dm->flush();
            return $this->redirectToRoute('contrat_visualisation', array('id' => $contrat->getId()));
        }
        $factures = $contratManager->getAllFactureForContrat($contrat);
        return $this->render('contrat/acceptation.html.twig', array('contrat' => $contrat, 'factures' => $factures, 'form' => $form->createView(), 'societe' => $contrat->getSociete()));
    }

    /**
     * @Route("/contrat/{id}/copie", name="contrat_copie")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function copieAction(Request $request, Contrat $contrat) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $contratReconduit = $contrat->copier();
        $dm->persist($contratReconduit);
        $dm->flush();

        return $this->redirectToRoute('contrat_acceptation', array('id' => $contratReconduit->getId()));
    }

    /**
     * @Route("/contrats-reconduction-historique", name="contrats_reconduction_historique")
     */
    public function reconductionHistoriqueAction(Request $request) {
      $dm = $this->get('doctrine_mongodb')->getManager();

      $contratManager = new ContratManager($dm);
      $listeReconductions = $contratManager->getNbContratsReconduitByDateReconduction();
      return $this->render('contrat/reconduction_historique.html.twig',array('listeReconductions' => $listeReconductions));

    }
    /**
     * @Route("/contrat/{id}/reconduction", name="contrat_reconduction")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function reconductionAction(Request $request, Contrat $contrat) {
        if (!$contrat->isReconductible()) {
            return $this->redirectToRoute('contrat_visualisation', array('id' => $contrat->getId()));
        }
        $augmentation = floatval($request->get("augmentation", 0));
        if(!is_float($augmentation)) {

            throw new \Exception("L'augmentation n'est pas un nombre");
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $etablissements = $contrat->getEtablissements();
        $contratReconduit = $contrat->reconduire($augmentation);
        $dm->persist($contratReconduit);
        $dm->flush();
        $this->get('contrat.manager')->copyPassagesForContratReconduit($contratReconduit,$contrat);
        $dm->persist($contratReconduit);
        $contrat->setReconduit(true);
        $dm->persist($contratReconduit);
        $dm->flush();

        return $this->redirectToRoute('contrat_visualisation', array('id' => $contratReconduit->getId()));
    }



    /**
     * @Route("/contrat/{id}/annulation", name="contrat_annulation")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function annulationAction(Request $request, Contrat $contrat) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        if (!$contrat->isAnnulable()) {
            return $this->redirectToRoute('contrat_visualisation', array('id' => $contrat->getId()));
        }
        $contrat->setDateResiliation(new \DateTime());
        $form = $this->createForm(new ContratAnnulationType($dm, $contrat), $contrat, array(
            'action' => $this->generateUrl('contrat_annulation', array('id' => $contrat->getId())),
            'method' => 'POST',
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contratForm = $form->getData();
            $contrat->setTypeContratOriginal($contrat->getTypeContrat());
            $contrat->setTypeContrat(ContratManager::TYPE_CONTRAT_ANNULE);
            $forcerAnnulationPassages = $form['forcerAnnulationPassages']->getData() == 1;
            foreach($contrat->getContratPassages() as $etb => $passagesByEtb) {
                foreach ($passagesByEtb->getPassages() as $passage) {
                    if ($passage->isRealise() || $passage->isAnnule()) {
                        continue;
                    }
                    if($passage->getDatePrevision()->format('Ymd') <= $contrat->getDateResiliation()->format('Ymd') && !$forcerAnnulationPassages) {
                        continue;
                    }
                    $passage->setStatut(PassageManager::STATUT_ANNULE);
                    $passage->setCommentaire("Annuler suite à l'annulation du contrat");
                }
            }
            foreach ($contrat->getMouvements() as $mouvement) {
            	if (!$mouvement->isFacture()) {
            		$contrat->removeMouvement($mouvement);
            	}
            }
            $commentaire = "";
            if ($contrat->getCommentaire()) {
                $commentaire.= $contrat->getCommentaire() . "\n";
            }
            $commentaire.= $form['commentaireResiliation']->getData();
            $contrat->setCommentaire($commentaire);
            $dm->flush();

            return $this->redirectToRoute('contrat_visualisation', array('id' => $contrat->getId()));
        }

        return $this->render('contrat/annulation.html.twig', array('form' => $form->createView(), 'contrat' => $contrat, 'societe' => $contrat->getSociete()));
    }

    /**
     * @Route("/contrat/{id}/reactivation", name="contrat_reactivation")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function reactivationAction(Request $request, Contrat $contrat) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $contrat->setTypeContrat($contrat->getTypeContratOriginal());
        $contrat->setTypeContratOriginal(null);
        $contrat->setDateResiliation(null);
        $commentaire = null;
        if($contrat->getCommentaire()) {
            $commentaire = $contrat->getCommentaire()."\n";
        }
        $commentaire .= "Contrat réactivé le ".date("d/m/Y");
        $contrat->setCommentaire($commentaire);

        foreach($contrat->getContratPassages() as $etb => $passagesByEtb) {
            foreach ($passagesByEtb->getPassages() as $passage) {
                if (!$passage->isAnnule()) {
                    continue;
                }

                $passage->setStatut(PassageManager::STATUT_A_PLANIFIER);
            }
        }

        $dm->flush();

        return $this->redirectToRoute('contrat_visualisation', array('id' => $contrat->getId()));
    }

    /**
     * @Route("/contrat/{id}/visualisation", name="contrat_visualisation")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function visualisationAction(Request $request, Contrat $contrat) {
        if($contrat->isEnAttenteAcceptation()) {

            return $this->redirectToRoute('contrat_acceptation', array('id' => $contrat->getId()));
        }


        $dm = $this->get('doctrine_mongodb')->getManager();

        $factures = $dm->getRepository('AppBundle:Facture')->findAllByContrat($contrat);

        return $this->render('contrat/visualisation.html.twig', array('contrat' => $contrat, 'factures' => $factures, 'societe' => $contrat->getSociete()));
    }

    /**
     * @Route("/contrat/{id}/markdown", name="contrat_markdown")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function markdownAction(Request $request, Contrat $contrat) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('contrat.manager');
        if (!$contrat->getMarkdown()) {
            $contrat->setMarkdown($this->renderView('contrat/contrat.markdown.twig', array('contrat' => $contrat, 'contratManager' => $cm)));
            $dm->persist($contrat);
            $dm->flush();
        }

        $formMarkdown = $this->createForm(new ContratMarkdownType(), $contrat, array(
            'action' => $this->generateUrl('contrat_markdown', array('id' => $contrat->getId())),
            'method' => 'POST',
        ));

        $formMarkdown->handleRequest($request);
        if ($formMarkdown->isSubmitted() && $formMarkdown->isValid()) {
            $contrat = $formMarkdown->getData();
            $dm->persist($contrat);
            $dm->flush();
        }

        $formGenerator = $this->createForm(new ContratGeneratorType(), $contrat, array(
            'action' => $this->generateUrl('contrat_markdown', array('id' => $contrat->getId())),
            'method' => 'POST',
        ));

        $formGenerator->handleRequest($request);
        if ($formGenerator->isSubmitted() && $formGenerator->isValid()) {
            $contrat = $formGenerator->getData();
            $contrat->setMarkdown($this->renderView('contrat/contrat.markdown.twig', array('contrat' => $contrat, 'contratManager' => $cm)));
            $dm->persist($contrat);
            $dm->flush();
        }


        return $this->render('contrat/visualisation.markdown.twig', array('contrat' => $contrat, 'formMarkdown' => $formMarkdown->createView(), 'formGenerator' => $formGenerator->createView()));
    }

    /**
     * @Route("/contrat/{id}/suppression", name="contrat_suppression")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function suppressionAction(Request $request, Contrat $contrat) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        if (!$contrat->isModifiable()) {
            throw $this->createNotFoundException();
        }
        $societeId = $contrat->getSociete()->getId();
        foreach ($contrat->getContratPassages() as $contratPassages) {
            foreach ($contratPassages->getPassages() as $passage) {
                $dm->remove($passage);
            }
        }
        $dm->remove($contrat);
        $dm->flush();
        return $this->redirectToRoute('contrats_societe', array('id' => $societeId));
    }

    /**
     * @Route("/contrat/{id}/generation-mouvement", name="contrat_generation_mouvement")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function generationMouvementAction(Request $request, Contrat $contrat) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $contrat->generateMouvement();
        $dm->persist($contrat);
        $dm->flush();

        return $this->redirectToRoute('facture_societe', array('id' => $contrat->getSociete()->getId()));
    }

    /**
     * @Route("/contrat/{id}/pdf", name="contrat_pdf")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function pdfAction(Request $request, Contrat $contrat) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('contrat.manager');

        $contrat->setMarkdown($this->renderView('contrat/contrat.markdown.twig', array('contrat' => $contrat, 'contratManager' => $cm)));
        $dm->persist($contrat);
        $dm->flush();

        $header = $this->renderView('contrat/pdf-header.html.twig', array(
            'contrat' => $contrat
        ));
        $footer = $this->renderView('contrat/pdf-footer.html.twig', array(
            'contrat' => $contrat
        ));
        $html = $this->renderView('contrat/pdf.html.twig', array(
            'contrat' => $contrat
        ));
        if ($request->get('output') == 'html') {

            return new Response($html, 200);
        }

        return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($html, array(
                    'footer-html' => $footer,
                    'header-html' => $header,
                    'margin-right' => 0,
                    'margin-left' => 0,
                    'margin-top' => 38,
                    'margin-bottom' => 38,
                    'page-size' => "A4"
                )), 200, array(
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="contrat-' . $contrat->getNumeroArchive() . '.pdf"'
                )
        );
    }


    /**
     * @Route("/contrat/{id}/force-reconduction", name="contrat_force_reconduction")
     * @ParamConverter("contrat", class="AppBundle:Contrat")
     */
    public function typePonctuelAction(Request $request, Contrat $contrat) {
    	$dm = $this->get('doctrine_mongodb')->getManager();
    	$contrat->setReconduit(true);
    	$dm->flush();

        if($request->isXmlHttpRequest()) {

            return new Response();
        }

    	return $this->redirectToRoute('contrats_reconduction_massive');
    }

    /**
     * @Route("/contrat/export-pca", name="pca_export")
     */
    public function exportPcaAction(Request $request) {
        ini_set('memory_limit', '-1');
      // $response = new StreamedResponse();
        $formRequest = $request->request->get('form');
        $dateDebutString = $formRequest['dateDebut']." 23:59:59";

        $dateDebut = \DateTime::createFromFormat('d/m/Y H:i:s',$dateDebutString);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('contrat.manager');
        $pca_for_csv = $cm->getPcaForCsv($dateDebut);

        $filename = sprintf("export_pca_du_%s.csv", $dateDebut->format("Y-m-d"));
        $handle = fopen('php://memory', 'r+');

        foreach ($pca_for_csv as $paiement) {
            fputcsv($handle, $paiement,';');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $response = new Response(utf8_decode($content), 200, array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ));
        $response->setCharset('UTF-8');

        return $response;
    }

    /**
     * @Route("/contrats-reconduire-massivement", name="contrats_reconduire_massivement")
     */
    public function contrats_reconduire_massivement(Request $request) {
        set_time_limit(0);
        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('contrat.manager');
        $contratsAReconduire = array();
        $formRequest = $request->request->get('reconduction');
        $augmentation = (isset($formRequest['augmentation']))? $formRequest['augmentation'] : 0;
        foreach ($formRequest as $key => $value) {
          if(preg_match("/^CONTRAT-/",$key)){
              $contratsAReconduire[$key] = $cm->getRepository()->findOneById($key);
          }
        }

        foreach ($contratsAReconduire as $contrat) {
            if($contrat->getReconduit()) {

                continue;
            }
            $contratReconduit = $contrat->reconduire($augmentation);
            $dm->persist($contratReconduit);
            $contrat->setReconduit(true);
            $cm->copyPassagesForContratReconduit($contratReconduit,$contrat);
            $dm->flush();
        }

        return $this->redirectToRoute('contrats_reconduction_massive');
    }

    /**
     * @Route("/contrats/erreurs", name="contrats_erreurs")
     */
    public function contratsErreursAction(Request $request) {
        ini_set('memory_limit', '1024M');
        $dm = $this->get('doctrine_mongodb')->getManager();

        $contrats = $dm->getRepository('AppBundle:Contrat')->findAllErreurs();

        return $this->render('contrat/erreurs.html.twig', array('contrats' => $contrats));
    }


    /**
     * @Route("/contrats-reconduction", name="contrats_reconduction_massive")
     */
    public function reconductionMassiveAction(Request $request) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $cm = $this->get('contrat.manager');

        $dateRecondution = new \DateTime();
        $typeContrat = null;
        $societe = null;

        $formContratsAReconduire = $this->createForm(new ReconductionFiltresType(), null, array(
        		'action' => $this->generateUrl('contrats_reconduction_massive'),
        		'method' => 'post',
        ));
        $formContratsAReconduire->handleRequest($request);
        if ($formContratsAReconduire->isSubmitted() && $formContratsAReconduire->isValid()) {

        	$formValues =  $formContratsAReconduire->getData();
        	$dateRecondution = $formValues["dateRenouvellement"];
        	$typeContrat = $formValues["typeContrat"];
        	$societe = $formValues["societe"];
        }

        $contratsAReconduire = $cm->getRepository()->findContratsAReconduire($typeContrat, $dateRecondution, $societe);
        $formReconduction = $this->createForm(new ReconductionType($contratsAReconduire), null, array(
        		'action' => $this->generateUrl('contrats_reconduire_massivement'),
        		'method' => 'post',
        ));;

        return $this->render('contrat/reconduction_massive.html.twig',array('contratsAReconduire' => $contratsAReconduire,
                                                                            'dateRecondution' => $dateRecondution,
                                                                            'formContratsAReconduire' => $formContratsAReconduire->createView(),
                                                                            'formReconduction' => $formReconduction->createView()));
    }

    /**
     * @Route("/contrat/export-rentabilite", name="rentabilite_export")
     */
	public function exportRentabiliteAction(Request $request) {
        ini_set('memory_limit', '256M');
    	// $response = new StreamedResponse();
    	$formRequest = $request->request->get('form');
    	$client = (isset($formRequest['societe']) && $formRequest['societe'] && ($formRequest['societe']!= ""))? $formRequest['societe'] : null;
    	if ($client) {
    		$ec = explode(',', $client);
    		$client = trim($ec[count($ec) -1]);
    	}
    	$pdf = (isset($formRequest["pdf"]) && $formRequest["pdf"]);

    	$dateDebutString = $formRequest['dateDebut']." 00:00:00";
    	$dateFinString = $formRequest['dateFin']." 23:59:59";

    	$dateDebut = \DateTime::createFromFormat('d/m/Y H:i:s',$dateDebutString);
    	$dateFin = \DateTime::createFromFormat('d/m/Y H:i:s',$dateFinString);

    	$cm = $this->get('contrat.manager');

    	$statsForRentabilite = $cm->getStatsForRentabiliteForCsv($dateDebut,$dateFin,$client);

    	if(!$pdf){
    		$filename = sprintf("export_details_rentabilite_du_%s_au_%s.csv", $dateDebut->format("Y-m-d"),$dateFin->format("Y-m-d"));
    		$handle = fopen('php://memory', 'r+');

    		foreach ($statsForRentabilite as $stat) {
    			fputcsv($handle, $stat,';');
    		}

    		rewind($handle);
    		$content = stream_get_contents($handle);
    		fclose($handle);

    		$response = new Response(utf8_decode($content), 200, array(
    				'Content-Type' => 'text/csv',
    				'Content-Disposition' => 'attachment; filename=' . $filename,
    		));
    		$response->setCharset('UTF-8');

    		return $response;
    	}else{
    		$html = $this->renderView('contrat/pdfStatsRentabilite.html.twig', array(
    				'statsForRentabilite' => $statsForRentabilite,
    				'dateDebut' => $dateDebut,
    				'dateFin' => $dateFin
    		));


    		$filename = sprintf("export_stats_rentabilite_du_%s_au_%s.pdf",  $dateDebut->format("Y-m-d"), $dateFin->format("Y-m-d"));

    		if ($request->get('output') == 'html') {

    			return new Response($html, 200);
    		}

    		return new Response(
    				$this->get('knp_snappy.pdf')->getOutputFromHtml($html, array(
    						'disable-smart-shrinking' => null,
    						'encoding' => 'utf-8',
    						'margin-left' => 10,
    						'margin-right' => 10,
    						'margin-top' => 10,
    						'margin-bottom' => 10),$this->getPdfGenerationOptions()), 200, array(
    								'Content-Type' => 'application/pdf',
    								'Content-Disposition' => 'attachment; filename="' . $filename . '"'
    						));
    	}
    }



    /**
     * @Route("/contrats-commerciaux/export", name="commerciaux_export")
     */
    public function exportCommerciauxAction(Request $request) {

    	// $response = new StreamedResponse();
    	$formRequest = $request->request->get('form');
    	$commercial = (isset($formRequest['commercial']) && $formRequest['commercial'] && ($formRequest['commercial']!= ""))?
    	$formRequest['commercial'] : null;
    	$pdf = (isset($formRequest["pdf"]) && $formRequest["pdf"]);

    	$dateDebutString = $formRequest['dateDebut']." 00:00:00";
    	$dateFinString = $formRequest['dateFin']." 23:59:59";

    	$dateDebut = \DateTime::createFromFormat('d/m/Y H:i:s',$dateDebutString);
    	$dateFin = \DateTime::createFromFormat('d/m/Y H:i:s',$dateFinString);

    	$cm = $this->get('contrat.manager');

    	$statsForCommerciaux = $cm->getStatsForCommerciauxForCsv($dateDebut,$dateFin,$commercial);

    	if(!$pdf){
    		$filename = sprintf("export_details_commerciaux_du_%s_au_%s.csv", $dateDebut->format("Y-m-d"),$dateFin->format("Y-m-d"));
    		$handle = fopen('php://memory', 'r+');

    		foreach ($statsForCommerciaux as $stat) {
    			fputcsv($handle, $stat,';');
    		}

    		rewind($handle);
    		$content = stream_get_contents($handle);
    		fclose($handle);

    		$response = new Response(utf8_decode($content), 200, array(
    				'Content-Type' => 'text/csv',
    				'Content-Disposition' => 'attachment; filename=' . $filename,
    		));
    		$response->setCharset('UTF-8');

    		return $response;
    	}else{
    		$html = $this->renderView('contrat/pdfStatsCommerciaux.html.twig', array(
    				'statsForCommerciaux' => $statsForCommerciaux,
    				'dateDebut' => $dateDebut,
    				'dateFin' => $dateFin
    		));


    		$filename = sprintf("export_stats_commerciaux_du_%s_au_%s.pdf",  $dateDebut->format("Y-m-d"), $dateFin->format("Y-m-d"));

    		if ($request->get('output') == 'html') {

    			return new Response($html, 200);
    		}

    		return new Response(
    				$this->get('knp_snappy.pdf')->getOutputFromHtml($html, array(
    						'disable-smart-shrinking' => null,
    						'encoding' => 'utf-8',
    						'orientation' => 'landscape',
    						'default-header' => false,
    						'margin-left' => 10,
    						'margin-right' => 10,
    						'margin-top' => 10,
    						'margin-bottom' => 10),$this->getPdfGenerationOptions()), 200, array(
    								'Content-Type' => 'application/pdf',
    								'Content-Disposition' => 'attachment; filename="' . $filename . '"'
    						));
    	}
    }


    public function getPdfGenerationOptions() {
    	return array('disable-smart-shrinking' => null, 'encoding' => 'utf-8', 'margin-left' => 3, 'margin-right' => 3, 'margin-top' => 4, 'margin-bottom' => 4);
    }

}
