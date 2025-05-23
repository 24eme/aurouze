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
use AppBundle\Document\Compte;
use AppBundle\Document\Etablissement;
use AppBundle\Document\Societe;

class DevisType extends AbstractType
{

    protected $dm = null;
    protected $cm = null;
    protected $com = null;
    protected $societe = null;

    public function __construct($dm, $cm, $societe, $commercial) {
        $this->dm = $dm;
        $this->cm = $cm;
        $this->com = $commercial;
        $this->societe = $societe;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $datePrevisionModifiable = true;

        if($builder->getData()->getRendezvous() && $builder->getData()->getRendezvous()->getDateFin()){
          $datePrevisionModifiable = false;
        }

        $builder->add('etablissement', DocumentType::class, array('label' => 'Lieux de livraison : ',
                'choices' => $this->getEtablissements(),
                'class' => 'AppBundle\Document\Etablissement',
                'expanded' => false,
                'multiple' => false,
                'attr' => array("class" => "select2 select2-simple"),
            ))
            ->add('lignes', CollectionType::class, array(
                'entry_type' => new DevisLigneType($this->cm),
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'label' => '',
            ))
            ->add('numeroCommande', TextType::class, array('label' => 'Numéro de commande', 'required' => false, "attr" => array("class" => "form-control")))
            ->add('commentaireInterne', TextareaType::class, array('label' => 'Informations complémentaires', 'required' => false, "attr" => array("class" => "form-control", "rows" => 3)))
            ->add('commercial', DocumentType::class, array_merge(array('required' => false), array(
                "choices" => $this->getCommerciaux(),
                'label' => 'Commercial* :',
                'class' => 'AppBundle\Document\Compte',
                'expanded' => false,
                'multiple' => false,
                "attr" => array("class" => "select2 select2-simple"))));

              $datePrevisionInput =  array(
                    'label' => 'Date prévu de la signature',
                    "attr" => array(
                        'class' => 'input-inline datepicker',
                        'data-provide' => 'datepicker',
                        'data-date-format' => 'dd/mm/yyyy'
                    ),
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => false
                );

                if(!$datePrevisionModifiable){
                  $datePrevisionInput=array_merge($datePrevisionInput,array('disabled' => 'disabled'));
                }

            $currentTechniciens = $builder->getData()->getTechniciens()->toArray();
            $builder->add('datePrevision', DateType::class, $datePrevisionInput)
            ->add('techniciens', DocumentType::class, array(
                'choices' => $this->getParticipants($currentTechniciens),
                'class' => Compte::class,
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'attr' => array("class" => "select2 select2-simple", "multiple" => "multiple", "style" => "width:100%;")
            ));

            if ($builder->getData()->getId()) {
                $dateAcceptationInput =  array(
                    'required' => false,
                    'label' => 'Date acceptation du devis',
                    "attr" => array(
                        'class' => 'input-inline datepicker',
                        'data-provide' => 'datepicker',
                        'data-date-format' => 'dd/mm/yyyy'
                    ),
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy'
                );
                $builder->add('dateAcceptation', DateType::class, $dateAcceptationInput);
            }

              $savelabel = ($builder->getData()->getId()) ? 'Sauver' : 'Créer';
              $builder->add('edit', SubmitType::class, [
                  'label' => $savelabel,
                  'attr' => ['class' => 'btn btn-success']
              ]);

              if($builder->getData()->getDateAcceptation() && !$builder->getData()->getRendezVous()) {
              $builder->add('plan', SubmitType::class, [
                  'label' => 'Planifier',
                  'attr' => ['class' => 'btn btn-default']
              ]);
              }
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
        return 'devis';
    }

    public function getEtablissements() {

      return $this->dm->getRepository('AppBundle:Etablissement')->findAllOrderedByIdentifiantSociete($this->societe);

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

    public function getParticipants(array $currentTechniciens) {
        return array_unique(array_merge(
            $currentTechniciens,
            $this->dm->getRepository('AppBundle:Compte')->findAllUtilisateursTechnicienActif()->toArray()
        ));
    }

}
