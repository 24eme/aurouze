<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Type\Adresse;
use AppBundle\Type\ContactCoordonneeType;
use AppBundle\Manager\EtablissementManager;

class EtablissementType extends AbstractType {

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
                ->add('simpleNom', TextType::class, array('label' => 'Nom* :'))
                ->add('type', ChoiceType::class, array('label' => 'Type* :', 'choices' => array_merge(array('' => ''), $this->getTypes()), "attr" => array("class" => "select2 select2-simple")))
                ->add('siret', TextType::class, ['label' => 'Siret :', 'required' => false])
                ->add('actif', CheckboxType::class, array('label' => ' ', 'required' => false, "attr" => array("class" => "switcher", "data-size" => "mini")))
                ->add('save', SubmitType::class, array('label' => 'Enregistrer', "attr" => array("class" => "btn btn-success pull-right")))
        		->add('adresse', AdresseType::class, array('data_class' => 'AppBundle\Document\Adresse'))
        		->add('contactCoordonnee', ContactCoordonneeType::class, array('data_class' => 'AppBundle\Document\ContactCoordonnee'))
                ->add('commentaire', TextareaType::class, array('label' => 'Commentaire Permanent Techniciens:',"required" => false,  "attr" => array("class" => "form-control", "rows" => 6), 'required' => false, 'empty_data'  => null))
                ->add('commentairePlanification', TextareaType::class, array('label' => 'Commentaire Interne de Planification:',"required" => false,  "attr" => array("class" => "form-control", "rows" => 6), 'required' => false, 'empty_data'  => null));

                $builder->add('sameContact', CheckboxType::class, array('label' => 'Même contact société', 'required' => false,   'empty_data'  => null, "attr" => array("class" => "collapse-checkbox", "data-target" => "#collapseContact")));
                $builder->add('sameAdresse', CheckboxType::class, array('label' => 'Même adresse société', 'required' => false,   'empty_data'  => null, "attr" => array("class" => "collapse-checkbox", "data-target" => "#collapseAdresse")));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Etablissement',
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'etablissement';
    }

    public function getTypes() {
        return EtablissementManager::$type_libelles;
    }

}
