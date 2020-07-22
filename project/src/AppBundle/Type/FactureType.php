<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FactureType extends AbstractType
{

    protected $dm = null;
    protected $cm = null;
    protected $com = null;
    protected $contrat = null;

    public function __construct($dm, $cm, $commercial, $contrat = null) {
        $this->dm = $dm;
        $this->cm = $cm;
        $this->com = $commercial;
        if($contrat){
          $this->contrat = $contrat;
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lignes', CollectionType::class, array(
                'entry_type' => new FactureLigneType($this->cm),
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'label' => '',
            ))
            ->add('frequencePaiement', ChoiceType::class, array(
                    'label' => 'Fréquence de paiement',
                    'choices' => $this->getFrequences(),
                    'expanded' => false,
                    'multiple' => false,
                    'required' => false,
                    'attr' => array("class" => "select2 select2-simple", "data-placeholder" => "Séléctionner une fréquence de paiement"),
            ))
            ->add('description', TextareaType::class, array('label' => 'Informations complémentaires :', 'required' => false, "attr" => array("class" => "form-control", "rows" => 3)))
            ->add('commercial', DocumentType::class, array_merge(array('required' => false), array(
                "choices" => $this->getCommerciaux(),
                'label' => 'Commercial* :',
                'class' => 'AppBundle\Document\Compte',
                'expanded' => false,
                'multiple' => false,
                "attr" => array("class" => "select2 select2-simple"))))
        ;

        if($this->contrat){
          $builder->add('contrat', TextType::class, array('disabled' => true, "mapped" => false, "attr" => array('placeholder' => $this->contrat->getIntitule())));
        }

        $builder->add('dateFacturation', DateType::class, array(
            'label' => 'Date de facturation',
            "attr" => array(
                'class' => 'input-inline datepicker',
                'data-provide' => 'datepicker',
                'data-date-format' => 'dd/mm/yyyy'
            ),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy'
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Facture'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'facture';
    }

    public function getFrequences() {
        $tags = $this->dm->getRepository('AppBundle:Contrat')->findAllFrequences();
        return array_merge(array(null => null), $tags);
    }

    public function getCommerciaux() {
        return $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursCommercial();
    }

    public function getDefaultCommercial() {
        return $this->dm->getRepository('AppBundle:Compte')->findOneByIdentifiant($this->com);
    }
}
