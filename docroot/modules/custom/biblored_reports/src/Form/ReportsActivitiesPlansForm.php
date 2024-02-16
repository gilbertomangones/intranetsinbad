<?php
/**
* @file
* Contains Drupal\biblored_reports\form\reportsActivitiesPlansForm
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

class ReportsActivitiesPlansForm extends FormBase
{

/**
* {@inheritdoc}
*/
public function getFormId() {
  return 'biblored_reports_reportsActivitiesPlansForm'; 
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
    //$nodes =  \Drupal\node\Entity\Node::loadMultiple($results);
    //var_dump($nodes);
    $header = [
          'id_program' => t("ID"),
          'program' => t('PROGRAMA'),
          'quantity' => t('CANTIDAD'),
        ];
     $i = 0;
      
      
      foreach ($results as $record) {
        $id = $record->get('field_backup_id_programa')->value;

        if (!empty($id)) {
          $proc_interna = isset($record->get('field_proc_interna')->value) ? $record->get('field_proc_interna')->value : 0;
          $proc_externo = isset($record->get('field_proc_externo')->value) ?  $record->get('field_proc_externo')->value : 0;
          $id_program = $record->get('field_backup_id_programa')->value;
          
          $name_program = isset($record->get('field_backup_name_programa')->value) ? $record->get('field_backup_name_programa')->value : "";
          $quantity = $proc_interna + $proc_externo;
          
          $tabla[] = [
           'id_program' => $id_program,
           'program' => $name_program,
           'quantity' => $quantity, 
         ];
        }

         $i++;
      }
    
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
  
  $query = \Drupal::entityQuery('node');
  $query->condition('status', 1);
  $query->accessCheck(TRUE);
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
  
  //$actividades = $query->count()->execute();
  
  $output = [];
  $form_state->set('field_count',$nodes);
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
