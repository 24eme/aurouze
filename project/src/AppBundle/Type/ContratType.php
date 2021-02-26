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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AppBundle\Type\PrestationType;
use AppBundle\Document\Compte;
use AppBundle\Manager\ContratManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Form\CallbackTransformer;
use AppBundle\Transformer\EtablissementsTransformer;
use AppBundle\Transformer\InterlocuteurTransformer;
use AppBundle\Transformer\ProduitTransformer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

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
                ->add('typeContrat', ChoiceType::class, array('label' => 'Type de contrat* :', 'choices' => array_merge(array('' => ''), ContratManager::$types_contrat_libelles), "attr" => array("class" => "select2 select2-simple")))
                ->add('nomenclature', TextareaType::class, array('label' => 'Nomenclature* :', "attr" => array("class" => "form-control", "rows" => 6)))
                ->add('duree', TextType::class, array('label' => 'Durée du contrat* :'))
                ->add('auditPassage', TextType::class, array('label' => 'Audit commercial au passage n° :', 'required' => false))
                ->add('multiTechnicien', TextType::class, array('label' => 'Nombre de techniciens :', 'required' => false))
                ->add('duree_garantie', TextType::class, array('required' => false, 'label' => 'Durée de la garantie :'))
                ->add('nbFactures', TextType::class, array('label' => 'Nombre de factures (associés à un passage)* :'))
                ->add('dureePassage', TextType::class, array('label' => 'Durée estimative d\'un passage* :', 'attr' => array('class' => 'input-timepicker')))
                ->add('prixHt', NumberType::class, array('label' => 'Prix HT* :', 'scale' => 2))
                ->add('tvaReduite', CheckboxType::class, array('label' => 'Tva réduite', 'required' => false, 'label_attr' => array('class' => 'control-label')));

        $builder->add('devisInterlocuteur', ChoiceType::class, array('label' => 'Adresse du devis* : ',
            "choices" => $this->getInterlocuteurs($builder),
            'expanded' => false,
            'multiple' => false,
            "attr" => array("class" => "select2 select2-simple")))
        ;

        $builder->get('devisInterlocuteur')->addModelTransformer(new InterlocuteurTransformer($this->container->get('interlocuteur.manager')));

        $builder->add('etablissements', ChoiceType::class, array('label' => 'Lieux de passage* : ',
            'choices' => $this->getEtablissements($builder),
            'expanded' => false,
            'multiple' => true,
            'disabled' => !$builder->getData()->isModifiable(),
            'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple"),
        ));

        $builder->get('etablissements')->addModelTransformer(new EtablissementsTransformer($this->dm, $builder->getData()->getSociete()));

        $builder->add('prestations', CollectionType::class, array(
            'entry_type' => new PrestationType($this->dm),
            'allow_add' => true,
            'allow_delete' => true,
            'delete_empty' => true,
            'label' => '',
        ));


        $builder->add('produits', CollectionType::class, array(
            'entry_type' => new ProduitType($this->dm),
            'allow_add' => true,
            'allow_delete' => true,
            'delete_empty' => true,
            'required' => false,
            'label' => '',
        ));

        $builder->add('commercial', DocumentType::class, array(
            "choices" => array_merge(array('' => ''), $this->getCommerciaux()),
            'label' => 'Commercial* :',
            'class' => 'AppBundle\Document\Compte',
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

                $builder->add('frequencePaiement', ChoiceType::class, array(
                		'label' => 'Fréquence de paiement* : ',
                		'choices' => $this->getFrequences(),
                		'expanded' => false,
                		'multiple' => false,
                		'required' => true,
                		'attr' => array("class" => "select2 select2-simple"),
                ));

                $builder->add('dateCreation', DateType::class, array(
            	"attr" => array('data-date-format' => 'dd/mm/yyyy', 'class' => 'input-inline datepicker', 'data-provide' => 'datepicker'),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'label' => 'Date de création* :',
        ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    protected function addElement(FormInterface $form, $societe = null)
    {
        $form->add('commanditaire', DocumentType::class, array('label' => 'Commanditaire : ',
            'choices' => ($societe) ? array($societe) : array(),
            'required' => false,
            'class' => 'AppBundle\Document\Societe',
            'attr' => array("class" => "select2 select2-ajax", "data-placeholder" => "Rechercher une société"),
        ));

    }

    function onPreSubmit(FormEvent $event) {
        $form = $event->getForm();
        $values = $event->getData();
        $societe = (isset($values['commanditaire']) && $values['commanditaire']) ? $this->dm->getRepository('AppBundle:Societe')->find($values['commanditaire']) : null;
        $this->addElement($form, $societe);
    }

    function onPreSetData(FormEvent $event) {
        $form = $event->getForm();
        $document = $event->getData();
        $societe = ($document && $document->getCommanditaire())? $document->getCommanditaire() : null;
        $this->addElement($form, $societe);
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

    public function getInterlocuteurs($builder) {
        $interlocuteurs = $this->container->get('interlocuteur.manager')->findAll($builder->getData()->getSociete());
        $interlocuteursArray = array();

        foreach ($interlocuteurs as $interlocuteur) {
            $interlocuteursArray[$interlocuteur->getId()] = $interlocuteur->getLibelleComplet();
        }
        return $interlocuteursArray;
    }

    /**
     * @return string
     */
    public function getName() {
        return 'contrat';
    }

    public function getCommerciaux() {
        return $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursCommercial();
    }

    public function getProduits() {
        return $this->dm->getRepository('AppBundle:Configuration')->findConfiguration()->getProduits()->toArray();
    }

    public function getFrequences() {
    	$tags = $this->dm->getRepository('AppBundle:Contrat')->findAllFrequences();
    	return array_merge(array(null => null), $tags);
    }

}
