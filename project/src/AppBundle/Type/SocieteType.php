<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Type\Adresse;
use AppBundle\Type\ContactCoordonnee;
use AppBundle\Manager\EtablissementManager;
use AppBundle\Transformer\ProvenanceTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Iban;
use MakinaCorpus\IbanBundle\Form\Type\IbanType;

class SocieteType extends AbstractType {

    protected $container;
    protected $dm;
    protected $isNew;

    public function __construct(ContainerInterface $container, DocumentManager $documentManager, $isNew = false) {
        $this->container = $container;
        $this->dm = $documentManager;
        $this->isNew = $isNew;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('raisonSociale', TextType::class, array('label' => 'Raison sociale* :'))
                ->add('codeComptable', TextType::class, array('label' => 'Code comptable* :'))
                ->add('commentaire', TextareaType::class, array('label' => 'Commentaires :', "attr" => array("class" => "form-control", "rows" => 6), 'required' => false, 'empty_data' => null))
                ->add('type', ChoiceType::class, array('label' => 'Type* :', 'choices' => array_merge(array('' => ''), $this->getTypes()), "attr" => array("class" => "select2 select2-simple")))
                ->add('actif', CheckboxType::class, array('label' => ' ', 'required' => false, "attr" => array("class" => "switcher", "data-size" => "mini")))
                ->add('save', SubmitType::class, array('label' => 'Enregistrer', "attr" => array("class" => "btn btn-success pull-right")))
                ->add('adresse', AdresseType::class, array('data_class' => 'AppBundle\Document\Adresse'))
                ->add('contactCoordonnee', ContactCoordonneeType::class, array('data_class' => 'AppBundle\Document\ContactCoordonnee'))
                ->add('sepa', SepaType::class, array('data_class' => 'AppBundle\Document\Sepa'))
                ->add('siret', TextType::class, ['label' => 'Siret :', 'required' => false]);
        if ($this->isNew) {
            $builder->add('generer', CheckboxType::class, array('label' => 'Générer l\'établissement lié, à partir des données de la société', 'required' => false, 'empty_data' => null, 'mapped' => false, 'data' => false));
        }



        $builder->add('provenance', ChoiceType::class, array(
            'choices' => array_merge(array('' => ''), $this->getProvenances()),
            'expanded' => false,
            'multiple' => false,
            'required' => false,
            'empty_data' => null,
            'attr' => array("class" => "select2 select2-simple"),
        ));
        $builder->get('provenance')->addModelTransformer(new ProvenanceTransformer($this->dm));

        $builder->add('tags', ChoiceType::class, array(
            'choices' => $this->getTags(),
            'expanded' => false,
            'multiple' => true,
            'required' => false,
            'empty_data' => null,
            'attr' => array("class" => "select2 select2-simple", "data-tags" => "true"),
        ));
        $builder->get('tags')->resetViewTransformers();



                $builder->add('frequencePaiement', ChoiceType::class, array(
                		'label' => 'Fréquence de paiement* : ',
                		'choices' => $this->getFrequences(),
                		'expanded' => false,
                		'multiple' => false,
                		'required' => true,
                		'attr' => array("class" => "select2 select2-simple"),
                ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Societe',
        ));
    }

    public function getProvenances() {
        $config = $this->dm->getRepository('AppBundle:Configuration')->findConfiguration();
        if(!$config) {

            return array();
        }

        return $config->getProvenances()->toArray();
    }

    public function getTags() {
        $tags = $this->dm->getRepository('AppBundle:Societe')->findAllTags();
        $result = array();
        foreach ($tags as $tag) {
            $result[$tag] = $tag;
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getName() {
        return 'societe_edition';
    }

    public function getTypes() {
        return EtablissementManager::$type_libelles;
    }

    public function getFrequences() {
    	$tags = $this->dm->getRepository('AppBundle:Contrat')->findAllFrequences();
    	return array_merge(array(null => null), $tags);
    }


}
