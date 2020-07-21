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
        $builder->add('description', TextareaType::class, array('label' => 'Constat :', 'required' => false, "attr" => array("class" => " phoenix", "rows" => 10)))
                ->add('commentaireTechnicien', TextareaType::class, [
                    'label' => 'Commentaire technicien:',
                    'required' => false,
                    "attr" => array("class" => " phoenix", "rows" => 5)
                ]);
        //     ->add('dureeRaw', 'time', array(
        //     'input' => 'string',
        //     'widget' => 'single_text',
        //     "attr" => array("class" => " phoenix")));
        //
        //     $builder->get('dureeRaw')
        //         ->addModelTransformer(new CallbackTransformer(
        //             function ($dureeAsDateTime) {
        //                  if(!$dureeAsDateTime){
        //                    return "01:00:00";
        //                  }
        //                 return $dureeAsDateTime->format('H').':'.$dureeAsDateTime->format('i').":00";
        //             },
        //             function ($dureeAsString) {
        //               $today = new \DateTime(date('Y-m-d 00:00:00'));
        //               if(!$dureeAsString){
        //                 return $today->modify("+1 hours");
        //               }
        //               $dureeArr = explode(":",$dureeAsString);
        //               return \DateTime::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d')." ".$dureeArr[0].":".$dureeArr[1].":".$dureeArr[2]);
        //             }
        //         ))
        //     ;


        //
        // $builder->add('produits', CollectionType::class, array(
        //     'entry_type' => new ProduitPassageMobileType($this->dm),
        //     'allow_add' => true,
        //     'allow_delete' => true,
        //     'delete_empty' => true,
        //     'label' => '',
        //     'attr' => array("placeholder" => 'Produit utilisé'),
        // ));

        // $builder->add('niveauInfestation', CollectionType::class, array(
        //     'entry_type' => new NiveauInfestationPassageMobileType($this->dm),
        //     'allow_add' => true,
        //     'allow_delete' => true,
        //     'delete_empty' => true,
        //     'label' => '',
        //     'attr' => array("placeholder" => 'Niveau d\'infestation'),
        // ));
        //
        //
        // $builder->add('nettoyages', ChoiceType::class, array(
        // 		'label' => 'Nettoyage : ',
        // 		'choices' => $this->getNettoyages(),
        // 		'expanded' => false,
        // 		'multiple' => true,
        // 		'required' => false,
        // 		'attr' => array("class" => "phoenix ui-li-has-count", "multiple" => "multiple", "data-native-menu" => "false","placeholder" => 'Nettoyage'),
        // ));
        // //$builder->get('nettoyages')->resetViewTransformers();
        //
        // $builder->add('applications', ChoiceType::class, array(
        // 		'label' => 'Respect des applications : ',
        // 		'choices' => $this->getApplications(),
        // 		'expanded' => false,
        // 		'multiple' => true,
        // 		'required' => false,
        // 		'attr' => array("class" => "phoenix ui-li-has-count", "multiple" => "multiple", "data-native-menu" => "false","placeholder" => 'Applications')
        // ));
      //  $builder->get('applications')->resetViewTransformers();
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
