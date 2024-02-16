<?php
/**
* @file
* Contains \Drupal\reportes\Form\GenerarReportesForm.
*/
namespace Drupal\reportes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\reportes\Controller\FuncionesController;
use Drupal\biblored_module\Controller\EvEndpoint;
use Drupal\taxonomy\Entity\Term; // Leer campos que dependan de Términos 
use Drupal\Core\Datetime\DrupalDateTime; // Condition date
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;

/**
* Implements an reports module form.
*/
class GenerarReportesForm extends FormBase {

/**
 * {@inheritdoc}
 */
public function getFormId() {
  return 'reportes_generarreportesform'; //nombremodule_nombreformulario  
}

/**
 * {@inheritdoc}
 */
public function buildForm(array $form, FormStateInterface $form_state) {
  
  // BIBLIOTECAS
  $vid = 'nodos_bibliotecas';

  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
  $bibliotecas[0] = "Todos";
  foreach($terms as $term) {
    
       if ($term->depth == 0) { // 0 PARA EL PADRE
           // Array con todas las bibliotecas
               $term_data[] = array(
                   "id" => $term->tid,
                   "name" => $term->name,
                   //'tid_biblioteca_agenda' => \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'],
               );
               $bibliotecas[$term->tid] = $term->name;
       }
   }

  // SOLO LINEAS
  $vid_linea = 'areas';
    // Obtener solo los tid del nivel 1  
  $terms_lineas =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_linea, $parent = 0, $max_depth = 1, $load_entities = FALSE);

  $terms_programas =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_linea, $parent = 0, $max_depth = 1, $load_entities = FALSE);
  $lineas[0] = "Todas";
  foreach($terms_lineas as $linea) {
    //$tid_linea_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($linea->tid)->get('field_tid_linea_agenda')->getValue()[0]['value'];
      $term_data_linea[] = array(
        "name" => $linea->name,
        //'tid_linea_agenda' => $tid_linea_agenda,
        "id" => $linea->tid,
      );        
      $lineas[$linea->tid] = $linea->name; 
  }

  // PROGRAMAS DE UNA LINEA
  //$terms_programas = $statistics->programas(5);

