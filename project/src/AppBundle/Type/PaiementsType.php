<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use AppBundle\Type\PaiementType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;

class PaiementsType extends AbstractType {

    protected $container;
    protected $dm;

    public function __construct(ContainerInterface $container, DocumentManager $documentManager) {
        $this->container = $container;
        $this->dm = $documentManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
    //  var_dump($options['data']->getFacture()->getId()); exit;
        $builder->add('dateCreation', DateType::class, array(
            'label' => 'Date création :',
            "attr" => array(
                'class' => 'input-inline datepicker',
                'data-provide' => 'datepicker',
                'data-date-format' => 'dd/mm/yyyy',
                "placeholder" => 'Date des paiements'
            ),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy'))
            ->add('numeroRemise', TextType::class, array('label' => 'Numéro remise de chèque',"required" => false,"attr" => array("placeholder" => 'Numéro remise de chèque')));

        $builder->add('paiement', CollectionType::class, array(
        'entry_type' => new PaiementType($this->container, $this->dm),
        'allow_add' => true,
        'allow_delete' => true,
        'delete_empty' => true,
        'label' => ' ',
        ));
        $builder->add('save', SubmitType::class, array('label' => 'Enregistrer', "attr" => array("class" => "btn btn-success pull-right")));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Paiements',
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'paiements';
    }

}
