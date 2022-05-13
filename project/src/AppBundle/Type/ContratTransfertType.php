<?php

namespace AppBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Document\Contrat;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Doctrine\ODM\MongoDB\DocumentManager;


class ContratTransfertType extends AbstractType {

    protected $contrat;
    protected $dm;
    
    public function __construct(Contrat $c, DocumentManager $documentManager) {
        $this->contrat = $c;
        $this->dm = $documentManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('factures', CheckboxType::class, array('label' => 'Transférer les factures égalements', 'required' => false, 'data' => true, 'label_attr' => array('class' => 'control-label')));
        $builder->add('documents', CheckboxType::class, array('label' => 'Transférer les documents de l\' établissement égalements', 'required' => false, 'data' => true, 'label_attr' => array('class' => 'control-label')));
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    protected function addSociete(FormInterface $form, $societe = null)
    {
        $form->add('societe', DocumentType::class, array(
            'choices' => ($societe) ? array($societe) : array(),
            'required' => true,
            'class' => 'AppBundle\Document\Societe',
            'attr' => array("class" => "select2 select2-ajax", "data-placeholder" => "Rechercher une société"),
        ));
    }
    
    protected function addEtablissement(FormInterface $form, $societe = null)
    {
        $choices = array();
        if ($societe) {
        foreach ($societe->getEtablissements() as $e) {
            if(!$e->getActif()){
                continue;
            }
            $choices[$e->getId()] = $e->getIntitule();
        }
        }
        foreach ($this->contrat->getEtablissements() as $etablissement) {
            $form->add($etablissement->getId(), ChoiceType::class, array('label' => 'Etablissement* :', 'choices' => $choices, 'required' => true,  "attr" => array("class" => "select2 select2-simple select2-etablissements")));
        }
    }
    
    function onPreSubmit(FormEvent $event) {
        $form = $event->getForm();
        $values = $event->getData();
        $societe = (isset($values['societe']) && $values['societe']) ? $this->dm->getRepository('AppBundle:Societe')->find($values['societe']) : null;
        $this->addSociete($form, $societe);
        $this->addEtablissement($form, $societe);
    }
    function onPreSetData(FormEvent $event) {
        $form = $event->getForm();
        $document = $event->getData();
        $societe = ($document && isset($document['societe']))? $this->dm->getRepository('AppBundle:Societe')->find($document['societe']) : null;
        $this->addSociete($form, $societe);
        $this->addEtablissement($form, $societe);
    }

    /**
     * @return string
     */
    public function getName() {
        return 'contrat_transfert';
    }

}
