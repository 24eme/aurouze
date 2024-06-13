<?php

namespace AppBundle\Config;

class Config {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function get($name) {

        return $this->container->getParameter($name);
    }
}