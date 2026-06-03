<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Manager\PaiementsManager;
use AppBundle\Manager\ContratManager;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class StatistiquesController extends Controller
{

	/**
	 * @Route("/statistiques", name="statistiques")
	 */
	public function indexAction() {
		$exportForms = $this->createExportsForms();
		return $this->render('statistiques/index.html.twig', array('exportForms' => $exportForms));
	}

    private function createExportsForms()
    {

      $exportsTypes = PaiementsManager::$types_exports;
      $exportForms = array();
      foreach ($exportsTypes as $exporttype => $type_export) {
        $exportForms[$exporttype] = new \stdClass();
        $exportForms[$exporttype]->type = $exporttype;
        $exportForms[$exporttype]->libelle = $type_export['libelle'];
        $exportForms[$exporttype]->picto = $type_export['picto'];
        $exportForms[$exporttype]->pdf = $type_export['pdf'];
        $formBuilder = $this->createFormBuilder(array());
            $formBuilder->add('dateDebut', DateType::class, array('required' => true,
                "attr" => array('class' => 'input-inline datepicker',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd/mm/yyyy'
                    ),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'label' => 'Date de début* :',
            ));
            if($exporttype != PaiementsManager::TYPE_EXPORT_PCA){
              $formBuilder->add('dateFin', DateType::class, array('required' => true,
                "attr" => array('class' => 'input-inline datepicker',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd/mm/yyyy'
                    ),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'label' => 'Date de fin* :',
            ));
          }

          if(in_array($exporttype, array(PaiementsManager::TYPE_EXPORT_CONTRATS, PaiementsManager::TYPE_EXPORT_STATS, PaiementsManager::TYPE_EXPORT_DETAIL_CA))) {
            $commerciaux =$this->get('doctrine_mongodb')->getManager()->getRepository('AppBundle:Compte')->findAllUtilisateursCommercial();
            $formBuilder->add('commercial', DocumentType::class, array(
                'required' => false,
                "choices" => array_merge(array('' => ''), $commerciaux),
                'label' => 'Commercial :',
                'class' => 'AppBundle\Document\Compte',
                'expanded' => false,
                'multiple' => false,
                "attr" => array("class" => "select2 select2-simple", "data-placeholder" => "Sélectionner un commercial", "style"=> "width:100%;")));
        	}
					if(in_array($exporttype, array(PaiementsManager::TYPE_EXPORT_DETAIL_CA_PRESTA))) {
						$prestations = $this->get('doctrine_mongodb')->getManager()->getRepository('AppBundle:Configuration')->findConfiguration()->getPrestationsArray();
						$formBuilder->add('prestation', ChoiceType::class, array(
							'required' => false,
							'choices'  => array_merge(array('' => ''), $prestations),
							'label' => 'Prestation :',
							"attr" => array("class" => "select2 select2-simple","data-placeholder" => 'Prestation', "style"=> "width:100%;")));
					}
          if($type_export['pdf']){
            $formBuilder->add('pdf', CheckboxType::class, array('label' => 'PDF', 'required' => false, 'label_attr' => array('class' => 'small')));
          }
        $formBuilder->setAction($this->generateUrl($exporttype.'_export'));
        $form = $formBuilder->getForm();

        $exportForms[$exporttype]->form = $form->createView();
      }
      return $exportForms;
    }
}
