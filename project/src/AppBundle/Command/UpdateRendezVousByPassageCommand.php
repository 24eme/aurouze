<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ImportEtablissement
 *
 * @author mathurin
 */

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRendezVousByPassageCommand extends ContainerAwareCommand {

    protected $dm;

    protected function configure() {
        $this
                ->setName('update:rendezvous-par-passage')
                ->setDescription('Création des rendez vous par passage');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.default_document_manager');

        $pm = $this->getContainer()->get('passage.manager');
        $rvm = $this->getContainer()->get('rendezvous.manager');

        $passages = $pm->getRepository()->findBy(array("dateFin" => array('$ne' => null), "rendezVous" => null));
        $i = 0;

        foreach($passages as $passage) {
            if($passage->getDateDebut() && $passage->getDateFin()){
              if(!$passage->getRendezVous()) {
                $rdv = $rvm->createFromPlanifiable($passage);
                $this->dm->persist($rdv);
                $idRdv = $rdv->getId();
                echo "Création du rendez-vous $idRdv \n";
                if ($i >= 100) {
                    $this->dm->flush();
                    $i = 0;
                }
                $i++;
            }else{
              $idRdv = $passage->getRendezVous()->getId();
              echo "Le rendez-vous $idRdv existe déjà\n";
            }
          }
        }

        $this->dm->flush();
    }

}
