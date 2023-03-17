<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Document\Contrat;
use AppBundle\Document\Compte;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\CallbackTransformer;

class ContratAcceptationType extends AbstractType {

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
        $readonly = array();
        $datePicker = array();
        $required = array();

        if ($this->contrat->isEnAttenteAcceptation()) {
            $required = array('required' => false);
        }

        if (!$this->contrat->isModifiable()) {
            $readonly = array('readonly' => 'readonly');
        } else {
            $datePicker = array('class' => 'input-inline datepicker',  'data-provide' => 'datepicker');
        }


        $builder->add('dateCreation', DateType::class, array_merge($required, array(
            "attr" => array_merge($datePicker, array(
                'data-date-format' => 'dd/mm/yyyy'
                    ), $readonly),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'label' => "Date d'édition :",
        )))->add('dateDebut', DateType::class, array_merge($required, array(
            "attr" => array_merge($datePicker, array(
                'data-date-format' => 'dd/mm/yyyy'
                    ), $readonly),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'label' => 'Date de début du contrat:',
        )))->add('dateAcceptation', DateType::class, array_merge($required, array(
            "attr" => array_merge($datePicker, array(
                'data-date-format' => 'dd/mm/yyyy'
                    ), $readonly),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'label' => 'Date d\'acceptation :',
        )));

        $builder->add('technicien', DocumentType::class, array_merge($required, array(
            "choices" => array_merge(array(null => null), $this->getTechniciens()),
            'label' => 'Technicien :',
            'class' => 'AppBundle\Document\Compte',
            'expanded' => false,
            'multiple' => false,
            'required' => false,
            "attr" => array("class" => "select2 select2-simple"))));

        $builder->add('commercial', DocumentType::class, array_merge($required, array(
            "choices" => array_merge(array('' => ''), $this->getCommerciaux()),
            'label' => 'Commercial :',
            'class' => 'AppBundle\Document\Compte',
            'expanded' => false,
            'multiple' => false,
            "attr" => array("class" => "select2 select2-simple"))));

        $builder->add('commentaire', TextareaType::class, array('label' => 'Commentaire contrat :', "required" => false, "attr" => array("class" => "form-control", "rows" => 8)));
        $builder->add('referenceClient', TextType::class, array('label' => 'Numéro de commande :', 'required' => false));
        $builder->add('factureDestinataire', TextType::class, array('label' => 'Destinataire de la facture (si différent de celui de la société) :', 'required' => false));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Contrat',
        ));
    }

    public function getTechniciens() {
        return $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursTechnicien();
    }

    public function getCommerciaux() {
        return $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursCommercial();
    }

    /**
     * @return string
     */
    public function getName() {
        return 'contrat_acceptation';
    }

}
