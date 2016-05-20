<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Doctrine\ODM\MongoDB\DocumentManager;

class PassageType extends AbstractType
{

    protected $dm;

    public function __construct(DocumentManager $documentManager) {
        $this->dm = $documentManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextareaType::class, array('label' => 'Constat :', 'required' => false, "attr" => array("class" => "form-control", "rows" => 10)))
            ->add('duree', TextType::class, array('label' => 'Durée effective du passage* :', 'attr' => array('class' => 'input-timepicker')))
            ->add('save', SubmitType::class, array('label' => 'Valider', "attr" => array("class" => "btn btn-success")));
        ;

        $builder->add('produits', CollectionType::class, array(
            'entry_type' => new ProduitPassageType($this->dm),
            'allow_add' => true,
            'allow_delete' => true,
            'delete_empty' => true,
            'label' => '',
        ));

        $builder->add('nettoyages', ChoiceType::class, array(
        		'label' => 'Nettoyage : ',
        		'choices' => $this->getNettoyages(),
        		'expanded' => false,
        		'multiple' => true,
        		'required' => false,
        		'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple", "data-tags" => "true"),
        ));
        $builder->get('nettoyages')->resetViewTransformers();

        $builder->add('applications', ChoiceType::class, array(
        		'label' => 'Respect des applications : ',
        		'choices' => $this->getApplications(),
        		'expanded' => false,
        		'multiple' => true,
        		'required' => false,
        		'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple"),
        ));
        $builder->get('applications')->resetViewTransformers();
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Passage'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'passage';
    }

    public function getTechniciens() {
        return $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursTechnicien();
    }

    public function getNettoyages() {
    	$tags = $this->dm->getRepository('AppBundle:Passage')->findAllNettoyages();
    	$result = array();
    	foreach ($tags as $tag) {
    		$result[$tag] = $tag;
    	}
    	return $result;
    }

    public function getApplications() {
    	$tags = $this->dm->getRepository('AppBundle:Passage')->findAllApplications();
    	$result = array();
    	foreach ($tags as $tag) {
    		$result[$tag] = $tag;
    	}
    	return $result;
    }
}
