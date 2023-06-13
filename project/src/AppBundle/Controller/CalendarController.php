<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Tool\CalendarDateTool;
use AppBundle\Document\Compte;
use AppBundle\Document\Etablissement;
use AppBundle\Document\RendezVous;
use AppBundle\Document\CompteInfos;
use AppBundle\Document\Passage;
use AppBundle\Document\Devis;
use AppBundle\Manager\EtablissementManager;
use Behat\Transliterator\Transliterator;
use AppBundle\Type\PassageCreationType;
use AppBundle\Type\RendezVousType;
use Symfony\Component\HttpFoundation\Cookie;

class CalendarController extends Controller {

    /**
     * @Route("/calendrier/technicien/0", name="calendar", defaults={"etablissement" = null})
     * @Route("/calendrier/technicien/{id}", name="calendar")
     * @ParamConverter("etablissement", class="AppBundle:Etablissement")
     */
    public function calendarAction(Request $request, Etablissement $etablissement = null) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $planifiable = $request->get('planifiable', null);
        $technicien = $request->get('technicien');
        $techniciensFiltre = $request->get("techniciens", unserialize($request->cookies->get('techniciens', serialize(array()))));
        $date = $request->get('date', new \DateTime());
        $calendarTool = new CalendarDateTool(
            $date,
            $request->get('mode', CalendarDateTool::MODE_WEEK),
            $this->container->getParameter('calendar_extra')
        );

        if ($planifiable) {
            $planifiable = $this->guessTypePlanifiable($planifiable, $dm);
        }

        $technicienObj = null;
        if ($technicien) {
            $technicienObj = $dm->getRepository('AppBundle:Compte')->findOneById($technicien);
        }

        $techniciens = $dm->getRepository('AppBundle:Compte')->findAllUtilisateursCalendrier();
        $techniciensFinal = array();
        $techniciensOnglet = $techniciens;
        foreach($techniciens as $t) {
            if(in_array($t->getId(), $techniciensFiltre)) {
                $techniciensFinal[$t->getId()] = $t;
            }
        }

        if(count($techniciensFinal) > 0) {
            $techniciensOnglet = $techniciensFinal;
        }

        if (!$etablissement && $planifiable) {
            $etablissement = $planifiable->getEtablissement();
        }

