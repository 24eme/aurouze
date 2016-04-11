<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AppBundle\Type\PrestationType;
use AppBundle\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Form\CallbackTransformer;
use AppBundle\Transformer\EtablissementsTransformer;

class ContratType extends AbstractType {

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
        $builder
                ->add('typeContrat', ChoiceType::class, array('label' => 'Type de contrat :', 'choices' => array_merge(array('' => ''), $this->container->getParameter('contrat_type')), "attr" => array("class" => "select2 select2-simple")))
                ->add('nomenclature', TextareaType::class, array('label' => 'Nomenclature :', "attr" => array("class" => "form-control", "rows" => 6)))
                ->add('duree', TextType::class, array('label' => 'Durée du contrat :'))
                ->add('duree_garantie', TextType::class, array('required' => false, 'label' => 'Durée de la garantie :'))
                ->add('nbFactures', TextType::class, array('label' => 'Nombre de factures :'))
                ->add('dureePassage', TextType::class, array('label' => 'Durée estimative d\'un passage :', 'attr' => array('class' => 'input-timepicker')))
                ->add('prixHt', NumberType::class, array('label' => 'Prix HT :', 'scale' => 2))
                ->add('save', SubmitType::class, array('label' => 'Suivant', "attr" => array("class" => "btn btn-success pull-right")));

       

        $builder->add('etablissements', ChoiceType::class, array('label' => 'Lieux de passage : ',
            		'choices' => $this->getEtablissements($builder),
	        		'expanded' => false, 
	        		'multiple' => true,
        			'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple"),
        ));

         $builder->get('etablissements')->addModelTransformer(new EtablissementsTransformer($this->dm,$builder->getData()->getSociete()));
        
        $builder->add('prestations', CollectionType::class, array(
            'entry_type' => new PrestationType($this->dm),
            'allow_add' => true,
            'allow_delete' => true,
            'delete_empty' => true,
            'label' => '',
        ));

        $builder->add('commercial', DocumentType::class, array(
            "choices" => array_merge(array('' => ''), $this->getUsers(User::USER_TYPE_COMMERCIAL)),
            'label' => 'Commercial :',
            'class' => 'AppBundle\Document\User',
            'expanded' => false,
            'multiple' => false,
            "attr" => array("class" => "select2 select2-simple")));

        $builder->add('technicien', DocumentType::class, array(
            "choices" => array_merge(array('' => ''), $this->getUsers(User::USER_TYPE_TECHNICIEN)),
            'label' => 'Technicien :',
            'class' => 'AppBundle\Document\User',
            'expanded' => false,
            'multiple' => false,
            "attr" => array("class" => "select2 select2-simple")));

        $builder->get('dureePassage')
                ->addModelTransformer(new CallbackTransformer(
                        function ($originalDescription) {
                    $heure = floor($originalDescription / 60);
                    return $heure . ':' . ((($originalDescription / 60) - $heure) * 60);
                }, function ($submittedDescription) {
                    $duration = explode(':', $submittedDescription);
                    return $duration[0] * 60 + $duration[1];
                }));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Contrat',
        ));
    }

    public function getEtablissements($builder) {
        $etablissements = $this->dm->getRepository('AppBundle:Etablissement')->findAllOrderedByIdentifiantSociete($builder->getData()->getSociete());
        $etablissementsArray = array();
        foreach ($etablissements as $etablissement) {
            $etablissementsArray[$etablissement->getId()] = $etablissement->getIntitule();
        }
        return $etablissementsArray;
    }

    /**
     * @return string
     */
    public function getName() {
        return 'contrat';
    }

    public function getUsers($type) {
        return $this->dm->getRepository('AppBundle:User')->findAllByType($type);
    }

}
