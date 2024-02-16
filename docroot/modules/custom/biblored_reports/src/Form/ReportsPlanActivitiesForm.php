<?php
/**
* @file
* Contains Drupal\biblored_reports\form\ReportsPlanActivitiesForm
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

class ReportsPlanActivitiesForm extends FormBase
{
/**
* {@inheritdoc}
*/
public function getFormId() {
	return 'biblored_reports_reportsPlanActivitiesForm'; 
}

/**
* {@inheritdoc}
*/

public function buildForm(array $form, FormStateInterface $form_state) {
 //1. Getting the concesión.
	$internals = new BackupsData;
  $table_mix = [];

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
      '#required'=> TRUE,
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

    $results_plans = $form_state->get('field_count_plans');
    $form_state->set('field_count_plans', $results_plans);

		$header_plan = [
          'id_program' => t("ID"),
          'program' => t('Programa'),
          'quantity' => t('Plan'),
          'origin' => '...',
        ];
    $header_activity = [
          'id_program' => t("ID"),
          'program' => t('Programa'),
          'quantity' => t('Actividad'),
          'origin' => '...',
        ];
		$header = [
	        'id_program' => t("ID"),
	        'program' => t('PROGRAMA'),
	        'quantity_plan' => t('PLAN'),
          'quantity_activity' => t('ACTIVIDAD'),
          'porc_cumpl' => '%',
      	];

     $i = 0;
      // Actividades
     $content_activities = [];
     $content_activities = $this->get_content_activities($results);
     $content_plans = $this->get_content_plans($results_plans);
      
     $table_mix = array_merge($content_activities, $content_plans);

     // Agrupar planes y actividades
     $output_grouped = $this->grouped_act_plan($table_mix);
    
     $content_vs =  $this->get_content_vs($output_grouped);
    /*
    $form['table_plans'] = [
      '#type' => 'tableselect',
      '#header' => $header_plan,
      '#options' => $content_plans,
      '#empty' => t('Empty'),
      ];

    $form['table_activity'] = [
      '#type' => 'tableselect',
      '#header' => $header_activity,
      '#options' => $content_activities,
      '#empty' => t('Empty'),
      ];
    */
    $form['table_vs'] = [
      '#title' => t('Reporte integrado de Plan de acción vs Actividades ejecutadas'),
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $content_vs,
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
  // 1. Query for Avtivities
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
  
  $output = array('numact' => $actividades, 'porc_cumpl' => $porc_cumpl.'%'); 
  
  $form_state->set('field_count',$actividades);

  //2. Query for plans
  $query = \Drupal::entityQuery('node');
  $query->accessCheck(TRUE);
  $query->condition('status', 1);
  $query->condition('type', 'plan_de_accion_concesion');

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

  $nids = $query->execute();
  $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);
  
  $form_state->set('field_count_plans',$nodes);

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
/**
* Get the activities 
* Return array
*/
public function get_content_activities($results) {
  //var_dump($results);
  foreach ($results as $record) {
        $id = $record['field_backup_id_programa'];
        if (!empty($id)) {
          $term = Term::load($id);
          $name = isset($term) ? $term->getName() : 'Null';
          $tabla_activity[] = [
             'id_program' => isset($record['field_backup_id_programa']) ? $record['field_backup_id_programa'] : 'Null',
             'program' => $name,
             'quantity' => $record['nid_count'], 
             'origin' => 'activity',
           ];
        }
         $i++;
      }
  return $tabla_activity;
}
/**
 * get plans
 */
public function get_content_plans($results_plans) {
  foreach ($results_plans as $record) {
        $id = $record->get('field_backup_id_programa')->value;

        if (!empty($id)) {
          $id_program = $record->get('field_backup_id_programa')->value;
          $name_program = isset($record->get('field_backup_name_programa')->value) ? $record->get('field_backup_name_programa')->value : "";
          $quantity = $record->get('field_proc_interna')->value + $record->get('field_proc_externo')->value;
          
          $tabla_plans[] = [
           'id_program' => $id_program,
           'program' => $name_program,
           'quantity' => $quantity, 
           'origin' => 'plan',
         ];
        }
      }
  return $tabla_plans;
}

public function grouped_act_plan($table_mix) {
  $output = [];
    foreach ($table_mix as $key => $value) {
      $output[$value['id_program']][$value['origin']]['value'] = $value['quantity'];
      $output[$value['id_program']][$value['origin']]['name'] = $value['program'];
    }
  return $output;
}

public function get_content_vs($output) {

  $tabla_vs = [];
      
      foreach ($output as $key => $value) {
        $quantity_plan = 'Null';
        $quantity_activity = 'Null';
        if (isset($value['plan'])) {
          $name_program = $value['plan']['name'];
          $id_program = $key;
          $quantity_plan = !empty($value['plan']['value']) ? $value['plan']['value'] : 0;
        }
        if (isset($value['activity'])) {
          $name_program = $value['activity']['name'];
          $id_program = $key;
          $quantity_activity = !empty($value['activity']['value']) ? $value['activity']['value'] : 0;
        }

        // Porcentaje de cumplimiento
        if ($quantity_activity > 0 && $quantity_plan > 0){
          $porc_cumpl = round(($quantity_activity / $quantity_plan * 100), 2);
        }elseif ($quantity_activity > 0 && $quantity_plan ==  'Null') {
          $porc_cumpl = 'No Plan';
        } elseif ($quantity_activity == 'Null' && $quantity_plan > 0) {
          $porc_cumpl = 'No Actividades';
        }
        $tabla_vs[] = [
           'id_program' => $id_program,
           'program' => $name_program,
           'quantity_plan' => $quantity_plan, 
           'quantity_activity' => $quantity_activity,
           'porc_cumpl' => $porc_cumpl,
         ];
      }
    return $tabla_vs;
}

}