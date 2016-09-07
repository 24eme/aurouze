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
use AppBundle\Manager\PassageManager;
use Symfony\Component\Console\Helper\ProgressBar;
use AppBundle\Manager\ContratManager;

class ContratsUpdateCommand extends ContainerAwareCommand {

    protected $dm;

    protected function configure() {
        $this
                ->setName('contrats:update')
                ->setDescription('Mise à jour de contrats');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.default_document_manager');

        $contrats = $this->dm->getRepository('AppBundle:Contrat')->findAll();

        $i = 0;
        foreach ($contrats as $contrat) {
            echo $contrat->getId()."\n";
            $contrat->preUpdate();
            foreach($contrat->getContratPassages() as $passages) {
                foreach($passages->getPassages() as $passage) {
                    if($passage->isAPlanifie()) {
                        $passagePrec = $this->getContainer()->get('passage.manager')->passagePrecedentRealiseSousContrat($passage);
                        if($passagePrec) {
                            $passage->setDureePrecedente($passagePrec->getDureeDate());
                            $passage->setDatePrecedente($passagePrec->getDateDebut());
                        }
                    }
                }
            }
            if($i > 500) {
                $this->dm->flush();
                $i=0;
            }
            $i++;
        }

        $this->dm->flush();
    }
}
