<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class FactureType extends AbstractType
{

    protected $cm = null;

    public function __construct($cm) {
        $this->cm = $cm;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lignes', CollectionType::class, array(
                'entry_type' => new FactureLigneType($this->cm),
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'label' => '',
            ))
            ->add('save', SubmitType::class, array('label' => 'Valider'));
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Facture'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'facture';
    }
}
