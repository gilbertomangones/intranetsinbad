<?php
/**
 * @file
 * Contains \Drupal\alertas\Form\AlertasAvancesForm.
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


class AlertasAvancesForm extends FormBase {
	const SETTINGS = 'planaccion.settings';
	
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'alertas_alertasavancesform'; //nombremodule_nombreformulario
	}

	public function buildForm(array $form, FormStateInterface $form_state) {
		global $base_url;
		$config = $this->config(static::SETTINGS);
		$predeterminados = new EvEndpoint;
		$instance_alerts = new AlertasEndpoint;

		$base_url_parts = parse_url($base_url);
		$concesion = $instance_alerts->get_terms_vocabulary('concesion'); // Get array of terms Concesiones
		$lineas = $instance_alerts->get_terms_vocabulary('areas'); // Get array of terms lineas
    	
		$output = "";
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
	                      'class' => array(''),
	                    ),
	        '#prefix' => '<div class="" id="planes-wrapper01">',
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
	    '#prefix' => '<div class="" id="planes-wrapper">',
	    '#suffix' => '</div>',
	    '#attributes' => array(
                    	'style'=>'',
                      'class' => array(''),
                    ),
	];

		$form['filtro']['linea'] = array(
			'#type' => 'select',
			'#title' => 'Línea',
			'#options' => $lineas,
			'#required'=> TRUE,
		   ); 
		$rango_avance = [
			''	=> "Cualquiera",
			'50' => 'Bajo', 
			'70' => "Medio", 
			'71' => "Alto",
		];
		$form['filtro']['avance_proceso'] = [
		    '#type' => 'select',
		    '#title' => t('Alerta Proceso'),
	      	'#options' => $rango_avance,
		    '#prefix' => '<div class="" id="planes-wrapper">',
		    '#suffix' => '</div>',
		    '#attributes' => array(
	                    	'style'=>'',
	                      'class' => array('c'),
	                    ),
		];
		$form['filtro']['avance_producto'] = [
		    '#type' => 'select',
		    '#title' => t('Alerta Producto'),
	      	'#options' => $rango_avance,
		    '#prefix' => '<div class="" id="planes-wrapper">',
		    '#suffix' => '</div>',
		    '#attributes' => array(
	                    	'style'=>'',
	                      'class' => array(''),
	                    ),
		];
		$form['filtro']['actions1'] = [
	        '#type' => 'actions',
	        'submit' => [
	            '#type' => 'submit',
	            '#value' => $this->t('Buscar'),
	        ],
	        '#prefix' => '<div class="btn-suprogramas">',
	        '#suffix' => '</div>',
	    ];
    	
    	$form['exportar'] = [
      		'#type' => 'processed_text',
      		'#text' => "<a id='exportarexcel' href='javascript:;'>Exportar Excel</a>",
      		'#format' => 'full_html',
    	];
	    // Getting values
	    $input = $form_state->getUserInput();
    	if (!empty($input)) {
    		$planes = $input['planes'];
			$linea = $input['linea'];
			$avance_proc = $input['avance_proceso'];
			$avance_prod = $input['avance_producto'];
        }

	    
  		

  		if (isset($input['op']) && $input['op'] === 'Buscar') {
        	
        	$output = $this->getAlertasAvances($planes, $linea, $avance_proc, $avance_prod);
        	
  			$header  = array(
	        'programa' => "Linea",
            'programa_accion' => "Acción",
            'codigo_accion' => "Código acción",
        	'tipo' => 'Tipo',
			'meta_proceso' => "Meta Proceso 1",
        	'ind_proceso' => "Indicador Proceso 1",
			'meta_producto' => "Meta Producto 1",
        	'ind_producto' => "Indicador Producto 1",
        	'meta_impacto' => "Meta Proceso 2",
        	'ind_impacto' => "Indicador Proceso 2",
            'meta_producto_2' => "Meta Producto 2",
        	'ind_producto_2' => "Indicador Producto 2",
			//***
            'cant_sesiones' => "Avance Proceso 1", // proceso 1 Sesiones
	        'cant_asistentes' => "Avance Producto 1", // producto 1 Asistentes
            'calculo_avance_proceso_2' => "Avance Proceso 2",
            'calculo_avance_producto_2' => "Avance Producto 2",
			'porc_proceso' => "% Avance Proceso 1",
			'porc_producto' => "% Avance Producto 1",
            'porc_proceso_2' => "% Avance Proceso 2",
			'porc_producto_2' => "% Avance Producto 2",
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
  		}
	    
	    return $form;
	}
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$statistics = new FuncionesController;
		$input = $form_state->getUserInput();  
	    $form_state->setRebuild();
	    $form_state->setStorage([]);   
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

	public function getAlertasAvances($planes, $linea, $avance_proceso, $avance_producto) {
	  $programs = new AlertasEndpoint;
	  $options = $programs->get_programas_plan($planes, $linea, $avance_proceso, $avance_producto);
	  return $options;
	}
}
?>