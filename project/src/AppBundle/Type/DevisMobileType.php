<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\CallbackTransformer;
use Doctrine\ODM\MongoDB\DocumentManager;

class DevisMobileType extends AbstractType
{

    protected $dm;
    protected $devisId;

    public function __construct(DocumentManager $documentManager, $devisId, $previousDevis = null) {
        $this->dm = $documentManager;
        $this->devisId = $devisId;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $devisId = $builder->getData()->getId();
        $builder->add('commentaireTechnicien', TextareaType::class, [
                    'label' => 'Commentaire technicien:',
                    'required' => false,
                    "attr" => array("class" => " phoenix", "rows" => 5)
                ]);

         $defaultEmail = $builder->getData()->getEmailTransmission();
         $defaultSecondEmail = $builder->getData()->getSecondEmailTransmission();
         $defaultNomResp = $builder->getData()->getNomTransmission();

        $builder->add('emailTransmission', EmailType::class, array(
          'label' => 'Email :',
          'required' => false,
          'data' => $defaultEmail,
          'attr' => array('class' => " phoenix","placeholder" => 'Email de transmission')));

        $builder->add('secondEmailTransmission', EmailType::class, array(
          'label' => 'Second email :',
          'required' => false,
          'data' => $defaultSecondEmail,
          'attr' => array('class' => " phoenix","placeholder" => 'Email supplémentaire de transmission')));

        $builder->add('nomTransmission', TextType::class, array(
          'label' => 'Nom :',
           'required' => false,
           'data' => $defaultNomResp,
           'attr' => array('class' => " phoenix","placeholder" => 'Nom du signataire')));

        $builder->add('signatureBase64', HiddenType::class, array('required' => false, 'attr' => array('class' => "phoenix", "data-cible" => "mobile_".$devisId."_signatureBase64")));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Document\Devis'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'mobile_'.str_replace("-","_",$this->devisId);
    }

    public function getTechniciens() {
        return $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursTechnicien();
    }


}
