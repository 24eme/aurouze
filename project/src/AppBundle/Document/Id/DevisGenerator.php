<?php

namespace AppBundle\Document\Id;

use Doctrine\ODM\MongoDB\Id\AbstractIdGenerator;
use Doctrine\ODM\MongoDB\DocumentManager;

class DevisGenerator extends AbstractIdGenerator
{
    public function generate(DocumentManager $dm, $document)
    {
        $className = get_class($document);
        $db = $dm->getDocumentDatabase($className);

        $query = array('_id' => $document->getSociete()->getId());
        $newObj = array('$inc' => array('devisIncrement' => 1));

        $command = array();
        $command['findandmodify'] = "Societe";
        $command['query'] = $query;
        $command['update'] = $newObj;
        $command['upsert'] = true;
        $command['new'] = true;
        $result = $db->command($command);

        $document->setIdentifiant(sprintf("%s-%s-%04d", $document->getSociete()->getIdentifiant(), $document->getDateEmission()->format('Ymd'), $result['value']['devisIncrement']));

        $id = sprintf("%s-%s", "DEVIS", $document->getIdentifiant());

        $this->generateNumeroDevis($dm, $document);

        return $id;
    }

    public function generateNumeroDevis(DocumentManager $dm, $document) {
        if($document->getNumeroDevis()) {
            return;
        }

        $className = get_class($document);
        $db = $dm->getDocumentDatabase($className);
        $annee = $document->getDateEmission()->format('Y');

        $command = array();
        $command['findandmodify'] = 'doctrine_increment_ids';
        $command['query'] = array('_id' => "DevisArchive");
        $command['update'] = array('$inc' => array($annee => 1));
        $command['upsert'] = true;
        $command['new'] = true;
        $result = $db->command($command);
        $document->setNumeroDevis(sprintf("%s%04d", $annee, $result['value'][$annee]));
    }
}
