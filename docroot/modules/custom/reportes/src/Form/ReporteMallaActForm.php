<?php

/**
* @file
* Contains \Drupal\reportes\Form\ReporteMallaActForm.
*/
namespace Drupal\reportes\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\reportes\Controller\FuncionesController;
use Drupal\biblored_module\Controller\EvEndpoint;
use Symfony\Component\HttpFoundation\JsonResponse; // Para procesar json
use Drupal\Component\Serialization\Json;
/**
* Implements an reports module form.
*/
class ReporteMallaActForm extends FormBase {

/**
 * {@inheritdoc}
 */
public function getFormId() {
  return 'reportes_MallaActividadesform'; //nombremodule_nombreformulario  

}

/**
 * {@inheritdoc}
 */
public function buildForm(array $form, FormStateInterface $form_state) {
	$statistics = new FuncionesController;
	$bib = new EvEndpoint;
	

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

	

  $form['filtro']['linea'] = array(
     '#type' => 'select',
     '#title' => 'Línea',
     '#description' => 'Línea Misional',
     '#options' => $lineas,
     '#ajax' => [
        'callback' => [$this, 'getEstrategias'], //'::getProgramas',
        'wrapper' => 'programas-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
      //'#empty_option' => $this->t('Bibliotecas'),
      '#prefix' => '<div class="col-md-4 mallalinea">',
      '#suffix' => '</div>',
      '#required' => TRUE,
    ); 

    $form['filtro']['estrategias'] = [
      '#type' => 'select',
      '#title' => t('Estrategia'),
      '#validated' => TRUE,
      '#options' => $this->getOptions($form_state),
      '#prefix' => '<div class="col-md-4" id="programas-wrapper">',
      '#suffix' => '</div>',
      '#default_value' => isset($form_state->getValues()['estrategias'])?$form_state->getValues()['estrategias']:"",
      '#ajax' => [
        'callback' => '::getProgramas',
        'wrapper' => 'programas-wrapper1',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => array('message' => 'En proceso...', 'type' => 'throbber'),
      ],
    ];
    $form['filtro']['programas'] = [
      '#type' => 'select',
      '#title' => t('Programas'),
      '#validated' => TRUE,
      '#options' => $this->getOptionsProgramas($form_state),
      '#prefix' => '<div class="col-md-4" id="programas-wrapper1">',
      '#suffix' => '</div>',
      '#default_value' => isset($form_state->getValues()['programas'])?$form_state->getValues()['programas']:"",
      /*'#ajax' => [
        'callback' => '::getSubprogramas',
        'wrapper' => 'programas-wrapper1',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => array('message' => 'En proceso...', 'type' => 'throbber'),
      ],*/
    ];

    // BIBLIOTECAS CON SIGLAS
	
	$form['filtro']['biblioteca_1'] = array(
	     '#type' => 'select',
	     '#title' => $this->t('Espacios'),
	     '#description' => 'Espacio',
	     '#options' => $statistics->bibliotecas_sinbad(),
	     '#ajax' => [
	        'callback' => '::getBibliotecas',
	        'wrapper' => 'bibliotecas-wrapper-espacio',
	        'method' => 'replace',
	        'effect' => 'fade',
	      ],
	      '#prefix' => '<div class="col-md-6 mallaespacio">',
	      '#suffix' => '</div>',
        '#required' => TRUE,
	    );
	
	$form['filtro']['biblioteca_2'] = [
	      '#type' => 'select',
	      '#title' => $this->t('Biblioteca'),
	      '#options' => $bib->bibliotecas_sistema($form_state->getValue('biblioteca_1')),
	      '#validated' => TRUE,
	      '#default_value' => isset($form_state->getValues()['biblioteca_2'])?$form_state->getValues()['biblioteca_2']:"",
	      //'#empty_option' => $this->t('Bibliotecas'),
	      '#prefix' => '<div class="col-md-6" id="bibliotecas-wrapper-espacio">',
	      '#suffix' => '</div>',
	  ];
  $form['fechaini'] = array(
     '#type' => 'date',
     '#required' => TRUE,
     '#title' => $this
       ->t('Fecha inicio &nbsp;'),
  	 '#prefix' => '<div class="col-md-6">',
	 '#suffix' => '</div>',
    );

  $form['fechafin'] = array(
   '#type' => 'date',
   '#required' => TRUE,
   '#title' => $this
     ->t('Fecha fin &nbsp; &nbsp;&nbsp;&nbsp; &nbsp;'),
  '#prefix' => '<div class="col-md-6">',
  '#suffix' => '</div>',
  );
  
  $form['actions'] = [
    '#type' => 'actions',
    'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Enviar'),
    ],
  ];

  $values = $form_state->getValues();
  
