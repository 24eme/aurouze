<?php
namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Manager\PaiementsManager;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class RelanceType extends AbstractType {

	protected $factures;

	public function __construct($factures = array())
	{
		$this->factures = $factures;
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		foreach ($this->factures as $facture) {
        		$builder->add($facture->getId(), CheckboxType::class, array('label' => ' ', 'required' => false, 'label_attr' => array('class' => 'small')));
    }
    $builder->add('relancer', 'submit', array('label' => "Générer la relance suivante (sans envoi de mail)", "attr" => array("class" => "btn btn-primary pull-right")));
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return 'relance';
	}
}
