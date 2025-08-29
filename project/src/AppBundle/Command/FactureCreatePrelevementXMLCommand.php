<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Tool\PrelevementXml;
use AppBundle\Document\Paiements;
use AppBundle\Document\Paiement;
use AppBundle\Manager\PaiementsManager;

class FactureCreatePrelevementXMLCommand extends ContainerAwareCommand
{
    protected $dm;

    protected function configure()
    {
        $this->setName('facture:create-prelevement')
             ->setDescription('Création d\'un fichier XML pour la banque')
             ->addArgument(
                 'factures', InputArgument::IS_ARRAY, "Numéros des factures"
             );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $fm = $this->getContainer()->get('facture.manager');
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $banqueParameters = $this->getContainer()->getParameter('banque');

        $factures = [];

        foreach ($input->getArgument('factures') as $f) {
           $factures[] = $fm->getRepository()->findOneBy(['numeroFacture' => $f]);
        }

        $prelevement = new PrelevementXML($factures, $banqueParameters);
        $file = $prelevement->createPrelevement();

        $paiements = new Paiements($dm);
        $paiements->setDateCreation(new \DateTime('now'));
        $paiements->setPrelevement(true);
        $paiements->setImprime(false);

        $societesInFirstPrev = array();

        foreach ($factures as $facture) {
            $paiement = new Paiement();
            $paiement->setFacture($facture);
            $paiement->setMoyenPaiement(PaiementsManager::MOYEN_PAIEMENT_PRELEVEMENT_BANQUAIRE);
            $paiement->setTypeReglement(PaiementsManager::TYPE_REGLEMENT_FACTURE);
            $paiement->setDatePaiement($facture->getInPrelevement());
            $paiement->setMontant($facture->getMontantTTC());
            $paiement->setLibelle('FACT '.$facture->getNumeroFacture().' du '.$facture->getDateEmission()->format("d m Y").' '. str_ireplace(array(".",","),"EUR",sprintf("%0.2f",$facture->getMontantAPayer())));
            $paiement->setVersementComptable(false);
            $paiements->addPaiement($paiement);

            if($facture->getSociete()->getSepa()->isFirst()){
                $societesInFirstPrev[$facture->getSociete()->getId()] = $facture->getSociete();
            }
        }

        $paiements->setXmlbase64($prelevement->getXml());

        foreach ($societesInFirstPrev as $societe) {
            $societe->getSepa()->setFirst(false);
        }

        $dm->persist($paiements);
        $dm->flush();

        $output->writeln($file);
    }
}
