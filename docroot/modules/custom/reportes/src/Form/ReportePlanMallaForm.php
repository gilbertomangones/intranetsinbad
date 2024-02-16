<?php
/**
* @file
* Contains \Drupal\reportes\Form\ReportePlanMallaForm.
*/
namespace Drupal\reportes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\reportes\Controller\FuncionesController;

/**
* Implements an reports module form.
*/
class ReportePlanMallaForm extends FormBase {

/**
 * {@inheritdoc}
 */
public function getFormId() {
  return 'reportes_reporteplanMallaform'; //nombremodule_nombreformulario  
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
  	  '#required'=> TRUE,
      //'#options' =>  array(),
      '#empty_option' => $this->t('Programas'),
      '#prefix' => '<div id="programas-wrapper">',
      '#suffix' => '</div>',    
  ];
  
  
  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => $this->t('Buscar'),
    '#button_type' => 'primary',
  ];

  $values = $form_state->getValues();
  
  if (!empty($values)) {

    $resultados = $form_state->get('field_count');
    $form_state->set('field_count', $resultados);
    $header = [
        //'ID' => t("ID"),
        'programa' => t('PROGRAMA'),
        'proceso_nocontratado' => t('Proceso no contratado (Plan acción)'),
        'proceso_contratado' => t('Proceso contratado (Plan acción)'),
        'meta_proceso' => t('Meta Proceso plan acción'),
        'recurso_invertido' => t('Recurso planeado'),
        'porc1'  => t('% planeado proceso'),        
        'prod_nocontratado' => t('No contratado (Meta producto) '), 
        'prod_contratado' => t('Contratado (Meta producto)'),
        'meta_producto' => t('Meta producto plan acción'),  
        'porc2'  => t('% planeado producto'),
        'proceso_nocontratado_operativo' => t('Operativo - Proceso no contratado'),
        'proceso_contratado_operativo' => t('Operativo - Proceso contratado'),
        'meta_sesiones_operativo' => t('Operativo - Meta proceso'),
        'producto_nocontratado_operativo' => t('Operativo - Producto no contratado'),
        'producto_contratado_operativo' => t('Operativo - Producto contratado'),
        'meta_producto_operativo' => t('Operativo - Meta producto'),
      ];

     // RESULTADOS
    $i = 0;
    $tabla = [];
    foreach ($resultados as $key => $record) {
      if (isset($record[0]['programa'])) {
      $tabla[] = [
         //'ID' => $record[0]['nid'],
         'programa' => $record[0]['programa'],
         'proceso_nocontratado' => $record[0]['proceso_nocontratado'], 
         'proceso_contratado' => $record[0]['proceso_contratado'],
         'meta_proceso' => $record[0]['meta_sesiones'],
         'recurso_invertido' => $record[0]['invertido'],
         'porc1'  => number_format($record[0]['porc_cumpl_metaproceso'],2), 
         'prod_nocontratado' => $record[0]['producto_nocontratado']."%",
         'prod_contratado' => $record[0]['producto_contratado'],
         'meta_producto' => $record[0]['meta_producto'],
         'porc2' => number_format($record[0]['porc_cumpl_metaproducto'],2)."%",
         'proceso_nocontratado_operativo' => $record[0]['proceso_nocontratado_operativo'],
         'proceso_contratado_operativo' => $record[0]['proceso_contratado_operativo'],
         'meta_sesiones_operativo' => $record[0]['meta_sesiones_operativo'],
         'producto_nocontratado_operativo' => $record[0]['producto_nocontratado_operativo'],
         'producto_contratado_operativo' => $record[0]['producto_contratado_operativo'],
         'meta_producto_operativo' => $record[0]['meta_producto_operativo'],  
       ];
      }
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
  $output_planes_acciones = "";
 
  //drupal_set_message($this->t('Your phone number is @number:', ['@number' => $form_state->getValue('phone_number')]));

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
      
/*
      echo "programas".$programa."<br>";
      echo "linea".$linea."<br>";
      echo "Concesion".$concesion."<br>";
      echo "Plan".$plan."<br>";
      */
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
      
      /*print "linea".$linea;
      print "prog".$programa;
      print "valor".$valor_linea;*/
      
      // OBTENER TODOS LOS PLANES DE ACCIONES EN JSON
      $endpoint_planaccion = $host."/json/planaccion-vs-malla/".$valor_plan."/".$valor_linea;

      $output_planes_acciones = $statistics->entidades($endpoint_planaccion);
      
      /*
      foreach ($output as $value) {
        $entity_ids[] = $value['nid'];
      }
      */
      
      // PLANES ACCIÓN
      //$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
      
      foreach ($output_planes_acciones as $key => $value) {
        
        $nid_plan_accion = $value['nid'][0]['value'];
        $programa_plan = $value['field_linea'][0]['target_id'];
        $nom_programa_plan = $statistics->nombreTermino($programa_plan);
        
        $concesion = $value['field_concesion'][0]['target_id'];
        $nom_plan_concesion = $statistics->nombreTermino($concesion);
        // Meta proceso
        $field_proc_interna = isset($value['field_proc_interna'][0]) ? $value['field_proc_interna'][0]['value']:0; // nocontratada
        $field_proc_externo = isset($value['field_proc_externo'][0]) ? $value['field_proc_externo'][0]['value']:0; // contratada
        $field_recurso_invertido = $value['field_recurso_invertido'][0]['value']; //presupuesto
        $field_meta_sesiones_plan = $value['field_meta_sesiones'][0]['value']; // Meta sesiones
        
        // Meta producto
        $field_prod_interno = $value['field_prod_interno'][0]['value']; // No contratado
        $field_prod_externo = $value['field_prod_externo'][0]['value'];// Contratado
        
        $field_numero_asistentes_plan = $value['field_numero_asistentes'][0]['value']; // Meta asistentes

        // Meses
        $field_total_porcentaje_ejecucion = $value['field_total_porcentaje_ejecucion'][0]['value'];        
        // ************************* Fase 2 ********************************/
            // Buscar todos los planes operativos que contengan este programa(C/plan acción)
            $endpoint_operativo = $host."/json/planoperativo-cada-planaccion/".$programa_plan."/".$plan;

            //var_dump($endpoint_operativo);
            
            $output_operativo = $statistics->entidades($endpoint_operativo);

            //var_dump($output_operativo);
            /*
            foreach ($output_operativo as $value) {
              $entity_ids_operativos[] = $value['nid'];
            }
            */
            // PLANES OPERATIVOS
            //$nodes_operativos = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids_operativos);

            $sum_proc_nocontratado_operativo = 0;
            $sum_proc_contratado_operativo = 0;
            $sum_metasesiones = 0;
            $sum_prod_nocontratado_operativo = 0;
            $sum_prod_contratado_operativo = 0;
            $sum_asistentes = 0;
      		$porc_cumpl_metaproceso_planaccion = 0;
      		$proc_cumpl_metaproducto_planaccion = 0;
            //var_dump($output_operativo);
        
        
        foreach ($output_operativo as $key => $value_operativo) {
          
          $nid_operativo = $value_operativo['nid'][0]['value'];
          $subprograma = $value_operativo['field_linea'][0]['target_id'];
          
          // Proceso
          $proc_nocontratado_operativo =  $value_operativo['field_proc_interna'][0]['value'];
          
          $sum_proc_nocontratado_operativo = $sum_proc_nocontratado_operativo + $proc_nocontratado_operativo;

          $proc_contratado_operativo = $value_operativo['field_proc_externo'][0]['value'];
          $sum_proc_contratado_operativo = $sum_proc_contratado_operativo + $proc_contratado_operativo;

          $field_meta_sesiones = $proc_nocontratado_operativo + $proc_contratado_operativo;

          $sum_metasesiones = $sum_metasesiones + $field_meta_sesiones;
          

          // Producto
          $prod_nocontratado_operativo = $value_operativo['field_prod_interno'][0]['value'];
          
          $sum_prod_nocontratado_operativo = $sum_prod_nocontratado_operativo + $prod_nocontratado_operativo;

          $prod_contratado_operativo = $value_operativo['field_prod_externo'][0]['value'];
          
          $sum_prod_contratado_operativo = $sum_prod_contratado_operativo + $prod_contratado_operativo;

          $field_numero_asistentes = $prod_nocontratado_operativo + $prod_contratado_operativo;
          
          $sum_asistentes = $sum_asistentes + $field_numero_asistentes;
          
        }
        
		if ($sum_metasesiones != 0 ){
        	$porc_cumpl_metaproceso_planaccion = ($sum_metasesiones/$field_meta_sesiones_plan)*100;
        }
        if ($sum_asistentes != 0) {
        	$proc_cumpl_metaproducto_planaccion= ($sum_asistentes/$field_numero_asistentes_plan)*100;
        }
        
     
        
        $output1 = array(
          //'nid' => isset($nid_plan_accion)?$nid_plan_accion:"",
          'programa' => isset($nom_programa_plan)?$nom_programa_plan:"", 
          'proceso_nocontratado' => isset($field_proc_interna)?number_format($field_proc_interna, 2):"",
          'proceso_contratado' => isset($field_proc_externo)?$field_proc_externo:"", 
          'meta_sesiones' => $field_proc_interna + $field_proc_externo, //$field_meta_sesiones, 
          'invertido' => isset($field_recurso_invertido)?number_format($field_recurso_invertido):"",
          'porc_cumpl_metaproceso' => number_format($porc_cumpl_metaproceso_planaccion,2),
          'producto_nocontratado' => isset($field_prod_interno)?number_format($field_prod_interno):"",
          'producto_contratado' => isset($field_prod_externo)?number_format($field_prod_externo):"",
          'meta_producto' => $field_prod_interno + $field_prod_externo, //$field_numero_asistentes,
          'porc_cumpl_metaproducto' => number_format($proc_cumpl_metaproducto_planaccion,2),

          'proceso_nocontratado_operativo' => isset($sum_proc_nocontratado_operativo)?number_format($sum_proc_nocontratado_operativo):"",
          'proceso_contratado_operativo' => isset($sum_proc_contratado_operativo)?$sum_proc_contratado_operativo:"",
          'meta_sesiones_operativo' => isset($sum_metasesiones)?number_format($sum_metasesiones):"",
          'producto_nocontratado_operativo' => isset($sum_prod_nocontratado_operativo)?number_format($sum_prod_nocontratado_operativo):"",
          'producto_contratado_operativo' => isset($sum_prod_contratado_operativo)?number_format($sum_prod_contratado_operativo):"",
          'meta_producto_operativo' => isset($sum_asistentes)?number_format($sum_asistentes):"",
        );

        $output2 = array('numact' => "", 'porc_cumpl' => '%'); 
        $output3 = array('num_asistentes' => "", 'porc_cumpl_prod'=>'%'); 

        $union[] = array(array_merge($output1, $output2, $output3));
      }

      $form_state->set('field_count',$union);
      $form_state->setRebuild(); // Esta es la clave

}


function getPlanes($form, FormStateInterface $form_state) {

    $statistics = new FuncionesController;
    $options = $statistics->planes($form_state->getValue('concesion'));  
    $form['planes']['#options'] = $options;
    
    return $form['planes'];
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

}