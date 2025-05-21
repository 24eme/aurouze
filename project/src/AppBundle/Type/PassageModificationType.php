<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Document\Compte;
use AppBundle\Manager\PassageManager;
use AppBundle\Document\Prestation;
use Symfony\Component\Form\CallbackTransformer;
use AppBundle\Transformer\PrestationTransformer;

class PassageModificationType extends AbstractType
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
            ->add('typePassage', ChoiceType::class, array('label' => 'Type de passage :', 'choices' => array_merge(array("" => ""), PassageManager::$typesPassageLibelles), "attr" => array("class" => "select2 select2-simple", "data-placeholder" => "Sélectionner un type")));

        if(!$builder->getData()->getRendezVous()) {
            $currentTechniciens = $builder->getData()->getTechniciens()->toArray();
            $builder->add('techniciens', DocumentType::class, array(
                    'choices' => $this->getTechniciens($currentTechniciens),
                    'class' => 'AppBundle\Document\Compte',
                    'required' => false,
            		'expanded' => false,
            		'multiple' => true,
            		'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple", "style" => "width:100%;")
            ));
        }

        $builder->add('prestations', ChoiceType::class, array(
            		'choices' => $this->getPrestations(),
	        		'expanded' => false,
	        		'multiple' => true,
        			'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple", "style" => "width:100%;"),
        ));
        if(!$builder->getData()->getRendezVous()) {
            $builder->add('datePrevision', DateType::class, array(
                "attr" => array(
                        'class' => 'input-inline datepicker',
                        'data-provide' => 'datepicker',
                        'data-date-format' => 'dd/mm/yyyy',
                ),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy'
            ), array("label" => "Date de prévision"));
        }

        $builder->add('mouvement_declenchable', null, array('label' => "Ce mouvement déclenche une facture", "required" => false));
        $builder->add('commentaire', null, array('label' => 'Commentaire :', 'required' => false));
        $builder->get('prestations')->addModelTransformer(new PrestationTransformer($this->dm));
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

    public function getTechniciens(array $currentTechniciens) {
        return array_unique(array_merge(
            $currentTechniciens,
            $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursTechnicienActif()->toArray()
        ));
    }

    public function getPrestations()
    {
    	return $this->dm->getRepository('AppBundle:Configuration')->findConfiguration()->getPrestations()->toArray();
    }
}
