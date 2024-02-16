<?php
/**
* @file
* Contains \Drupal\reportes\Form\Generar_planaccion_ejecucionForm.
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
class Generar_planaccion_ejecucionForm extends FormBase {

/**
 * {@inheritdoc}
 */
public function getFormId() {
  return 'reportes_generar_planaccion_ejecucionform'; //nombremodule_nombreformulario  
}

/**
 * {@inheritdoc}
 */
public function buildForm(array $form, FormStateInterface $form_state) {
  $vid_concesion = 'concesion';
  $terms_plan =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_concesion, $parent = 0, $max_depth = 1, $load_entities = FALSE);

    $concesion['All'] = "Todas";

  foreach ($terms_plan as $key => $term) {
    $concesion[$term->tid] = $term->name;
  }
  

  // BIBLIOTECAS
  $vid = 'nodos_bibliotecas';

  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
  $bibliotecas['All'] = "Todas";
 
  foreach($terms as $term) {
    
       $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];
       
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
  $lineas['All'] = "Todas";
  foreach($terms_lineas as $linea) {
    //$tid_linea_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($linea->tid)->get('field_tid_linea_agenda')->getValue()[0]['value'];
      $term_data_linea[] = array(
        "name" => $linea->name,
        //'tid_linea_agenda' => $tid_linea_agenda,
        "id" => $linea->tid,
      );        
      $lineas[$linea->tid] = $linea->name; 
  }

  $form['concesion'] = array(
       '#type' => 'select',
       '#title' => 'Concesión',
       '#description' => 'Concesión',
       '#validated' => TRUE,
       '#options' => $concesion,
       '#ajax' => [
          'callback' => '::getPlanes',
          'wrapper' => 'planes-wrapper',
          'method' => 'replace',
          'effect' => 'fade',
        ],
      );
  $form['planes'] = [
        '#type' => 'select',
        '#title' => 'Planes',
        '#required'=> TRUE,
        '#validated' => TRUE,
        '#prefix' => '<div id="planes-wrapper">',
        '#suffix' => '</div>',
    ];
  // PROGRAMAS DE UNA LINEA
  //$terms_programas = $statistics->programas(5);

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
      '#validated' => TRUE,
      //'#options' =>  array(),
      '#empty_option' => $this->t('Programas'),
      '#default_value' => '',
      '#prefix' => '<div id="programas-wrapper">',
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
    'required' => true,
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
  
    //$result = $values['number1'] + $values['number2'];
    //$form['result'] = ['#markup' => '<p>' . $result . '</p>'];
    if (!empty($values)) {
        $form['exportar'] = [
          '#type' => 'processed_text',
          '#text' => "<a id='exportar' href='javascript:;'>Exportar</a>",
          '#format' => 'full_html',
        ];
    
    $resultados = $form_state->get('field_count');
    

    $form_state->set('field_count', $resultados);

    $header = [
        //'ID' => t("ID"),
        'programa' => t('PROGRAMA'),
        'proceso_nocontratado' => t('Proceso no contratado (Plan acción)'),
        'proceso_contratado' => t('Proceso contratado (Plan acción)'),
        'meta_proceso' => t('Meta Proceso plan acción'),
        'recurso_invertido' => t('Recurso planeado'),
        'porc1'  => t('% cumplimiento'),        
        'prod_nocontratado' => t('No contratado (Meta producto) '), 
        'prod_contratado' => t('Contratado (Meta producto)'),
        'meta_producto' => t('Meta producto plan acción'),  
        'porc2'  => t('% cumplimiento '),
        
      ];
       
    // RESULTADOS
    $i = 0;
    
    foreach ($resultados as $key => $record) {
      
      $tabla[] = [
         //'ID' => $record[0]['nid'],
         'programa' => $record[0]['programa'],
         'proceso_nocontratado' => $record[0]['proceso_nocontratado'], 
         'proceso_contratado' => $record[0]['proceso_contratado'],
         'meta_proceso' => $record[0]['meta_sesiones'],
         'recurso_invertido' => $record[0]['invertido'],
         'porc1'  => number_format($record[0]['porc_cumpl_metaproceso'],2)."%", 
         'prod_nocontratado' => $record[0]['producto_nocontratado'],
         'prod_contratado' => $record[0]['producto_contratado'],
         'meta_producto' => $record[0]['meta_producto'],
         'porc2' => number_format($record[0]['porc_cumpl_metaproducto'],2)."%",
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
  $statistics = new FuncionesController;
  /*drupal_set_message($this->t('Valores: /@linea /@programas',  
      [ '@linea' => $form_state->getValue('linea'),
      '@programas' => $form_state->getValue('programas'),
      ])
  );*/
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
      $concesion = $form_state->getValue('concesion');
      $plan = $form_state->getValue('planes');
      
      $statistics = new FuncionesController;
      $db = \Drupal::database();
      $valor_linea = "All";
      $valor_plan = "All";
      $host = \Drupal::request()->getHost();   
      $base_url_parts = parse_url($base_url); 
      $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];

      if ($concesion != "" && $plan != "") {
        $valor_plan = $plan;
      }
      
      if ($linea != "" && $programa != "All" ) {
        $valor_linea = $programa;
      }elseif ($linea !="" && ($programa == "" || $programa == "All")) {
        $valor_linea = $linea;
      }
      
      $fecha_busqueda = "&fecha%5Bmin%5D=".$fechaini."&fecha%5Bmax%5D=".$fechafin;

      
      $endpoint = $host."/json/planaccion?plan=".$valor_plan."&prog=".$valor_linea.$fecha_busqueda;

      $output = $statistics->entidades($endpoint);

      foreach ($output as $value) {
        $entity_ids[] = $value['nid'];
      }
      
      // PLANES ACCIÓN
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
      
      foreach ($nodes as $key => $value) {
        
        $nid_plan_accion = $value->nid->value;
        $programa_plan = $value->field_linea->target_id;
        $nom_programa_plan = $statistics->nombreTermino($programa_plan);
        
        $concesion = $value->field_concesion->target_id;
        $nom_plan_concesion = $statistics->nombreTermino($concesion);
        // Meta proceso
        $field_proc_interna = $value->field_proc_interna->value; // nocontratada
        $field_proc_externo = $value->field_proc_externo->value; // contratada
        $field_recurso_invertido = $value->field_recurso_invertido->value; //presupuesto
        $field_meta_sesiones_plan = $value->field_meta_sesiones->value; // Meta sesiones
        
        // Meta producto
        $field_prod_interno = $value->field_prod_interno->value; // No contratado        
        $field_prod_externo = $value->field_prod_externo->value;// Contratado        
        $field_numero_asistentes_plan = $value->field_numero_asistentes->value; // Meta asistentes

        // Meses
        $field_total_porcentaje_ejecucion = $value->field_total_porcentaje_ejecucion->value;        
        // ************************* Fase 2 ********************************/
        // OBTENER TODAS LAS ACTIVIDADES EJECUTADAS INGRESADAS QUE PERTENEZCAN AL FILTRO EJECUTADO
        $endpoint_actividades = $host."/json/detalleactividades?subprog=".$programa_plan;
        
        $output_actividades = $statistics->entidades($endpoint_actividades);

        //var_dump($output_operativo);

        foreach ($output_actividades as $value) {
          $entity_ids[] = $value['nid'];
        }
        //var_dump($entity_ids);
        
        $nodes_operativos = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
        $sum_proc_nocontratado_operativo = 0;
        $sum_proc_contratado_operativo = 0;
        $sum_metasesiones = 0;
        $sum_prod_nocontratado_operativo = 0;
        $sum_prod_contratado_operativo = 0;
        $sum_asistentes = 0;
        
        $field_numero_asistentes_0_5_ = 0;
        $field_numero_asistentes_6_12_ = 0;
        $field_numero_asistentes_13_18_= 0;
        $field_numero_asistentes_19_27_= 0;
        $field_numero_asistentes_28_60 = 0;
        $field_numero_asistentes_61_mas= 0;
        $field_numero_asistentes = 0;
        $field_participantes_de_genero_ma = 0;
        $field_participantes_de_genero_fe = 0;
        $field_participantes_de_genero_tr = 0;

        $cont_actividades = 0;
        foreach ($nodes_operativos as $key => $value_ejec) {
          
          $nid_actividad = $value_ejec->nid->value;
          $subprograma = $value_ejec->field_linea->target_id;
          
          $field_numero_asistentes_0_5_  += $value_ejec->field_numero_asistentes_0_5_->value;
          $field_numero_asistentes_6_12_ += $value_ejec->field_numero_asistentes_6_12_->value;
          $field_numero_asistentes_13_18_+= $value_ejec->field_numero_asistentes_13_18_->value;
          $field_numero_asistentes_19_27_+= $value_ejec->field_numero_asistentes_19_27_->value;
          $field_numero_asistentes_28_60 += $value_ejec->field_numero_asistentes_28_60->value;
          $field_numero_asistentes_61_mas+= $value_ejec->field_numero_asistentes_61_mas->value;
          
          $field_participantes_de_genero_ma += $value_ejec->field_participantes_de_genero_ma->value;
          $field_participantes_de_genero_fe += $value_ejec->field_participantes_de_genero_fe->value;
          $field_participantes_de_genero_tr += $value_ejec->field_participantes_de_genero_tr->value;
         
          $field_numero_asistentes += $value_ejec->field_numero_asistentes->value;
          $cont_actividades++;

        }

        $porc_cumpl_metaproceso_planaccion = ($cont_actividades/$field_meta_sesiones_plan)*100;
        $proc_cumpl_metaproducto_planaccion= ($field_numero_asistentes/$field_numero_asistentes_plan)*100;

        
        $output1 = array(
          //'nid' => isset($nid_plan_accion)?$nid_plan_accion:"",
          'programa' => isset($nom_programa_plan)?$nom_programa_plan:"", 
          'proceso_nocontratado' => isset($field_proc_interna)?number_format($field_proc_interna):"",
          'proceso_contratado' => isset($field_proc_externo)?number_format($field_proc_externo):"", 
          'meta_sesiones' => $field_proc_interna + $field_proc_externo, //$field_meta_sesiones, 
          'invertido' => isset($field_recurso_invertido)?number_format($field_recurso_invertido):"",
          'porc_cumpl_metaproceso' => number_format($porc_cumpl_metaproceso_planaccion,2),
          'producto_nocontratado' => isset($field_prod_interno)?number_format($field_prod_interno):"",
          'producto_contratado' => isset($field_prod_externo)?number_format($field_prod_externo):"",
          'meta_producto' => $field_prod_interno + $field_prod_externo, //$field_numero_asistentes,
          'porc_cumpl_metaproducto' => number_format($proc_cumpl_metaproducto_planaccion,2),

        );

        $output2 = array(); 
        $output3 = array(); 

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
  
  $form['dep']['subprogramas'] = [
      '#options'=> $statistics->subprogramas($form_state->getValue('programas')),
  ];

  return $form['dep'];
}


function getBibliotecas($form, FormStateInterface $form_state) {

  $statistics = new EvEndpoint;
  $options = $statistics->bibliotecas_sistema($form_state->getValue('biblioteca'));  
  $form['biblioteca_2']['#options'] = $options;
  
  return $form['biblioteca_2'];
}

function getPlanes($form, FormStateInterface $form_state) {

    $statistics = new FuncionesController;
    $options = $statistics->planes($form_state->getValue('concesion'));  
    $form['planes']['#options'] = $options;
    
    return $form['planes'];
  }


}