$form['biblioteca'] = array(
     '#type' => 'select',
     '#title' => 'Espacio',
     '#description' => 'Espacio',
     '#options' => $bibliotecas,
     '#ajax' => [
        'callback' => '::getBibliotecas',
        'wrapper' => 'bibliotecas-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    );

/*
if ($form_state->getValue('biblioteca')){
  $form['biblioteca']['#default_value'] = $form_state->getValue('biblioteca');
}*/
$form['biblioteca_2'] = [
      '#type' => 'select',
      '#title' => 'Biblioteca',
      '#required' => TRUE,
      '#prefix' => '<div id="bibliotecas-wrapper">',
      '#suffix' => '</div>',
  ];

/*if ($form_state->getValue('biblioteca_2')){
  $form['biblioteca_2']['#default_value'] = $form_state->getValue('biblioteca_2');
}
*/

  $form['linea'] = array(
     '#type' => 'select',
     '#title' => 'Línea',
     '#description' => 'Línea Misional',
     '#options' => $lineas,
     '#ajax' => [
        'callback' => '::getProgramas',
        'wrapper' => 'programas-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ); 

  $form['dep']['programas'] = [
      '#type' => 'select',
      '#title' => 'Programas',
      //'#validated' => TRUE,
      //'#options' =>  array(),
      '#empty_option' => $this->t('Programas'),
      '#default_value' => '',
      '#prefix' => '<div id="programas-wrapper">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::getSubprogramas',
        'wrapper' => 'programas-wrapper2',
        'method' => 'replace',
        'effect' => 'fade',
      ],
  ];
 
  $form['dep']['subprogramas'] = [
      '#type' => 'select',
      '#title' => 'Subprogramas',
      '#required' => TRUE,    
      //'#options' =>  array(),
      '#empty_option' => $this->t('Subprogramas'),
      //'#default_value' => '',
      '#prefix' => '<div id="programas-wrapper2">',
      '#suffix' => '</div>',
  ];

  $form['fechainicial'] = array(
    '#title' => t('Entre Fecha inicial'),
    '#type' => 'date',
    '#required' => true,
  );
  $form['fechafinal'] = array(
    '#title' => t('y Fecha final'),
    '#type' => 'date',
    '#required' => true,
  );
  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => $this->t('Buscar'),
    '#button_type' => 'primary',
  ];
  $form['exp'] = [
      '#title' => $this->t('Reports'),
      '#type' => 'link',
      '#url' => 'javascript:;',
      '#attributes' => array('id' => 'exportar'),
    ];
  $values = $form_state->getValues();
  
  if (!empty($values)) {
    //$result = $values['number1'] + $values['number2'];
    //$form['result'] = ['#markup' => '<p>' . $result . '</p>'];
    
    $form['exportar'] = [
      '#type' => 'processed_text',
      '#text' => "<a id='exportar' href='javascript:;'>Exportar</a>",
      '#format' => 'full_html',
    ];

    $resultados = $form_state->get('field_count');
    
    $form_state->set('field_count', $resultados);
    /*
    $form['results'] = [
      '#type' => 'processed_text',
      '#text' => $resultados,//$form_state->getValue('biblioteca')."hola",
      '#format' => filter_default_format()
    ]; 
    */
    $header = array();
    $header = [
        //'ID' => t("ID"),
        'biblioteca' => t('BIBLIOTECA'),
        'subprograma' => t('SUBPROGRAMA'),
        'meta1' => t('META PROCESO'),
        'porc1'  => t('% CUMPLIMIENTO PROCESO'),
        'numact'=> t('No. ACTIVIDADES'),  
        'meta2' => t('META PRODUCTO'),  
        'porc2'  => t('% CUMPLIMIENTO PRODUCTO'),
        'numasis'=> t('No. ASISTENTES'),
      ];
       
    // RESULTADOS
    $i = 0;
    $tabla = array();
    foreach ($resultados as $key => $record) {
      
      $tabla[] = [
         //'ID' => $record[0]['nid'],
         'biblioteca' => $record[0]['biblioteca'],
         'subprograma' => $record[0]['subprograma'],
         'meta1' => $record[0]['meta1'], 
         'porc1' => number_format((float)$record[0]['porc_cumpl'],2)."%",
         'numact' => $record[0]['numact'],
         'meta2' => $record[0]['meta2'],
         'porc2'  => number_format((float)$record[0]['porc_cumpl_prod'],2)."%",
         'numasis' => $record[0]['num_asistentes'],
       ];
       $i++;
    }

    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $tabla,
      '#empty' => t('Si información encontrada'),
      ]; 
  }

  return $form;
}

/**
 * {@inheritdoc}
 */
public function validateForm(array &$form, FormStateInterface $form_state) {
  /* if (strlen($form_state->getValue('phone_number')) < 3) {
    $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number.'));
  } */
}

/**
 * {@inheritdoc}
 */ 
