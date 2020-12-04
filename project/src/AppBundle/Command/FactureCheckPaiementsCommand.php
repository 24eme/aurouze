<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of
 *
 * @author jb
 */

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Manager\PassageManager;
use Symfony\Component\Console\Helper\ProgressBar;
use AppBundle\Manager\ContratManager;
use Symfony\Component\Console\Input\InputOption;

class FactureCheckPaiementsCommand extends ContainerAwareCommand {

    protected $dm;

    protected function configure() {
        $this
                ->setName('update:facture-check-paiements')
                ->setDescription('Facture check paiements')
                ->addOption(
                    'save',
                    false,
                    InputOption::VALUE_OPTIONAL,
                    'enregistre les modifications en base'
                );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.default_document_manager');

        echo "\nControle des factures avec leurs paiements...\n";
        $this->updateFacturesMontantFacture($output, $input->getOption('save'));
    }

    public function updateFacturesMontantFacture($output, $save = false) {

        $allFactures = $this->dm->getRepository('AppBundle:Facture')->retrieveAll();

        $cptTotal = 0;
        $progress = new ProgressBar($output, 100);
        $progress->start();

        foreach ($allFactures as $facture) {
            $montant = (isset($facture['montantPaye']))? $facture['montantPaye'] : 0;
            $cloture = (isset($facture['cloture']))? $facture['cloture'] : 0;
            $cptTotal++;
            if ($cptTotal % (count($allFactures) / 100) == 0) {
                $progress->advance();
            }
            if ($cloture) {
              continue;
            }
            try {
              $f = $this->dm->getRepository('AppBundle:Facture')->findOneBy(["_id" => $facture['_id']]);
            } catch(\Exception $e) {
              echo "\n Erreur de mapping de type : ".$facture['_id']."\n";
              conitnue;
            }
            $f->updateMontantPaye();
            if (ceil($montant) != ceil($f->getMontantPaye())) {
              echo "\n".$f->getId().";$montant;".$f->getMontantPaye()."\n";
            }
        }
        if ($save) {
          echo "\n enregistrement en base... \n";
          $this->dm->flush();
        }
        $progress->finish();

    }

}
