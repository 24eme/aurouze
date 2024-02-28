<?php

namespace AppBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

class ConfigurationRepository extends BaseRepository {
    public $configuration = null;


    public function findConfiguration() {
        if(!$this->configuration) {
            $this->configuration = $this->findOneById("CONFIGURATION");
        }

        return $this->configuration;
    }


}
