<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Config\FileLocator;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new Knp\Bundle\SnappyBundle\KnpSnappyBundle(),
            new FOS\ElasticaBundle\FOSElasticaBundle(),
            new Knp\Bundle\MarkdownBundle\KnpMarkdownBundle(),
            new Vich\UploaderBundle\VichUploaderBundle(),
            new AppBundle\AppBundle(),

        );

        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }

    protected function initializeContainer()
    {
      parent::initializeContainer();
      putenv("SECRETKEY=".$this->container->getParameter('secret'));
    }

    protected function buildContainer()
    {
        $container = parent::buildContainer();

        if($container->hasParameter('instanceapp') && $container->getParameter('instanceapp')) {
            $loader = new YamlFileLoader($container, new FileLocator($this));
            $loader->load($this->getRootDir().'/config/app_'.$container->getParameter('instanceapp').'.yml');
        }

        $container->setParameter('commitref', $this->getCommitRef());


        return $container;
    }

    public function getCacheDir()
    {

        return $this->rootDir.'/../var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return $this->rootDir.'/../var/logs/'.$this->getEnvironment();
    }

    function getCommitRef() {
        if(!file_exists($this->rootDir.'/../../.git/HEAD')) {

            return null;
        }

        $head = str_replace(["ref: ", "\n"], "", file_get_contents($this->rootDir.'/../../.git/HEAD'));
        $commit = null;

        if(strpos($head, "refs/") !== 0) {
            $commit = $head;
        }

        if(file_exists($this->rootDir.'/../../.git/'.$head)) {
            $commit = str_replace("\n", "", file_get_contents($this->rootDir.'/../../.git/'.$head));
        }

        return substr($commit, 0, 7);
    }

}
