<?php
/**
 * @file
 * Contains \Drupal\alertas\Form\AlertasBibliotecasForm.
 */
namespace Drupal\alertas\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\Core\Controller\ControllerBase;
//use Drupal\planaccion\Controller\MallaEndpoint;
use Drupal\reportes\Controller\FuncionesController;
use Drupal\biblored_module\Controller\EvEndpoint;
use Drupal\alertas\Controller\AlertasEndpoint;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;

use Drupal\Core\Url;
use Drupal\Core\Link;

use \Drupal\Core\State\StateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

class AlertasBibliotecasForm extends FormBase
{
	const SETTINGS = 'planaccion.settings';
	
/**
* {@inheritdoc}
*/
public function getFormId() {
	return 'alertas_alertasbibliotecasform'; //nombremodule_nombreformulario
}

public function buildForm(array $form, FormStateInterface $form_state) {
	global $base_url;
	$config = $this->config(static::SETTINGS);
	$predeterminados = new EvEndpoint;
	$base_url_parts = parse_url($base_url); 
	$host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
	$planactual = $host."/json/planactual";
	$output_planactual = $predeterminados->serviciojson($planactual);
	$tid_planactual = $output_planactual[0]['field_concesion'];

	$vid_concesion = 'concesion';
	$terms_plan =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_concesion, $parent = 0, $max_depth = 1, $load_entities = FALSE);
	$concesion[''] = "Seleccionar una Concesión";
	$output  = array();
	foreach ($terms_plan as $key => $term) {
	    $concesion[$term->tid] = $term->name;
	}

	// SOLO LINEAS
	$vid_linea = 'areas';
    // Obtener solo los tid del nivel 1  
  	$terms_lineas =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_linea, $parent = 0, $max_depth = 1, $load_entities = FALSE);
	$lineas['All'] = "Todas";
	foreach($terms_lineas as $linea) {
		$term_data_linea[] = array(
			"name" => $linea->name,
			"id" => $linea->tid,
		);        
		$lineas[$linea->tid] = $linea->name; 
	}
	
	$form['filtro']['concesion'] = array(
       '#type' => 'select',
       '#title' => t('Contrato / Plan de acción:'),
       //'#validated' => TRUE,
       '#options' => $concesion,
       '#ajax' => [
          'callback' => '::getPlanes',
          'wrapper' => 'planes-wrapper',
          'method' => 'replace',
          'effect' => 'fade',
        ],
        '#attributes' => array(
                    	'style'=>'',
                      'class' => array('col-md-6'),
                    ),
        '#prefix' => '<div class="col-md-6" id="planes-wrapper01">',
        '#suffix' => '</div>',
        '#validated' => 'true',
      );
	$form['filtro']['planes'] = [
	    '#type' => 'select',
	    '#title' => t(''),
	    '#required'=> TRUE,
	    '#validated' => TRUE,
      '#options' => $this->getOptionsPlanes($form_state),
      '#default_value' => isset($form_state->getValues()['planes'])?$form_state->getValues()['planes']:"",
            //'#options' => $options2,//array($tid_planactual => 'Plan actual'),
	    '#prefix' => '<div class="col-md-6" id="planes-wrapper">',
	    '#suffix' => '</div>',
	    '#attributes' => array(
                    	'style'=>'',
                      'class' => array('col-md-6'),
                    ),
	];

	$form['filtro']['linea'] = array(
		'#type' => 'select',
		'#title' => 'Línea',
		'#description' => 'Líneas Misionales',
		'#options' => $lineas,
		'#required'=> TRUE,
	   ); 
	$rango_avance = [
		''	=> "Cualquiera",
		'50' => 'Bajo', 
		'70' => "Medio", 
		'71' => "Alto",
	];
	
/*
	$form['filtro']['avance_proceso'] = [
	    '#type' => 'select',
	    '#title' => t('Alerta Proceso'),
      	'#options' => $rango_avance,
	    '#prefix' => '<div class="col-md-6" id="planes-wrapper">',
	    '#suffix' => '</div>',
	    '#attributes' => array(
                    	'style'=>'',
                      'class' => array('col-md-6'),
                    ),
	];

	$form['filtro']['avance_producto'] = [
	    '#type' => 'select',
	    '#title' => t('Alerta Producto'),
      	'#options' => $rango_avance,
	    '#prefix' => '<div class="col-md-6" id="planes-wrapper">',
	    '#suffix' => '</div>',
	    '#attributes' => array(
                    	'style'=>'',
                      'class' => array('col-md-6'),
                    ),
	];
    */
	$form['filtro']['actions1'] = [
        '#type' => 'actions',
        'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Buscar'),
        ],
        '#prefix' => '<div class="btn-suprogramas">',
        '#suffix' => '</div>',
    ];
  	$input = $form_state->getUserInput();
  	
  	if (isset($input['op']) && $input['op'] === 'Buscar') {
	  	$params = array(
	      'plan' => $form_state->getValue('planes'),
	    );
    	$planes = $input['planes'];
		$linea = $input['linea'];
		$avance_proc = $input['avance_proceso'];
		$avance_prod = $input['avance_producto'];
		
	  	$output = $this->getAlertas($planes, $linea, $avance_proc, $avance_prod);
	  	
	  	$header  = array(
	        'programa' => "Programa",
        	'tipo' => 'Tipo',
			'meta_proceso' => "Meta Proceso",
        	'meta_parcial_proc' => "Meta Parcial Proceso (al mes actual)",
        	'ind_proceso' => "Indicador Proceso",
			'meta_producto' => "Meta Producto",
        	'meta_parcial_prod' => "Meta Parcial Producto (al mes actual)",
        	'ind_producto' => "Indicador Producto",
			'cant_sesiones' => "Sesiones",
	        'cant_asistentes' => "Asistentes",
			'porc_proceso' => "% Proceso",
			'porc_producto' => "% Producto",
	    );
	    $form['table'] = [ 
			'#type' => 'tableselect',
			'#header' => $header,
			'#options' => $output,
			'#multiple' => TRUE,
			'#prefix' => '<div id="programas-wrapper-subp">',
			'#suffix' => '</div>',
			'#sticky' => TRUE,
			'#attributes' => array(
			'style'=>'margin-left: 0!important;',
			),
	    ];
	  $form['exportar'] = [
          '#type' => 'processed_text',
          '#text' => "<a id='exportarexcel' href='javascript:;'>Exportar Excel</a>",
          '#format' => 'full_html',
      ];  
	}
  return $form;
}	

/**
   * Get options for second field.
   */
public function getOptionsPlanes(FormStateInterface $form_state) {

  $statistics = new FuncionesController;
  
  $options = $statistics->planes($form_state->getValue('concesion'));  

  return $options;
  
}

public function getAlertas($planes, $linea, $avance_proceso, $avance_producto) {

  $programs = new AlertasEndpoint;
  
  $options = $programs->get_programas_plan($planes, $linea, $avance_proceso, $avance_producto); 

  return $options;
  
}
public function submitForm(array &$form, FormStateInterface $form_state) {
  
	$statistics = new FuncionesController;
	
	$term = Term::load($form_state->getValue('planes'));
    $name = $term->getName();
    \Drupal::messenger()->addMessage($this->t('Valores: @planes',  [ '@planes' => $name,]));
    $form['planes']['options'] = array($form_state->getValue('planes'));
    $input = $form_state->getUserInput();  
    $form_state->setRebuild();
    $form_state->setStorage([]);   
  }
 
 function getPlanes($form, FormStateInterface $form_state) {
 
    return $form['filtro']['planes'];
  }
} //End class