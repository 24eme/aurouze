<?php 


namespace AppBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

class BaseRepository extends DocumentRepository { 
    public function command(array $data, $options = [], &$hash = null)
    { $db = $this->getDocumentManager()->getDocumentDatabase('AppBundle:Contrat');

        try {
            $cursor = new \MongoCommandCursor($db->getMongoDB()->getConnection(), $db->getName(), $data);
            $cursor->setReadPreference($db->getReadPreference());

            return iterator_to_array($cursor);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            return ExceptionConverter::toResultArray($e);
        }
    }


}