public function submitForm(array &$form, FormStateInterface $form_state) {
  global $base_url;
  /*drupal_set_message($this->t('Valores: @biblioteca / @biblioteca_2 /@linea /@programas /@subprogramas',  
      [ '@biblioteca' => $form_state->getValue('biblioteca'),
      '@biblioteca_2' => $form_state->getValue('biblioteca_2'),
      '@linea' => $form_state->getValue('linea'),
      '@programas' => $form_state->getValue('programas'),
      '@subprogramas' => $form_state->getValue('subprogramas'),
      ])
  ); */
  $fechaini = $form_state->getValue('fechainicial');
  $fechafin = $form_state->getValue('fechafinal');
  $output1 = array();
  $union  = array();
  $linea = "";
  $programa = "";
  $subprograma = "";
  $bibli = 0;
  $porc_cumpl = 0;
  $linea = $form_state->getValue('linea');
  $programa = $form_state->getValue('programas');
  $prog = $form_state->getValue('programas');
  $subprograma = $form_state->getValue('subprogramas');
  $bibli =  $form_state->getValue('biblioteca_2');

  $statistics = new FuncionesController;
  $db = \Drupal::database();
  $valor_linea = 0;

  $fecha_busqueda = "&fecha%5Bmin%5D=".$fechaini."&fecha%5Bmax%5D=".$fechafin;

  $host = \Drupal::request()->getHost();   
  $base_url_parts = parse_url($base_url); 
  $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];

  if (($linea != "" || $linea =="All") && ($programa == "All" || $programa == "") && $subprograma =="") {
      $valor_linea = $linea;
    }elseif (($linea != "" || $linea !="All") && ($programa != "All") && $subprograma == "All"){
      $valor_linea = $programa;
    }elseif($linea != "All" && $programa != "All" && $subprograma != "All"){
      $valor_linea = $subprograma;
    }

  
  $endpoint = $host."/json/planoperativo-actividades/".$bibli."/".$valor_linea;
    
  $output = $statistics->entidades($endpoint);

  foreach ($output as $value) {
        $entity_ids[] = $value['nid'];
  }
      
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
  // PASO 1 - PLAN OPERTATIVO
 
  // Por cada plan operativo encontrado
  // 
  foreach ($nodes as $key => $value) {
    
    $nid = $value->nid->value;
    
    $field_biblioteca = $value->field_biblioteca->target_id;
    
    //$title =  $value->title->value;
    //$description =  strip_tags($value->body->value);
    $field_meta_sesiones      = $value->field_meta_sesiones->value; //Meta proceso
    $field_numero_asistentes  = $value->field_numero_asistentes->value; // Meta producto
    $field_linea_tid          = $value->field_linea->target_id;
    $subprograma              = $statistics->nombreTermino($field_linea_tid);
    
    $output1 = array(
      'nid' => $nid,
      'biblioteca' => $statistics->nombreTermino($field_biblioteca),
      'subprograma' => $subprograma, 
      'meta1' => $field_meta_sesiones,
      'meta2' => $field_numero_asistentes,
    );   
    

    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'actividad_ejecutada');
    
    $query->condition('field_linea', $field_linea_tid); // Linea misional, programa o subprograma  
    
    $query->condition('field_biblioteca', $field_biblioteca);  
    $query->accessCheck(TRUE);
    $actividades = $query->count()->execute();
	
    $porc_cumpl = ($field_meta_sesiones>0) ? ($actividades/$field_meta_sesiones)*100 : 0;

    $output2 = array('numact' => $actividades, 'porc_cumpl' => $porc_cumpl.'%'); 


    // cada actividad 
    $query3 = \Drupal::entityQuery('node');
  	$query3->accessCheck(TRUE);
    $query3->condition('status', 1);
    $query3->condition('type', 'actividad_ejecutada');
    $query3->condition('field_linea', $field_linea_tid);
    $query3->condition('field_biblioteca', $field_biblioteca); 
	
    $entity_ids_actividades = $query3->execute();
    
  //$nids = array_keys($entity_ids);
  
  $nodes_actividades = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids_actividades);
  
    $_asistentes = 0;
    foreach ($nodes_actividades as $key => $value) {
      $_asistentes  += $value->field_numero_asistentes->value;
    }
    $porc_cumpl_asis = ($field_numero_asistentes > 1) ? ($_asistentes/$field_numero_asistentes)*100 : 0;

    $output3 = array('num_asistentes' => $_asistentes, 'porc_cumpl_prod'=>$porc_cumpl_asis.'%');
   

    $union[] = array(array_merge($output1, $output2, $output3));
    
  }
    

    $form_state->set('field_count',$union);
    
    $form_state->setRebuild(); // Esta es la clave
    
 
  //drupal_set_message($this->t('Your phone number is @number:', ['@number' => $form_state->getValue('phone_number')]));
}


function getProgramas($form, FormStateInterface $form_state) {

  $statistics = new FuncionesController;
  $options = $statistics->programas($form_state->getValue('linea'));
  
  $form['dep']['programas']['#options'] = $options;
  
  /*$form['dep']['subprogramas'] = [
      '#options'=> $statistics->subprogramas($form_state->getValue('programas')),
  ];*/

  return $form['dep']['programas'];
}

function getSubprogramas($form, FormStateInterface $form_state) {
  $statistics = new FuncionesController;
  $form['dep']['subprogramas']['#options'] = $statistics->subprogramas($form_state->getValue('programas'));
  
  return $form['dep']['subprogramas'];
}

function getBibliotecas($form, FormStateInterface $form_state) {

  $statistics = new EvEndpoint;
  $options = $statistics->bibliotecas_sistema($form_state->getValue('biblioteca'));  
  $form['biblioteca_2']['#options'] = $options;
  
  return $form['biblioteca_2'];
}


}