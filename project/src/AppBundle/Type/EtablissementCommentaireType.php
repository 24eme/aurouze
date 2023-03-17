<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Doctrine\ODM\MongoDB\DocumentManager;

class EtablissementCommentaireType extends AbstractType
{
    protected $dm;
    protected $options;

    public function __construct(DocumentManager $documentManager,$options = null) {
        $this->dm = $documentManager;
        $this->options = $options;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('commentairePlanification', TextareaType::class, array('label' => 'Commentaire récurrent de planification :', 'required' => false, 'attr' => array('rows' => '3')))
            ->add('commentaire', TextareaType::class, array('label' => 'Information récurrent techniciens :', 'required' => false , 'attr' => array('rows' => '3')));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array('data_class' => 'AppBundle\Document\Etablissement'));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'etablissement_commentaire';
    }

}
