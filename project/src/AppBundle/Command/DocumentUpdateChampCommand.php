<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentUpdateChampCommand extends ContainerAwareCommand {

    protected $dm;

    protected function configure() {
        $this
            ->setName('update:document-update-champ')
            ->setDescription('Document mise à jour d\'un champ')
            ->addArgument('id', InputArgument::REQUIRED, "Id du document")
            ->addArgument('champ', InputArgument::REQUIRED, "Nom du champ à mettre à jour")
            ->addArgument('valeur', InputArgument::REQUIRED, "Nouvelle valeur du champ");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $idDoc = $input->getArgument('id');
        $champName = $input->getArgument('champ');
        $valeur = $input->getArgument('valeur');
        $typeDoc = strtok($idDoc, "-");
        $doc = $this->dm->getRepository("AppBundle:" . ucwords(strtolower($typeDoc)))->find($idDoc);


        $setFonctionName = "set" . ucfirst($champName);

        $doc->$setFonctionName($valeur);
        $output->writeln(sprintf("\n<comment>Mis à jour du document %s : '%s' a été assigné à %s</comment>" ,  $doc->getId() , $valeur ,  $champName));

        $this->dm->flush();
    }

}
