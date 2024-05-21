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

class ContratSuppressionCommand extends ContainerAwareCommand {

    protected $dm;

    protected function configure() {
        $this
                ->setName('contrat:suppression')
                ->setDescription("Suppression d'un contrat et ses passages")
                ->addArgument('id', InputArgument::REQUIRED, "Identifiant du document");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.default_document_manager');

        $contrat = $this->dm->getRepository("AppBundle:Contrat")->find($input->getArgument('id'));

        if(!$contrat) {

            echo "ERROR;Le contrat ".$input->getArgument('id')." n'existe pas\n";
            return;
        }

        foreach($contrat->getContratPassages() as $passages) {
            foreach($passages->getPassages() as $passage) {
              if($passage->isPlanifie() || $passage->isRealise()){
                echo $passage->getId()." est PLANIFIE ou REALISE\n";
                return;
              }
              echo $passage->getId()."\n";
              $this->dm->remove($passage);
            }
        }


        $this->dm->remove($contrat);

        echo $contrat->getId()."\n";
        echo "NUMERO_ARCHIVE:".$contrat->getNumeroArchive()."\n";

        $this->dm->flush();

        $ancienContrat = $this->dm->getRepository("AppBundle:Contrat")->findLastContratByNumero($contrat->getNumeroArchive());

        if(!$ancienContrat) {
            echo "ERROR;Pas d'ancien contrat ".$contrat->getNumeroArchive()."\n";
            return;
        }
        echo "ANCIEN_CONTRAT:".$ancienContrat->getId()."\n";
        $ancienContrat->setReconduit(false);

        $this->dm->flush();
    }


}
