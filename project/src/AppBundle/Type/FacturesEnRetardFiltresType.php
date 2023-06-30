<?php
namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Manager\FactureManager;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;

class FacturesEnRetardFiltresType extends AbstractType {

    protected $container;
    protected $dm;
    protected $societe;

    public function __construct(ContainerInterface $container, DocumentManager $documentManager,$societe) {
        $this->container = $container;
        $this->dm = $documentManager;
        $this->societe = $societe;
    }

	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$nbRelances = array_merge(array("Toutes les factures"), FactureManager::$types_nb_relance);

        $dateFactureBasse = null;

        if(!$this->societe){
            $date = new \DateTime();
            $interval = new \DateInterval('P2Y');
            $dateFactureBasse = $date->sub($interval); //mettre la date du jour - 2 ans
        }


		$builder->add('nbRelances', ChoiceType::class, array('label' => 'Nombre de relance',
                		'choices' => $nbRelances,
						        "required" => false,
                		"attr" => array("class" => "select2 select2-simple nbRelance")));
		$builder->add('dateFactureBasse', DateType::class, array('required' => false,
                        "label" => "Du",
                		"attr" => array('class' => 'input-inline datepicker dateFactureBasse',
                				'data-provide' => 'datepicker',
                				'data-date-format' => 'dd/mm/yyyy'
                		),
                        'data' => $dateFactureBasse,
                		'widget' => 'single_text',
                		'format' => 'dd/MM/yyyy',
		));
    $builder->add('dateFactureHaute', DateType::class, array('required' => false,
                        "label" => "Jusqu'au",
                		"attr" => array('class' => 'input-inline datepicker dateFactureBasse',
                				'data-provide' => 'datepicker',
                				'data-date-format' => 'dd/mm/yyyy'
                		),
                	//	'data' => $dateFactureBasse,
                		'widget' => 'single_text',
                		'format' => 'dd/MM/yyyy',
		));
    $builder->add('dateMois', DateType::class, array('required' => false,
                "label" => "Facture de",
                "attr" => array('class' => 'input-inline datepickermonthyear','data-provide' => 'datepicker','data-date-format' => 'mm/yyyy'),
                                'widget' => 'single_text',
                                'format' => 'mm/yyyy',
		          ));
    $commerciaux = $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursCommercial();
    $builder->add('commerciaux', DocumentType::class, array(
                'required' => false,
                "choices" => array_merge(array('' => ''), $commerciaux),
                'label' => 'Commerciaux :',
                'class' => 'AppBundle\Document\Compte',
                'expanded' => false,
                'multiple' => true,
                "attr" => array("class" => "select2 select2-simple", "data-placeholder" => "Séléctionner les commerciaux", "style"=> "width:100%;")));

		$builder->add('societe', TextType::class, array("required" => false, "attr" => array("class" => "typeahead form-control", "placeholder" => (isset($options['data']) && isset($options['data']['societe']) && $options['data']['societe']->getIntitule()) ? $options['data']['societe']->getIntitule() : "Rechercher une société")));
		$builder->add('save', SubmitType::class, array('label' => 'Filtrer', "attr" => array("class" => "btn btn-success pull-right")));
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return 'facture_retard_filtres';
	}
}
