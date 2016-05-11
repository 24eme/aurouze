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
use Doctrine\Common\Collections\ArrayCollection;

class ContratUpdateMvtsCommand extends ContainerAwareCommand {

    protected $dm;

    protected function configure() {
        $this
                ->setName('update:contrat-update-mvts')
                ->setDescription('Contrat update mvts');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        
        $allContrats = $this->dm->getRepository('AppBundle:Contrat')->findAll();
        
        $cptTotal = 0;
        $i = 0;
        $progress = new ProgressBar($output, 100);
        $progress->start();
        $nb = count($allContrats);
        foreach ($allContrats as $contrat) {
        	if (count($contrat->getMouvements()) > 0) {
	        	$mvts = array();
	        	foreach ($contrat->getMouvements() as $contratMvt) {
	        		$mvts[] = $contratMvt;
	        	}
	        	$contrat->cleanMouvements();
	        	foreach ($mvts as $mvt) {
	        		$contrat->addMouvement($mvt);
	        	}
	        	$cptTotal++;
	        	if ($cptTotal % ($nb / 100) == 0) {
	        		$progress->advance();
	        	}
	        	if ($i >= 1000) {
	        		$this->dm->flush();
	        		$i = 0;
	        	}
	        	$i++;
        	}
        }
        $this->dm->flush();
        $progress->finish();
    }

}
