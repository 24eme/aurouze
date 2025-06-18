<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\CallbackTransformer;
use AppBundle\Document\Attachement;

class AttachementTourneeType extends AbstractType
{
    protected $dm;

    public function __construct(DocumentManager $documentManager) {
        $this->dm = $documentManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
          ->add(
              $builder->create(
                'imageFile', FileType::class, [
                    'required' => false,
                    'label' => 'Choisir un document (.jpg, .png, .pdf)',
                    'multiple' => true
                ]
              )->addModelTransformer(new CallbackTransformer(
                  function () { },
                  function ($files) {
                      $attachements = [];
                      foreach ($files as $file) {
                          $attachement = new Attachement();
                          $attachement->setImageFile($file);
                          $attachements[] = $attachement;
                      }
                      return $attachements;
                  }
              ))
          )
          ->add('titre', TextType::class, array('label' => 'Nom* :','required' => false))
          ->add('updatedAt', DateType::class, array(
            'required' => false,
            'attr' => array(
                'class' => 'input-inline datepicker',
                'data-provide' => 'datepicker',
                'data-date-format' => 'dd/mm/yyyy'
            ),
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy',
            'data' => new \DateTime("now"),
            'label' => 'Date d\'ajout :',
          ));
          $builder->add('visibleClient', CheckboxType::class, array('label' => ' ', 'required' => false, "attr" => array("class" => "switcher", "data-size" => "mini")));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'attachement';
    }

}
