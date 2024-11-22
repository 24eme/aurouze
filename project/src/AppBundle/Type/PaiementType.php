<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Manager\PaiementsManager;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use AppBundle\Document\Facture;
use Symfony\Component\Form\FormInterface;

class PaiementType extends AbstractType {

    protected $container;
    protected $dm;

    public function __construct(ContainerInterface $container, DocumentManager $documentManager) {
        $this->container = $container;
        $this->dm = $documentManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('moyenPaiement', ChoiceType::class, array('label' => 'Moyen de paiement', 'choices' => array_merge(array(null => null), PaiementsManager::$moyens_paiement_libelles), "attr" => array("class" => "")));
        $builder->add('typeReglement', ChoiceType::class, array('label' => 'Type de paiement', 'choices' => PaiementsManager::$nouveau_types_reglements_libelles, "attr" => array("class" => "")));
    	$builder->add('libelle', TextType::class, array('label' => 'Libellé',"required" => false, "attr" => array("placeholder" => 'Libellé')));
    	$builder->add('datePaiement', DateType::class,
    		array(
    			'label' => 'Date de paiement',
    			'attr' => array('class' => 'input-inline datepicker text-center', 'data-provide' => 'datepicker', 'data-date-format' => 'dd/mm/yyyy', 'placeholder' => 'Date de paiement'),
    			'widget' => 'single_text',
    			'format' => 'dd/MM/yyyy'
    		)
    	);
    	$builder->add('montant', NumberType::class, array('label' => 'Montant', 'scale' => 2,"attr" => array("placeholder" => 'Montant', 'class' => 'nombreSomme text-right')));
    	$builder->add('factureMontantTTC', HiddenType::class, array('label' => 'Montant',  'mapped' => true,  "attr" => array('class' => 'factureMontantTTC')));
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
    	$builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    protected function addElement(FormInterface $form, Facture $facture = null)
    {
    	$form->add('facture', DocumentType::class, array(
    			'choices' => ($facture)? array($facture) : array(),
	        	'expanded' => false,
	        	'multiple' => false,
            	'class' => 'AppBundle\Document\Facture',
	        	'attr' => array("class" => "form-control select2")
    	));
    }

    function onPreSubmit(FormEvent $event) {
    	$form = $event->getForm();
    	$values = $event->getData();
    	$facture = (isset($values['facture']) && $values['facture'])? $this->dm->getRepository('AppBundle:Facture')->find($values['facture']) : null;
    	$this->addElement($form, $facture);
    }

    function onPreSetData(FormEvent $event) {
    	$form = $event->getForm();
    	$document = $event->getData();
    	$facture = ($document && $document->getFacture())? $document->getFacture() : null;
    	$this->addElement($form, $facture);
		if($facture){
			$this->factureMontantTTC = $facture->getMontantTTC();
			$document->setFactureMontantTTC($this->factureMontantTTC);
		}
    }

    public function getDocument(){
      return $this->getData();
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array('data_class' => 'AppBundle\Document\Paiement'));
    }

    public function getName() {
        return 'paiement_modification';
    }
}
