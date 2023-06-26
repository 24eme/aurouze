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

class FacturesUpdateAvoirCommand extends ContainerAwareCommand {

    protected $dm;

    protected function configure() {
        $this
                ->setName('facture:update-avoir')
                ->setDescription('Facture mise à jour des avoirs')
                ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $fm = $this->getContainer()->get('facture.manager');
        $dm = $this->getContainer()->get('doctrine_mongodb.odm.default_document_manager');

        $qb = $fm->getRepository()->createQueryBuilder();
        $qb->field('avoir')->notEqual(null);
        $factures = $qb->getQuery()->execute();
        foreach ($factures as $facture) {
            $avoir = $fm->getRepository()->findOneBy(array('numeroFacture' => $facture->getAvoir()));
            if (!$avoir||!$avoir->isAvoir()) {
                $output->writeln("<info>Problème avoir : ".$facture->getId()." : ".$facture->getAvoir()."</info>");
                continue;
            }
            $facture->setAvoirObject($avoir);
            $facture->updateMontantPaye();
            //$output->writeln("<info>".$avoir->getId()."</info>");
        }
        $dm->flush();
    }

}
