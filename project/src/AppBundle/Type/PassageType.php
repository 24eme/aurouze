<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use AppBundle\Transformer\ProduitTransformer;

class PassageType extends AbstractType
{

    protected $dm;

    public function __construct(DocumentManager $documentManager) {
        $this->dm = $documentManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextareaType::class, array('label' => 'Rapport :', 'required' => false, "attr" => array("class" => "form-control", "rows" => 10)))
            ->add('duree', TextType::class, array('label' => 'Durée effective du passage* :', 'attr' => array('class' => 'input-timepicker')))
            ->add('save', SubmitType::class, array('label' => 'Valider', "attr" => array("class" => "btn btn-success")));
        ;

        $builder->add('produits', CollectionType::class, array(
            'entry_type' => new ProduitPassageType($this->dm),
            'allow_add' => true,
            'allow_delete' => true,
            'delete_empty' => true,
            'required' => false,
            'label' => '',
        ));

        $builder->add('nettoyages', ChoiceType::class, array(
        		'label' => 'Nettoyage : ',
        		'choices' => $this->getNettoyages(),
        		'expanded' => false,
        		'multiple' => true,
        		'required' => false,
        		'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple", "data-tags" => "true"),
        ));
        $builder->get('nettoyages')->resetViewTransformers();

        $builder->add('applications', ChoiceType::class, array(
        		'label' => 'Respect des applications : ',
        		'choices' => $this->getApplications(),
        		'expanded' => false,
        		'multiple' => true,
        		'required' => false,
        		'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple"),
        ));
        $builder->get('applications')->resetViewTransformers();

        $builder->add('commentaireInterne', TextareaType::class, array('label' => 'Commentaire interne (non transmis au client) :', 'required' => false, "attr" => array("class" => "form-control", "rows" => 3)));
        $builder->add('emailTransmission', TextType::class, array(
            'label' => 'Email * :',
            'required' => false,
            'attr' => array("placeholder" => 'Email de transmission', 'multiple' => ''),
            'constraints' => [new Assert\Callback([$this, 'validateEmails'])]
        ));
        $builder->add('secondEmailTransmission', EmailType::class, array('label' => 'Second Email :','required' => false, 'attr' => array("placeholder" => 'Second email de transmission')));
        $builder->add('nomTransmission', TextType::class, array('label' => 'Nom :', 'required' => false, 'attr' => array("placeholder" => 'Nom du responsable')));

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Passage'
        ));
    }

    public function validateEmails($values, ExecutionContextInterface $context)
    {
        if (! $values) { return true; }
        $emails = explode(';', $values);
        $emails = array_filter($emails, 'strlen'); // pas d'email vide

        $bad = array_filter($emails, function ($email) {
            return filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false;
        });

        if (count($bad) > 0) {
            $context->buildViolation("Un ou plusieurs emails invalides : {{ emails }}")
                    ->setParameter('{{ emails }}', implode(', ', $bad))
                    ->atPath('emailTransmission')
                    ->addViolation();
        }
    }

    public function onPreSubmit(FormEvent $event)
    {
        $passage = $event->getData();

        if (! $passage) {
            return;
        }

        $emails = explode(';', $passage['emailTransmission']);
        $emails = array_filter($emails, 'strlen'); // pas d'email vide
        $emails = array_map('trim', $emails);

        $passage['emailTransmission'] = implode(';', $emails);
        $event->setData($passage);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'passage';
    }

    public function getTechniciens() {
        return $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursTechnicien();
    }

    public function getNettoyages() {
    	$tags = $this->dm->getRepository('AppBundle:Passage')->findAllNettoyages();
    	$result = array();
    	foreach ($tags as $tag) {
            $result[trim($tag)] = $tag;
    	}
    	return $result;
    }

    public function getApplications() {
    	$tags = $this->dm->getRepository('AppBundle:Passage')->findAllApplications();
    	$result = array();
    	foreach ($tags as $tag) {
            $result[trim($tag)] = $tag;
    	}
    	return $result;
    }
}
