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

        $passage = null;
        if ($request->get('passage')) {
            $passage = $dm->getRepository('AppBundle:Passage')->findOneById($request->get('passage'));
        }

        $technicien = $request->get('technicien');
        $technicienObj = null;
        if ($technicien) {
            $technicienObj = $dm->getRepository('AppBundle:Compte')->findOneById($technicien);
        }
        $techniciens = $dm->getRepository('AppBundle:Compte')->findAllUtilisateursCalendrier();
        
        $techniciensFiltre = $request->get("techniciens", unserialize($request->cookies->get('techniciens', serialize(array()))));
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


        $techniciensFiltre = $request->get("techniciens", unserialize($request->cookies->get('techniciens', serialize(array()))));
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

        $date = $request->get('date', new \DateTime());
        $calendarTool = new CalendarDateTool($date, $request->get('mode', CalendarDateTool::MODE_WEEK));

        $etablissement = null;
        if ($passage) {
            $etablissement = $passage->getEtablissement();
        }

        return $this->render('calendar/calendar.html.twig', array('calendarTool' => $calendarTool, 'techniciensOnglet' => $techniciensOnglet, 'techniciens' => $techniciens, 'passage' => $passage, 'technicien' => $technicien, 'technicienObj' => $technicienObj, 'etablissement' => $etablissement, 'date' => $date, 'mode' => $request->get('mode')));
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

        $calendarTool = new CalendarDateTool($request->get('date'), $request->get('mode'));

        $periodeStart = $calendarTool->getDateDebutSemaine('Y-m-d');
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

        if(count($techniciensFinal) > 0) {
            $techniciens = $techniciensFinal;
        }
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
        $pm = $this->get('passage.manager');
        $rvm = $this->get('rendezvous.manager');

        $passage = $dm->getRepository('AppBundle:Passage')->findOneById($request->get('passage'));

        $technicien = $dm->getRepository('AppBundle:Compte')->findOneById($request->get('technicien'));
        $rdv = $rvm->createFromPassage($passage);

        $rdv->setDateDebut(new \DateTime($request->get('start')));
        $rdv->setDateFin(new \DateTime($request->get('end')));
        $rdv->removeAllParticipants();
        $rdv->addParticipant($technicien);

        $dm->persist($rdv);
        $rdv->pushToPassage();
      //  $pm->updateNextPassageAPlannifier($rdv->getPassage());
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
     * @Route("/calendar/read", name="calendarRead")
     */
    public function calendarReadAction(Request $request) {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $pm = $this->get('passage.manager');
        $rvm = $this->get('rendezvous.manager');

        $technicien = $request->get('technicien');
        if($request->get('passage') && !$request->get('id')) {
            $passage = $dm->getRepository('AppBundle:Passage')->findOneById($request->get('passage'));
            $rdv = $rvm->createFromPassage($passage);
        } elseif($request->get('id')) {
            $rdv = $dm->getRepository('AppBundle:RendezVous')->findOneById($request->get('id'));
        }

        if(!$rdv) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(sprintf("Le rendez-vous \"%s\" n'a pas été trouvé", $request->get('id')));
        }

        $edition = (!$rdv->getPassage() || (!$rdv->getPassage()->isRealise()));

        if(!$edition && !$request->get('forceEdition', false)) {

            return $this->render('calendar/rendezVous.html.twig', array('rdv' => $rdv, 'service' => $request->get('service')));
        }

        $form = $this->createForm(new RendezVousType($dm), $rdv, array(
            'action' => $this->generateUrl('calendarRead', array('id' => ($rdv->getId()) ? $rdv->getId() : null, 'passage' => ($rdv->getPassage()) ? $rdv->getPassage()->getId() : null, "forceEdition" => true)),
            'method' => 'POST',
            'attr' => array('id' => 'eventForm'),
            'rdv_libre' => !$rdv->getPassage(),
        ));

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {

            return $this->render('calendar/rendezVous.html.twig', array('rdv' => $rdv, 'form' => $form->createView(), 'service' => $request->get('service')));
        }

        if(!$rdv->getId()) {
            $dm->persist($rdv);
            if($rdv->getPassage()) {
                $rdv->pushToPassage();
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
        $pm = $this->get('passage.manager');
        $technicien = $request->get('technicien');


        $dm->remove($rdv);

        $dm->flush();

        if($request->get('service')) {

            return $this->redirect($request->get('service'));
        }

        return $this->redirect($this->generateUrl('calendarManuel'));
    }

    public function buildEventObjCalendar($rdv,$technicien){
      $event = $rdv->getEventJson($technicien->getCouleur());
      $em = $this->get('etablissement.Manager');
      if($rdv->getPassage() && $rdv->getPassage()->getEtablissement()){
        $passageCoord = $rdv->getPassage()->getEtablissement()->getAdresse()->getCoordonnees();
        $secteur = EtablissementManager::getRegion($rdv->getPassage()->getEtablissement()->getAdresse()->getCodePostal());
        if(!$secteur){ $secteur = EtablissementManager::SECTEUR_PARIS; }
        $z = ($secteur == EtablissementManager::SECTEUR_SEINE_ET_MARNE)? '10' : '15';
        $dateRetour = $rdv->getPassage()->getDatePrevision()->format('Ym');
        if($rdv->getPassage()->getDateDebut()){
            $dateRetour = $rdv->getPassage()->getDateDebut()->format('Ym');
        }
        $event->retourMap = $this->generateUrl('passage',array('secteur' => $secteur, 'mois' => $dateRetour,'lat' => $passageCoord->getLat(),'lon' => $passageCoord->getLon(),'zoom' => $z));
      }
      return $event;
    }

}
