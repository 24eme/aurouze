<?php
namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class FactureLigneType extends AbstractType {

	protected $cm = null;

    public function __construct($cm) {
        $this->cm = $cm;
    }

	/** @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('libelle', TextType::class, array("attr" => array("class" => "typeahead form-control", "placeholder" => "Libellé (Produit ou autre)")))
		    ->add('quantite', TextType::class, array('attr' => array('placeholder' => "Quantité")))
		    ->add('prixUnitaire', TextType::class, array('attr' => array('placeholder' => "Prix unitaire", "class" => "form-control prix-unitaire")))
			->add('tauxTaxe', TextType::class, array('attr' => array('placeholder' => "Taux de TVA")));
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'AppBundle\Document\LigneFacturable',
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'facture_ligne';
	}

	public function getProduits() {

		return $this->cm->getConfiguration()->getProduitsArrayOrdered();
	}
}
