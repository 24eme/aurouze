<?php

namespace AppBundle\Type;

use AppBundle\Document\Attachement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ODM\MongoDB\DocumentManager;




class AttachementNameType extends AbstractType {

    protected $dm;

    public function __construct(DocumentManager $documentManager) {
        $this->dm = $documentManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */


    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => Attachement::class,
            'allow_extra_fields' => true,
        ));
    }


    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('titre', TextType::class, array(
                    'label' => 'Modifier le titre',
                ))
            ->add('updatedAt', DateType::class, array(
                'required' => false,
                'attr' => array(
                    'class' => 'input-inline datepicker',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd/mm/yyyy'),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'label' => 'Modifier la date d\'ajout',
                ))
            ->add('save', SubmitType::class, array(
            'label' => 'Sauvegarder'
            ))
            ->getForm();

        }
}

