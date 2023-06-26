<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;

class RendezVousType extends AbstractType {

    protected $dm;

    public function __construct(DocumentManager $documentManager) {
        $this->dm = $documentManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        if($options['rdv_libre']) {
            $builder->add('titre', TextType::class, array('attr' => array('placeholder' => "Titre de l'évenement")));
        }

        $builder->add('description', TextareaType::class, array('required' => false, 'attr' => array('rows' => 3  )));
        $builder->add('dateDebut', DateType::class, array(
            "attr" => array(
                    'class' => 'input-inline datepicker',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd/mm/yyyy'
            ),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy'
        ))
        ->add('dateFin', DateType::class, array(
            "attr" => array(
                    'class' => 'input-inline datepicker',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd/mm/yyyy'
            ),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy'
        ))
        ->add('timeDebut', TextType::class, array('label' => 'Heure debut', 'attr' => array('class' => 'input-timepicker', 'data-default' => '12:00')))
        ->add('timeFin', TextType::class, array('label' => 'Heure fin', 'attr' => array('class' => 'input-timepicker', 'data-default' => '12:00')));

        $builder->add('participants', DocumentType::class, array(
            	'choices' => $this->getParticipants(),
                'class' => 'AppBundle\Document\Compte',
        		'expanded' => false,
        		'multiple' => true,
        		'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple", "style" => "width:100%;")
        ));
        $builder->add('rendezVousConfirme', CheckboxType::class, array('label' => 'Rendez-vous confirmé ?', 'required' => false, 'label_attr' => array('class' => 'control-label')));
        $builder->add('all', CheckboxType::class, array('label' => 'Appliquer à tous les calendriers', 'required' => false, 'label_attr' => array('class' => 'control-label'), 'empty_data' => null, 'mapped' => false, 'data' => false));

        if( !$options['is_devis']) {
            $builder->add('prerequis', TextType::class, ['label' => 'Prérequis', 'required' => false, 'label_attr' => ['class' => 'control-label'], 'attr' => ['placeholder' => 'Récupérer les clés au bureau, appeler le client avant, etc ...']]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\RendezVous',
            'rdv_libre' => false,
            'is_devis' =>false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'rendezvous';
    }

    public function getParticipants() {
        return $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursTechnicien();
    }

}
