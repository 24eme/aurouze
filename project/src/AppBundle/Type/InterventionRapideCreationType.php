<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use AppBundle\Type\PrestationType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Doctrine\ODM\MongoDB\DocumentManager;

class InterventionRapideCreationType extends AbstractType {

    protected $dm;

    public function __construct(DocumentManager $documentManager) {
        $this->dm = $documentManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('commercial', DocumentType::class, array(
                    "choices" => array_merge(array('' => ''), $this->getCommerciaux()),
                    'label' => 'Commercial :',
                    'class' => 'AppBundle\Document\Compte',
                    'expanded' => false,
                    'multiple' => false,
                    "attr" => array("class" => "select2 select2-simple", "data-placeholder" => "Sélectionner un commercial")))
                ->add('technicien', DocumentType::class, array(
                    "choices" => array_merge(array('' => ''), $this->getTechniciens()),
                    'label' => 'Technicien :',
                    'class' => 'AppBundle\Document\Compte',
                    'expanded' => false,
                    'multiple' => false,
                    "attr" => array("class" => "select2 select2-simple", "data-placeholder" => "Sélectionner un technicien")))
                ->add('dateDebut', DateType::class, array(
                    'label' => 'Date début :',
                    "attr" => array(
                        'class' => 'input-inline datepicker',
                        'data-provide' => 'datepicker',
                        'data-date-format' => 'dd/mm/yyyy'
                    ),
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy'))
                ->add('prixHt', NumberType::class, array('label' => 'Prix HT', 'scale' => 2, 'required' => false))
                ->add('tvaReduite', CheckboxType::class, array('label' => 'Tva réduite', 'required' => false))
                ->add('uniquePrestations', ChoiceType::class, array(
                    		'choices' => $this->getPrestations(),
        	        		'expanded' => false,
        	        		'multiple' => true,
                			'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple", "style" => "width:100%;")))
                ->add('nomenclature', TextareaType::class, array('label' => 'Nomenclature', 'required' => false, "attr" => array("class" => "form-control", "rows" => 2)))
                ->add('description', TextareaType::class, array('label' => 'Description facture', 'required' => false, "attr" => array("class" => "form-control", "rows" => 1)))
                ->add('referenceClient', TextType::class, array('label' => 'Numéro de commande :', 'required' => false, 'attr' => array('placeholder' => 'Référence commande du client')));

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Contrat'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'interventionRapide';
    }

    public function getCommerciaux() {
        return $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursCommercial();
    }

    public function getTechniciens() {
        return $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursTechnicien();
    }

    public function getPrestations()
    {
    	return $this->dm->getRepository('AppBundle:Configuration')->findConfiguration()->getPrestationsArray();
    }

}
