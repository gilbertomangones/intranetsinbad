<?php
/**
* @file
* Contains Drupal\biblored_reports\form\reportsActivitiesForm
*/
namespace Drupal\biblored_reports\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\Core\Controller\ControllerBase;
use Drupal\biblored_reports\Controller\BackupsData;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;

use Drupal\Core\Url;
use Drupal\Core\Link;

class ReportsActivitiesForm extends FormBase
{

/**
* {@inheritdoc}
*/
public function getFormId() {
	return 'biblored_reports_reportsActivitiesForm'; 
}

/**
* {@inheritdoc}
*/

public function buildForm(array $form, FormStateInterface $form_state) {
 //1. Getting the concesión.
	$internals = new BackupsData;

	$items_concession = $internals->getConcessions();
	
	$form['concession'] = array (
     '#type' => 'select',
     '#title' => ('Concesión'),
     '#options' => $items_concession,
     '#validated' => TRUE,
     '#ajax' => [
          'callback' => '::getPlans',
          'wrapper' => 'plans-wrapper',
          'method' => 'replace',
          'effect' => 'fade',
        ],
   	);

	$form['plans'] = [
        '#type' => 'select',
        '#title' => 'Plán',
        '#required'=> TRUE,
        '#validated' => TRUE,
        '#prefix' => '<div id="plans-wrapper">',
        '#suffix' => '</div>',
        '#options' => $this->getOptionsPlans($form_state),
        '#ajax' => [
          'callback' => '::getLines',
          'wrapper' => 'lines-wrapper',
          'method' => 'replace',
          'effect' => 'fade',
        ],
    ];

    $form['line'] = array(
     '#type' => 'select',
     '#title' => 'Línea',
     '#description' => 'Línea Misional',
     '#options' => $this->getOptionsLines($form_state),
     '#prefix' => '<div id="lines-wrapper">',
     '#suffix' => '</div>',
     '#ajax' => [
        'callback' => '::getStrategy',
        'wrapper' => 'strategy-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ); 

    $form['strategy'] = [
      '#type' => 'select',
      '#title' => 'Estrategia',
      '#validated' => TRUE,
      '#prefix' => '<div id="strategy-wrapper">',
      '#suffix' => '</div>',
      '#options' => $this->getOptionsStrategy($form_state),
      '#ajax' => [
        'callback' => '::getPrograms',
        'wrapper' => 'programs-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
  	];

  	$form['program'] = [
      '#type' => 'select',
      '#title' => 'Programa',
      '#validated' => TRUE,
      '#prefix' => '<div id="programs-wrapper">',
      '#suffix' => '</div>',
      '#options' => $this->getOptionsPrograms($form_state),
  	];

  	$form['actions']['#type'] = 'actions';
  	$form['actions']['submit'] = [
    	'#type' => 'submit',
    	'#value' => $this->t('Buscar'),
    	'#button_type' => 'primary',
  	];

	$values = $form_state->getValues();
	
	if (!empty($values)) {
		
		$results = $form_state->get('field_count');
    $form_state->set('field_count', $results);
		
		$header = [
	        'id_program' => t("ID"),
	        'program' => t('PROGRAMA'),
	        'quantity' => t('CANTIDAD'),
      	];
     $i = 0;
      
      foreach ($results as $record) {
        $id = $record['field_backup_id_programa'];
      
        if (strlen(trim($id)) > 0) {
          $term = Term::load($id);
          $name = isset($term)?$term->getName():'Null';
          	
          $tabla[] = [
             'id_program' => $record['field_backup_id_programa'],
             'program' => $name,
             'quantity' => $record['nid_count'], 
           ];
        }
         $i++;
      }
//var_dump($tabla);
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $tabla,
      '#empty' => t('Empty'),
      ];
	}

	return $form;
}
/**
* {@inheritdoc}
*/
public function validateForm(array &$form, FormStateInterface $form_state) {
  //
}

/**
* {@inheritdoc}
*/
public function submitForm(array &$form, FormStateInterface $form_state) {
	/*drupal_set_message($this->t('Valores: @plan / @line / @strategy / @program', 
        [ '@plan' => $form_state->getValue('plans'),
          '@line' => $form_state->getValue('line'),
          '@strategy' => $form_state->getValue('strategy'),
          '@program' => $form_state->getValue('program'),
        ])
    );
    */
  $actividades = [];
  
  // Count number nodes grouped by program
  $query = \Drupal::entityQueryAggregate('node');
  
  //$query->join('node_revision__field_backup_name_programa', 'nfd', 'nfd.entity_id = node.entity_id');

  //$query = \Drupal::entityQuery('node');
  $query->condition('status', 1);
  $query->aggregate('nid', 'COUNT');

  $query->condition('type', 'actividad_ejecutada');
  $query->condition('field_concesion', $form_state->getValue('plans'));
  // In case is selected "_none == 'Ninguno'" && 
  if ($form_state->getValue('line') != '_none' && $form_state->getValue('line') != "") {
    $query->condition('field_backup_id_linea', $form_state->getValue('line')); 
  }
  if ($form_state->getValue('strategy') != '_none' && $form_state->getValue('strategy') != "") {
    $query->condition('field_backup_id_estrategia', $form_state->getValue('strategy'));
  }
  if ($form_state->getValue('program') != '_none' && $form_state->getValue('program') != "") {
    $query->condition('field_backup_id_programa', $form_state->getValue('program'));
  }
  
  $query->groupBy('field_backup_id_programa');
  
  $actividades = $query->execute();
  //$actividades = $query->count()->execute();
  $output = array('numact' => $actividades, 'porc_cumpl' => $porc_cumpl.'%'); 
  
  $output = [];
  $form_state->set('field_count',$actividades);
  $form_state->setRebuild(TRUE);

}

public function getPlans($form, FormStateInterface $form_state){
	return $form['plans'];
}

public function getLines($form, FormStateInterface $form_state){
  return $form['line'];
}

public function getStrategy($form, FormStateInterface $form_state){
  return $form['strategy'];
}

public function getPrograms($form, FormStateInterface $form_state){
  return $form['program'];
}

public function getOptionsPlans(FormStateInterface $form_state){
	
	$id = $form_state->getValue('concession');
  $plans = new BackupsData();
	$options = $plans->getPlansConcession($id);  
	
	return $options;
}
/**
 * Get the lines
 */

public function getOptionsLines(FormStateInterface $form_state){
  
  $plan = $form_state->getValue('plans');
  $lines = new BackupsData();
  $options = $lines->getLinesdependents($plan);  
  
  return $options;
}

public function getOptionsStrategy(FormStateInterface $form_state){
  
  $plan = $form_state->getValue('plans');
  $line = $form_state->getValue('line');
  $stategies = new BackupsData();
  $options = $stategies->getStrategyDependents($plan, $line);  

  return $options;
}

public function getOptionsPrograms(FormStateInterface $form_state){
  
  $plan = $form_state->getValue('plans');
  $line = $form_state->getValue('line');
  $strategy = $form_state->getValue('strategy');
  $programs = new BackupsData();
  $options = $programs->getProgramsDependents($plan, $line, $strategy);  

  return $options;
}



}