        return $this->render('calendar/calendar.html.twig', [
            'calendarTool' => $calendarTool,
            'techniciensOnglet' => $techniciensOnglet,
            'techniciens' => $techniciens,
            'planifiable' => $planifiable,
            'technicien' => $technicien,
            'technicienObj' => $technicienObj,
            'etablissement' => $etablissement,
            'date' => $date,
            'mode' => $request->get('mode')
        ]);
    }

    /**
     * @Route("/calendrier/global", name="calendarManuel")
     */
    public function calendarManuelAction(Request $request) {
        $response = new Response();
        $dm = $this->get('doctrine_mongodb')->getManager();
        $passage = null;
        if ($request->get('passage')) {
            $passage = $dm->getRepository('AppBundle:Passage')->findOneById($request->get('passage'));
        }

        $calendarTool = new CalendarDateTool($request->get('date'), $request->get('mode'), $this->container->getParameter('calendar_extra'));

        $periodeStart = $calendarTool->getDateDebutSemaine('Y-m-d');
        $periodeStart = ((new \DateTime($periodeStart))->modify('-3 month'));
        $periodeStart = $periodeStart->format('Y-m-d');

        $periodeEnd = $calendarTool->getDateFinSemaine('Y-m-d');

        $rdvs = $dm->getRepository('AppBundle:RendezVous')->findByDate($periodeStart, $periodeEnd);

        $eventsDates = array();
        while (strtotime($periodeStart) <= strtotime($periodeEnd)) {
            $eventsDates[$periodeStart] = array();
            $periodeStart = date("Y-m-d", strtotime("+1 day", strtotime($periodeStart)));
        }

        $allTechniciens = $techniciens = $dm->getRepository('AppBundle:Compte')->findAllUtilisateursCalendrier();
        $techniciensFinal = array();

        $techniciensFiltre = $request->get("techniciens", unserialize($request->cookies->get('techniciens', serialize(array()))));
        $response->headers->setCookie(new Cookie('techniciens', serialize($techniciensFiltre), time() + (365 * 24 * 60 * 60)));

        foreach($techniciens as $technicien) {
            if(in_array($technicien->getId(), $techniciensFiltre)) {
                $techniciensFinal[$technicien->getId()] = $technicien;
            }
        }

        if(!count($techniciensFinal) > 0) {
            foreach($techniciens as $technicien) {
                $techniciensFinal[$technicien->getId()] = $technicien;
            }
        }
        $techniciens = $techniciensFinal;
        $techniciensOnglet = $techniciens;

        $passagesCalendar = array();
        $index = 0;
        foreach ($rdvs as $rdv) {
            foreach ($rdv->getParticipants() as $compte) {
                if (!$rdv->getDateFin()) {
                    continue;
                }

                if (!isset($passagesCalendar[$compte->getIdentifiant()])) {
                    $passagesCalendar[$compte->getIdentifiant()] = array();
                    $index = 0;
                }


                $dateDebut = new \DateTime($rdv->getDateDebut()->format('Y-m-d') . ' 06:00:00');
                $diffDebut = (strtotime($rdv->getDateDebut()->format('Y-m-dTH:i:s')) - strtotime($rdv->getDateDebut()->format('Y-m-d') . 'T06:00:00')) / 60;
                $diffFin = (strtotime($rdv->getDateFin()->format('Y-m-dTH:i:s')) - strtotime($rdv->getDateDebut()->format('Y-m-d') . 'T06:00:00')) / 60;

                $passageArr = array(
                    'start' => $rdv->getDateDebut()->format('Y-m-d\TH:i:s'),
                    'end' => $rdv->getDateFin()->format('Y-m-d\TH:i:s'),
                    'backgroundColor' => $compte->getCouleur(),
                    'textColor' => '#000',
                    'coefStart' => round($diffDebut / 30, 1),
                    'coefEnd' => round($diffFin / 30, 1),
                    'resume' => $rdv->getTitre(),
                    'id' => $rdv->getId(),
                    'debut' => $rdv->getDateDebut()->format('Y-m-d'),
                    'fin' => $rdv->getDateFin()->format('Y-m-d'),
                );
                $index++;

                $passagesCalendar[$compte->getIdentifiant()][] = $passageArr;
            }
        }

        foreach ($eventsDates as $date => $value) {
            foreach ($techniciens as $technicien) {
                $eventsDates[$date][$technicien->getIdentifiant()] = array();
                if (isset($passagesCalendar[$technicien->getIdentifiant()])) {
                    foreach ($passagesCalendar[$technicien->getIdentifiant()] as $p) {
                        if (preg_match("/^$date/", $p['start'])) {
                            $eventsDates[$date][$technicien->getIdentifiant()][] = $p;
                        } elseif ($date >= $p['debut'] && $date <= $p['fin']) {
                        	$p['coefStart'] = 0;
                        	if (preg_match("/^$date/", $p['end'])) {
                        		$diffFin = (strtotime($p['end']) - strtotime($date . 'T06:00:00')) / 60;
                        	} else {
                        		$diffFin = (strtotime($date . 'T18:00:00') - strtotime($date . 'T06:00:00')) / 60;
                        	}
                        	$p['coefEnd'] = round($diffFin / 30, 1);
                        	$eventsDates[$date][$technicien->getIdentifiant()][] = $p;
                        }
                    }
                }
            }
        }
        return $this->render('calendar/calendarManuel.html.twig', array('calendarTool' => $calendarTool, 'eventsDates' => $eventsDates, 'nbTechniciens' => count($techniciens), 'techniciens' => $techniciens, 'techniciensOnglet' => $techniciensOnglet, 'technicien' => null, 'allTechniciens' => $allTechniciens, 'passage' => $passage, 'date' => $request->get('date')), $response);
    }

    /**
     * @Route("/calendar/add/passage", name="calendarAdd", options={"expose" = "true"})
     */
    public function calendarAddAction(Request $request) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $rvm = $this->get('rendezvous.manager');

        $planifiable = $request->get('planifiable');
        $planifiable = $this->guessTypePlanifiable($planifiable, $dm);

        $technicien = $dm->getRepository('AppBundle:Compte')->findOneById($request->get('technicien'));
        $rdv = $rvm->createFromPlanifiable($planifiable);

        $rdv->setDateDebut(new \DateTime($request->get('start')));
        $rdv->setDateFin(new \DateTime($request->get('end')));
        $rdv->removeAllParticipants();
        $rdv->addParticipant($technicien);

        $dm->persist($rdv);
        $rdv->pushToPlanifiable();
        $dm->flush();

        $response = new Response(json_encode($this->buildEventObjCalendar($rdv,$technicien)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/calendar/add/libre", name="calendarAddLibre", options={"expose" = "true"})
     */
    public function calendarAddLibreAction(Request $request) {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $rdv = new RendezVous();
        $rdv->setDateDebut(new \DateTime($request->get('start')));
        $dateFin = clone $rdv->getDateDebut();
        $dateFin = $dateFin->modify("+1 hour");
        $rdv->setDateFin($dateFin);
        if($request->get('technicien')) {
            $rdv->addParticipant($dm->getRepository('AppBundle:Compte')->findOneById($request->get('technicien')));
        }

        $form = $this->createForm(new RendezVousType($dm), $rdv, array(
            'action' => $this->generateUrl('calendarAddLibre'),
            'method' => 'POST',
            'attr' => array('id' => 'eventForm'),
            'rdv_libre' => true
        ));

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {

            return $this->render('calendar/rendezVous.html.twig', array('rdv' => $rdv, 'form' => $form->createView()));
        }

        if ($form->get("all")->getData()) {
            $techniciens = $dm->getRepository('AppBundle:Compte')->findAllUtilisateursCalendrier();
            $rdv->removeAllParticipants();
            foreach ($techniciens as $technicien) {
                $rdv->addParticipant($technicien);
            }
        }

        $dm->persist($rdv);

        $dm->flush();

        return new Response(json_encode(array("success" => true)));
    }

    /**
     * @Route("/calendar/update", name="calendarUpdate", options={"expose" = "true"})
     */
    public function calendarUpdateAction(Request $request) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $rvm = $this->get('rendezvous.manager');

        $rdv = $dm->getRepository('AppBundle:RendezVous')->findOneById($request->get('id'));
        $technicien = $dm->getRepository('AppBundle:Compte')->findOneById($request->get('technicien'));

        $rdv->setDateDebut(new \DateTime($request->get('start')));
        $rdv->setDateFin(new \DateTime($request->get('end')));

        $dm->flush();

        $response = new Response(json_encode($this->buildEventObjCalendar($rdv,$technicien)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/calendar/populate", name="calendarPopulate", options={"expose" = "true"})
     */
    public function calendarPopulateAction(Request $request) {

        $dm = $this->get('doctrine_mongodb')->getManager();
        $technicien = $dm->getRepository('AppBundle:Compte')->findOneById($request->get('technicien'));
        $periodeStart = $request->get('start');
        $periodeStart = ((new \DateTime($periodeStart))->modify('-3 month'));
        $periodeStart = $periodeStart->format('Y-m-d');
        $periodeEnd = $request->get('end');
        $rdvs = $dm->getRepository('AppBundle:RendezVous')->findByDateAndParticipant($periodeStart, $periodeEnd, $technicien);
        $calendarData = array();

        foreach ($rdvs as $rdv) {
            $calendarData[] = $this->buildEventObjCalendar($rdv,$technicien);
        }

        $response = new Response(json_encode($calendarData));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/calendar/populateWithHolidays", name="calendarPopulateWithHolidays")
     */
    public function calendarPopulateWithHolidaysAction(Request $request) {
        $response = new Response('
          [
            {
              "title": "FERIE : \nJour de l\'an",
              "start": "2022-01-01T00:00:00",
              "end": "2022-01-01T23:59:59"
            },
            {
              "title": "FERIE : \nLundi de Pâques",
              "start": "2022-04-18T00:00:00",
              "end": "2022-04-18T23:59:59"
            },
            {
              "title": "FERIE : \nFête du Travail",
              "start": "2022-05-01T00:00:00",
              "end": "2022-05-01T23:59:59"
            },
            {
              "title": "FERIE : \nVictoire des alliés",
              "start": "2022-05-08T00:00:00",
              "end": "2022-05-08T23:59:59"
            },
            {
              "title": "FERIE : \njeudi de l\'ascension",
              "start": "2022-05-26T00:00:00",
              "end": "2022-05-26T23:59:59"
            },
            {
              "title": "FERIE : \nLundi de pentecôte",
              "start": "2022-06-06T00:00:00",
              "end": "2022-06-06T23:59:59"
            },
            {
              "title": "FERIE : \nFête nationale",
              "start": "2022-07-14T00:00:00",
              "end": "2022-07-14T23:59:59"
            },
            {
              "title": "FERIE : \nAssomption",
              "start": "2022-08-15T00:00:00",
              "end": "2022-08-15T23:59:59"
            },
            {
              "title": "FERIE : \nLa Toussaint",
              "start": "2022-11-01T00:00:00",
              "end": "2022-11-01T23:59:59"
            },
            {
              "title": "FERIE : \nArmistice",
              "start": "2022-11-11T00:00:00",
              "end": "2022-11-11T23:59:59"
            },
            {
              "title": "FERIE : \nNoël",
              "start": "2022-12-25T00:00:00",
              "end": "2022-12-25T23:59:59"
            },
            {
              "title": "FERIE : \nJour de l\'an",
              "start": "2023-01-01T00:00:00",
              "end": "2023-01-01T23:59:59"
            },
            {
              "title": "FERIE : \nLundi de Pâques",
              "start": "2023-04-10T00:00:00",
              "end": "2023-04-10T23:59:59"
            },
            {
              "title": "FERIE : \nFête du Travail",
              "start": "2023-05-01T00:00:00",
              "end": "2023-05-01T23:59:59"
            },
            {
              "title": "FERIE : \nVictoire des alliés",
              "start": "2023-05-08T00:00:00",
              "end": "2023-05-08T23:59:59"
            },
            {
              "title": "FERIE : \njeudi de l\'ascension",
              "start": "2023-05-18T00:00:00",
              "end": "2023-05-18T23:59:59"
            },
            {
              "title": "FERIE : \nLundi de pentecôte",
              "start": "2023-05-29T00:00:00",
              "end": "2023-05-29T23:59:59"
            },
            {
              "title": "FERIE : \nFête nationale",
              "start": "2023-07-14T00:00:00",
              "end": "2023-07-14T23:59:59"
            },
            {
              "title": "FERIE : \nAssomption",
              "start": "2023-08-15T00:00:00",
              "end": "2023-08-15T23:59:59"
            },
            {
              "title": "FERIE : \nLa Toussaint",
              "start": "2023-11-01T00:00:00",
              "end": "2023-11-01T23:59:59"
            },
            {
              "title": "FERIE : \nArmistice",
              "start": "2023-11-11T00:00:00",
              "end": "2023-11-11T23:59:59"
            },
            {
              "title": "FERIE : \nNoël",
              "start": "2023-12-25T00:00:00",
              "end": "2023-12-25T23:59:59"
            }
          ]'
        );
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }




    /**
     * @Route("/calendar/read", name="calendarRead")
     */
    public function calendarReadAction(Request $request) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $rvm = $this->get('rendezvous.manager');
        $technicien = $request->get('technicien');

        if($request->get('planifiable') && !$request->get('id')) {
            $planifiable = $request->get('planifiable');
            $planifiable = $this->guessTypePlanifiable($planifiable, $dm);
            $rdv = $rvm->createFromPlanifiable($planifiable);
        } elseif($request->get('id')) {
            $rdv = $dm->getRepository('AppBundle:RendezVous')->findOneById($request->get('id'));
        }

        if(!$rdv) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(sprintf("Le rendez-vous \"%s\" n'a pas été trouvé", $request->get('id')));
        }

        $documents = [];
        if ($rdv->getPassage()) {
            $documents = $this->get('attachement.manager')->getRepository()
                         ->findByPassageAndVisibleClient($rdv->getPassage());
        }

        $is_devis = false;
        if ($rdv->getPlanifiable() && $rdv->getPlanifiable()->getTypePlanifiable() === Devis::DOCUMENT_TYPE) {
            $template = 'calendar/rendezVousDevis.html.twig';
            $is_devis = true;
        } else {
            $template = 'calendar/rendezVous.html.twig';
        }

        $edition = (!$rdv->getPlanifiable() || (!$rdv->getPlanifiable()->isRealise()));

        if(!$edition && !$request->get('forceEdition', false)) {
            return $this->render($template, array(
                'rdv' => $rdv,
                'documents' => $documents,
                'service' => $request->get('service')
            ));
        }

        $form = $this->createForm(new RendezVousType($dm), $rdv, array(
            'action' => $this->generateUrl('calendarRead', array('id' => ($rdv->getId()) ? $rdv->getId() : null, 'planifiable' => ($rdv->getPlanifiable()) ? $rdv->getPlanifiable()->getId() : null, "forceEdition" => true)),
            'method' => 'POST',
            'attr' => array('id' => 'eventForm'),
            'rdv_libre' => !$rdv->getPlanifiable(),
            'is_devis' => $is_devis,
        ));

        $form->handleRequest($request);


        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render($template, array('rdv' => $rdv, 'form' => $form->createView(), 'service' => $request->get('service')));
        }

        if(!$rdv->getId()) {
            $dm->persist($rdv);
            if($rdv->getPlanifiable()) {
                $rdv->pushToPlanifiable();
            }
        }

        $dm->flush();

        return new Response(json_encode(array("success" => true)));
    }

    /**
     * @Route("/calendar/delete/{id}", name="calendarDelete")
     * @ParamConverter("rdv", class="AppBundle:RendezVous")
     */
    public function calendarDeleteAction(Request $request, RendezVous $rdv) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $technicien = $request->get('technicien');

        $dm->remove($rdv);

        $dm->flush();

        if($request->get('service')) {

            return $this->redirect($request->get('service'));
        }

        return $this->redirect($this->generateUrl('calendarManuel'));
    }

    /**
     * @Route("/calendar/{planifiable}/planifier", name="calendar_planifier")
     */
    public function planifierAction(Request $request, $planifiable) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $planifiable = $this->guessTypePlanifiable($planifiable, $dm);

        if (! count($planifiable->getTechniciens())) {
            return $this->redirectToRoute('calendarManuel', array('passage' => $planifiable->getId()));
        }

        $date = $planifiable->getDateForPlanif();

        return $this->redirectToRoute('calendar', array(
            'planifiable' => $planifiable->getId(),
            'id' => $planifiable->getEtablissement()->getId(),
            'date' => $date->format('d-m-Y'),
            'technicien' => $planifiable->getTechniciens()->first()->getId())
        );
    }

    /**
     * @Route("/calendar/listeRendezVous", name="listeRendezVous")
     */
    public function listeRendezVousAction(Request $request) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $date = new \DateTime();
        $dateFin = (new \DateTime())->modify("+1 month");
        $rdvs = $dm->getRepository('AppBundle:RendezVous')->findByDate($date->format('Y-m-d'), $dateFin->format('Y-m-d'));
        return $this->render('recherche/listRendezVous.html.twig', array('rdvs' => $rdvs));
    }


    /**
     * @Route("/calendar/listeRendezVousTechnicien", name="listeRendezVousTechnicien")
     */
    public function listeRendezVousTechnicienAction(Request $request) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $date = new \DateTime();
        $dateFin = (new \DateTime())->modify("+5 years");
        $technicien = $request->get('technicien');
        $technicienObj = null;
        if ($technicien) {
            $technicienObj = $dm->getRepository('AppBundle:Compte')->findOneById($technicien);
        }
        $rdvs = $dm->getRepository('AppBundle:RendezVous')->findByDateAndParticipant($date->format('Y-m-d'),$dateFin->format('Y-m-d'), $technicienObj);
        return $this->render('recherche/listRendezVous.html.twig', array('rdvs' => $rdvs, 'technicien' => $technicienObj));
    }

    public function buildEventObjCalendar($rdv,$technicien){
        $event = $rdv->getEventJson($technicien->getCouleur());
        $em = $this->get('etablissement.Manager');

        if ($rdv->getPlanifiable() && $rdv->getPlanifiable()->getEtablissement()) {
            $planifiableCoord = $rdv->getPlanifiable()->getEtablissement()->getAdresse()->getCoordonnees();
            $dateRetour = $rdv->getPlanifiable()->getDatePrevision()->format('Ym');
            $secteur = EtablissementManager::getRegion($rdv->getPlanifiable()->getEtablissement()->getAdresse()->getCodePostal());

            if(! $secteur) { $secteur = EtablissementManager::SECTEUR_PARIS; }
            $z = ($secteur == EtablissementManager::SECTEUR_SEINE_ET_MARNE)? '10' : '15';
            if (! $this->getParameter('secteurs')) {
                $secteur = "0";
            }

            if ($rdv->getPlanifiable()->getDateDebut()) {
                $dateRetour = $rdv->getPlanifiable()->getDateDebut()->format('Ym');
            }
            if($rdv->getPlanifiable()->getTypePlanifiable() == Passage::DOCUMENT_TYPE){
              $event->retourMap = $this->generateUrl('passage_secteur', array('secteur' => $secteur, 'mois' => $dateRetour,'lat' => $planifiableCoord->getLat(),'lon' => $planifiableCoord->getLon(),'zoom' => $z));
              if ($secteur) {
                  $event->retourMap = $this->generateUrl('passage_secteur',array('secteur' => $secteur, 'mois' => $dateRetour,'lat' => $planifiableCoord->getLat(),'lon' => $planifiableCoord->getLon(),'zoom' => $z));
              }
            }else{
              $event->livraison = true;
            }
        }

        return $event;
    }

    private function guessTypePlanifiable($planifiable, $dm)
    {
        $type_planifiable = ucfirst(strtolower(strtok($planifiable, '-')));
        return $dm->getRepository('AppBundle:'.$type_planifiable)->findOneById($planifiable);
    }
}
