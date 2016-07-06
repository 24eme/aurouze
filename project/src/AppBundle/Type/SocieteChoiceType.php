<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SocieteChoiceType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $defaultChoice = array();
        if(isset($options['data']) && isset($options['data']['societe'])) {
            $defaultChoice = array($options['data']['societe']->getIntitule() => $options['data']['societe']->getIntitule());
        }
        $builder->add('actif', CheckboxType::class, array('label' => 'inclure les sociétés suspendues', 'required' => false, 'empty_data' => null, 'attr'=> array("data-search-actif" => "1")));
        $builder->add('societes', TextType::class, array("attr" => array("class" => "typeahead form-control", "placeholder" => "Rechercher une société")));
    }

    /**
     * @return string
     */
    public function getBlockPrefix() {
        return 'societe_choice';
    }

}