  if (!empty($values)) {
      
      $resultados = $form_state->get('field_count');
    
      $form_state->set('field_count', $resultados);

      // RESULTADOS
    $i = 0;
    $tabla = array();
    $header = array();
    $header = [
        //'ID' => t("ID"),
        'biblioteca' => t('BIBLIOTECA'),
        'fecha' => "FECHA",
        'programa' => t('PROGRAMA'),
        'metaproceso' => t("META PROCESO"),
        'metaproducto' => t("META PRODUCTO"),
        'proceso'=> t('PROCESO'),
        'sum_asistentes' => t('PRODUCTO'),
        'porccumplproceso' => t("% CUMPLIMIENTO PROCESO"),
        'porccumplproducto' => t("% CUMPLIMIENTO PRODUCTO"),
      ];
    $sum_asistentes = 0;
   // var_dump($resultados['bibliotecas']);
    foreach ($resultados['bibliotecas'] as $key => $record) {
      //var_dump($record['programas']);
      //$sum_asistentes += $record['field_numero_asistentes']; 
      //$biblioteca = $record['field_biblioteca'];
      //$programa = $record['field_linea'];
      foreach ($record['programas'] as $key2 => $value2) {
        //$mykey = key($record['programas']);
        //var_dump($mykey);
        
        //$i++;
        $biblioteca = $value2['name_bib'];
        $programa = $value2['name_programa'];
        $sum_asistentes = $value2['num_asistentes'];
        $numprogs = $value2['numprogs'];
        $fecha = $value2['fecha'];
        $meta_proceso = $value2['proceso'];
        $meta_producto = $value2['producto'];
        $porc_proceso = $value2['porccumplproceso'];
        $porc_producto = $value2['porccumplproducto'];
        $tabla[] = [
           //'ID' => $record['nid'],
           'biblioteca' => $biblioteca,
           'fecha' => date("Y:m", strtotime($fecha)),
           'programa' => $programa,
           'metaproceso' => $meta_proceso, 
           'metaproducto' => $meta_producto,
           'proceso' => $numprogs,
           'sum_asistentes' => $sum_asistentes,
           'porccumplproceso' => $porc_proceso."%",
           'porccumplproducto' => $porc_producto."%",
         ]; 
      }
      
    }

    //var_dump($tabla);

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

    $base_url_parts = parse_url($base_url);

    $statistics = new FuncionesController;
    // Capturar valores de filtro
    $linea = $form_state->getValue('linea');
    
    $estrategia = $form_state->getValue('estrategias');

    $programa = $form_state->getValue('programas');
    
    $espacio =  $form_state->getValue('biblioteca_1');

    $biblioteca =  $form_state->getValue('biblioteca_2');

    $fechaini = $form_state->getValue('fechaini');
    
    $fechafin = $form_state->getValue('fechafin');

    $valor_linea = "";

    $bib = "";

    $output = "";
    
    $fecha_busqueda = "fecha%5Bmin%5D=".$fechaini."&fecha%5Bmax%5D=".$fechafin;

    // Validar profundidad de búsqueda

    if ($linea != "All" && $estrategia != "All" && $programa != "All") {
      $valor_linea = $programa;
    }elseif ($linea != "All" && $estrategia != "All" && $programa == "All") {
      $valor_linea = $estrategia;
    }elseif ($linea != "All" && $estrategia == "All") {
      $valor_linea = $linea;
    }elseif ($linea == "All") {
      $valor_linea = "All";
    }
    
    if ($espacio != "All" && $biblioteca == "All"){
      $bib = $biblioteca;
    }elseif ($espacio != "All" && $biblioteca != "All") {
      $bib = $biblioteca;
    }

    
    // Ejecutar servicio
    $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
    
   
    $endpoint = $host."/servicios/actividadesejecutadas?espacio=".$bib."&linea=".$valor_linea."&".$fecha_busqueda;
    

    $output = $statistics->entidades($endpoint);
    // Agrupar actividades por Biblioteca y por programa
    $actividades_agrupadas = array();
    $cod_bib = 0;
    $cod_prog = 0;
    foreach ($output as $key => $value) {
      
      $cod_bib =  $value['field_biblioteca_1'];
      $cod_prog = $value['field_linea_1'];


      $actividades_agrupadas['bibliotecas'][$value['field_biblioteca_1']]['programas'][$value['field_linea_1']][$key]['cod_biblioteca_1'] = $value['field_biblioteca_1'];
      $actividades_agrupadas['bibliotecas'][$value['field_biblioteca_1']]['programas'][$value['field_linea_1']][$key]['name_biblioteca']  = $value['field_biblioteca'];
      $actividades_agrupadas['bibliotecas'][$value['field_biblioteca_1']]['programas'][$value['field_linea_1']][$key]['cod_programa']     = $value['field_linea_1'];
      $actividades_agrupadas['bibliotecas'][$value['field_biblioteca_1']]['programas'][$value['field_linea_1']][$key]['name_programa']    = $value['field_linea'];
      $actividades_agrupadas['bibliotecas'][$value['field_biblioteca_1']]['programas'][$value['field_linea_1']][$key]['fecha']            = $value['field_fecha_realizada_act'];
      $actividades_agrupadas['bibliotecas'][$value['field_biblioteca_1']]['programas'][$value['field_linea_1']][$key]['num_asistentes']   = $value['field_numero_asistentes'];

       

    }
    //var_dump($actividades_agrupadas['bibliotecas']);

    $actividades_resumen = array();

    foreach ($actividades_agrupadas['bibliotecas'] as $key => $value) {
        
        //var_dump($value);
        $mykey_prog = "";
        $num_prog = 0;
        //var_dump($value['programas']);
        foreach ($value['programas'] as $key_prog => $value_prog) {
          
          $num_prog = count($value_prog);
          $num_asistentes = 0;
          foreach ($value_prog as $key2 => $value2) {
            //var_dump($value2);
            $num_asistentes+= $value2['num_asistentes'];
            $biblioteca = $value2['cod_biblioteca_1'];
            $name_biblioteca = $value2['name_biblioteca'];
            $codprograma = $value2['cod_programa'];
            $name_programa = $value2['name_programa'];
            $fecha_act = $value2['fecha'];
          }

          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['num_asistentes']  = $num_asistentes;
          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['bib'] = $biblioteca;
          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['name_bib'] = $name_biblioteca;
          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['codprograma'] = $codprograma;
          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['name_programa'] = $name_programa;
          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['fecha'] = $fecha_act;
          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['numprogs'] = $num_prog;

          // por cada registro tomar:
          // $biblioteca, fecha_act y codprograma
          // y hacer la respectiva consulta ws

          $f_inicio = date("Y-m-01", strtotime($fecha_act));  // Obtener año-mes
          $f_fin = date("Y-m-t", strtotime($fecha_act));  // Obtener año-mes

          $fecha_busqueda_malla = "fechamalla%5Bmin%5D=".$f_inicio."&fechamalla%5Bmax%5D=".$f_fin;
          $endpoint_malla = $host."/servicios/planmalla?espacio=".$biblioteca."&linea=".$codprograma."&".$fecha_busqueda_malla;
         echo "<div style='display:none'>". $endpoint_malla . "</div>";
          $output_malla = $statistics->entidades($endpoint_malla);
          $proceso = 1;
          $producto = 1;
          foreach ($output_malla as $key3 => $value3) {
            //var_dump($value3);
            $proceso =  $value3['field_proc_externo'] + $value3['field_proc_interna'];
            $producto =  $value3['field_prod_externo'] + $value3['field_prod_interno'];
            
          }
          // CALCULANDO % CUMPLIMEINTO DE CADA PROGRAMA
          $porc_cumpl_proceso = round(($num_prog/$proceso)*100,2);  
          $porc_cumpl_producto = round(($num_asistentes/$producto)*100,2);

          

          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['proceso'] = $proceso;
          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['producto'] = $producto;

          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['porccumplproceso'] = $porc_cumpl_proceso;
          $actividades['bibliotecas'][$biblioteca]['programas'][$codprograma]['porccumplproducto'] = $porc_cumpl_producto;
          

         

        }
        
        
    }

    $form_state->set('field_count',$actividades);
    
    $form_state->setRebuild(); // Esta es la clave
}

function getPlanes($form, FormStateInterface $form_state) {

    return $form['filtro']['planes'];
}

/**
   * Get options for second field.
   */
public function getOptionsPlanes(FormStateInterface $form_state) {

  $statistics = new FuncionesController;
  
  $options = $statistics->planes($form_state->getValue('concesion'));  
  
  return $options;
  
}

function getEstrategias($form, FormStateInterface $form_state) {

  return $form['filtro']['estrategias'];
}

function getProgramas($form, FormStateInterface $form_state) {

  return $form['filtro']['programas'];
}

public function getOptions(FormStateInterface $form_state) {

  $linea_misional = $form_state->getValue('linea');

  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = $linea_misional, $max_depth = 1, $load_entities = FALSE);

    $subprogramas = array();
    $subprogramas['All'] = 'Todos';
    if ($form_state->getValue('linea') != "All") {
      foreach ($terms as $key => $value) {
        $subprogramas[$value->tid] = $value->name;
      }
      $options = $subprogramas;
    }
    
    return $options;
  }

public function getOptionsProgramas(FormStateInterface $form_state) {

  $linea_misional = $form_state->getValue('estrategias');

  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = $linea_misional, $max_depth = 1, $load_entities = FALSE);

  $subprogramas = array();
  $subprogramas['All'] = 'Todos';
    if ($form_state->getValue('estrategia') != "All") {
      foreach ($terms as $key => $value) {
        $subprogramas[$value->tid] = $value->name;
      }
      $options = $subprogramas;
    }
    
  return $options;
  }

function getBibliotecas($form, FormStateInterface $form_state) {
	  return $form['filtro']['biblioteca_2']; 
}


}
