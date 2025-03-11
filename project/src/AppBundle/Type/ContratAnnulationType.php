<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Document\Contrat;
use AppBundle\Document\Compte;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;

class ContratAnnulationType extends AbstractType {

    protected $dm;
    protected $contrat;

    public function __construct(DocumentManager $documentManager, Contrat $c) {
        $this->dm = $documentManager;
        $this->contrat = $c;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('dateResiliation', DateType::class, array(
                    'label' => 'Date résiliation :',
                    "attr" => array(
                        'class' => 'input-inline datepicker',
                        'data-provide' => 'datepicker',
                        'data-date-format' => 'dd/mm/yyyy'
                    ),
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy'))
                 ->add('commentaireResiliation', TextareaType::class, array('label' => 'Commentaire :', "required" => false, 'mapped' => false, "attr" => array("class" => "form-control", "rows" => 12)));


        $builder->add('save', SubmitType::class, array('label' => 'Annuler le contrat', "attr" => array("class" => "btn btn-danger pull-right")));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Contrat',
        ));
    }

    /**
     * @return string
     */
    public function getName() {

        return 'contrat_annulation';
    }

}
