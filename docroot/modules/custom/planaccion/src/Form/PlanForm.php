<?php
/**
* @file
* Contains Drupal\planform\form\PlanForm
*/
namespace Drupal\planaccion\Form;
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

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;

use Drupal\Core\Url;
use Drupal\Core\Link;

use \Drupal\Core\State\StateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
/**
* 
*/
class PlanForm extends FormBase
{
const SETTINGS = 'planaccion.settings';

/**
* {@inheritdoc}
*/
public function getFormId() {
return 'planaccion_planform'; //nombremodule_nombreformulario
}
/** 
 * {@inheritdoc}
 */
protected function getEditableConfigNames() {
  return [
    static::SETTINGS,
  ];
}
/**
* {@inheritdoc}
*/

public function buildForm(array $form, FormStateInterface $form_state) {
global $base_url;
$config = $this->config(static::SETTINGS);
$predeterminados = new EvEndpoint;
$base_url_parts = parse_url($base_url); 

$host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];

// ** $planactual = $host."/json/planactual";
$planactual = "https://intranet.biblored.net/sinbad/json/planactual";
$output_planactual = $predeterminados->serviciojson($planactual);
$tid_planactual = $output_planactual[0]['field_concesion'];

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
$termino = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($linea->tid);
  $estado = $termino->field_suprimir_activar->value;
  //var_dump($estado);
  
  if ($estado == 0) {  
    $term_data_linea[] = array(
    "name" => $linea->name,
    //'tid_linea_agenda' => $tid_linea_agenda,
    "id" => $linea->tid,
  );        
  $lineas[$linea->tid] = $linea->name; 
  }
}

$year[] = "Ninguno";
  for ($i=2018; $i <= date('Y', strtotime('+1 year')); $i++) { 
    $year[$i] = $i;
}
$form['filtro'] = array(
'#type' => 'details',
'#title' => t('Filtro inicial'),
'#description' => t('Filtrar por los campos a ingresar.'),
'#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
);
$form['filtro']['year'] = array (
   '#type' => 'select',
   '#title' => ('Año:'),
   '#options' => $year,
   '#default_value' => date('Y'),
);

$form['filtro']['concesion'] = array(
     '#type' => 'select',
     '#title' => 'Concesión',
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
    '#title' => t('Plan'),
    //'#required'=> TRUE,
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
//$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = 5, $max_depth = 1, $load_entities = FALSE);

$form['filtro']['linea'] = array(
   '#type' => 'select',
   '#title' => 'Línea',
   '#description' => 'Línea Misional',
   '#options' => $lineas,
   '#attributes' => array(
                    'style'=>'',
                    'class' => array(''),
                  ),
    //'#empty_option' => $this->t('Bibliotecas'),
   '#ajax' => [
      'callback' => [$this, 'getProgramas'], //'::getProgramas',
      'wrapper' => 'programas-wrapper',
      'method' => 'replace',
      'effect' => 'fade',
    ],
    '#prefix' => '<div class="mallalinea">',
    '#suffix' => '</div>',
  ); 
$form['filtro']['programas'] = [
    '#type' => 'select',
    '#title' => t('Estrategia'),
    '#validated' => TRUE,
    '#attributes' => array(
                    'style'=>'',
                    'class' => array(''),
                  ),
    '#options' => $this->getOptions($form_state),
    '#prefix' => '<div class="" id="programas-wrapper">',
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
$form['filtro']['actions1'] = [
      '#type' => 'actions',
      'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Programas'),
      ],
      '#prefix' => '<div class="btn-suprogramas">',
      '#suffix' => '</div>',
  ];

$input = $form_state->getUserInput();  

if (isset($input['op']) && $input['op'] === 'Programas') {

  $statistics = new FuncionesController;
  $output2 = $statistics->subprogramastable($form_state->getValue('programas'));  
  
  $params = array(
    'plan' => $form_state->getValue('planes'),
    'annio' => $form_state->getValue('year'), 
  );

  $output3 = $this->campos($output2, $params);
  
  $header2  = array(
      'id'  => [
            'data' => 'id',
            'rowspan' => '2',
            'class' => array('Ident'),
            'title' => 'ID',
            ],
    'progaccion' => 'ID Prog Acción',
      'nombre' => "PROGRAMA",
  );

  $header3 = $this->encabezadomeses();
  $headertotal = array_merge($header2, $header3);

 $form['table'] = [ 
      '#type' => 'tableselect',
      '#header' => $headertotal,
      '#options' => $output3,
      '#empty' => t('Sin contenido.'),
      //'#default_value' => variable_get('table'),
      '#multiple' => TRUE,
      '#prefix' => '<div id="programas-wrapper-subp">',
      '#suffix' => '</div>',
      '#sticky' => TRUE,
      '#attributes' => array(
          'style'=>'margin-left: 0!important;',
        ),
  ];

}


  $form['actions2'] = [
      '#type' => 'actions',
      'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Guardar Plan de acción'),
          '#submit' => array([$this, 'saveplan']),
      ],
  ];

return $form;
}
/**
* {@inheritdoc}
*/
public function validateForm(array &$form, FormStateInterface $form_state) {
/*if  (empty($form_state->getUserInput()['table'])){
  $form_state->setErrorByName('', $this->t('Debe chequear cada c'));
 }*/
}
/**
* {@inheritdoc}
*/
public function submitForm(array &$form, FormStateInterface $form_state) {

$statistics = new FuncionesController;


 \Drupal::messenger()->addMessage(
           $this->t('Valores: /@planes /@linea',  
    [ '@planes' => $form_state->getValue('planes'),
    '@linea' => $form_state->getValue('linea'),
    ])
         );
  $form['planes']['options'] = array($form_state->getValue('planes'));
  $form_state->setRebuild();
  $form_state->setStorage([]);   
}

public function saveplan(array &$form, FormStateInterface $form_state) {

    //var_dump($form_state->getUserInput());
  
    $user_id = $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    
    $annio = $form_state->getValues()['year'];
    
    $plan = $form_state->getValues()['planes'];
    
    $programa = $form_state->getValues()['linea'];

    // Deacuerdo al plan de accion extraer las fechas inicio y fin y asignarselas
    
    
    // Recorrer solamente los ID de los subprogramas seleccionados (check)
    
    foreach ($form_state->getUserInput()['table'] as $key => $value) {
        
        
      $idSubprograma = $value;
      $sum = 0;
      ${"indicador_proceso_" .$value} = [];
      ${"indicador_producto_" .$value} = [];
      ${"indicador_producto_2" .$value} = [];
      ${"impacto_indicador" .$value} = [];
	  
      if ($form_state->getUserInput()['table'][$key] == NULL){
          //drupal_set_message("Plan de acción con el programa Id: $key NO fue guardado porque no fue checkeado o seleccionado.", 'error');
        }else{
      	 \Drupal::messenger()->addMessage(
           $this->t("Plan de acción con el programa Id: $key seleccionado (checkbox) fue guardado correctamente")
         );
        }

        // Crear variables de cada campo y de cada subprograma seleccionado
        ${"proc_mnc_" .$value} = isset($form_state->getUserInput()["proc_mnc_".$value]) ? floatval($form_state->getUserInput()["proc_mnc_".$value]) : 0;
        //echo $mes."_proc_mnc_".$idSubprograma ."=". ${$mes."_proc_mnc_" . $value} ."<br>";

        ${"proc_mc_" .$value} = isset($form_state->getUserInput()["proc_mc_".$value]) ? floatval($form_state->getUserInput()["proc_mc_".$value]) : 0;
        //echo $mes."_proc_mc_".$idSubprograma ."=". ${$mes."_proc_mc_" . $value} ."<br>";
        
        if (isset($form_state->getUserInput()["indicador_proceso_".$value])) {
          ${"indicador_proceso_" .$value} = $form_state->getUserInput()["indicador_proceso_".$value];
        }

        ${"prod_mnc_" .$value} = isset($form_state->getUserInput()["prod_mnc_".$value]) ? floatval($form_state->getUserInput()["prod_mnc_".$value]) : 0;
        //echo $mes."_prod_mnc_".$idSubprograma ."=". ${$mes."_prod_mnc_" . $value} ."<br>";

        ${"prod_mc_" .$value} = isset($form_state->getUserInput()["prod_mc_".$value]) ? floatval($form_state->getUserInput()["prod_mc_".$value]) : 0;
        //echo $mes."_prod_mc_".$idSubprograma ."=". ${$mes."_prod_mc_" . $value} ."<br>";
    
        if (isset($form_state->getUserInput()["indicador_producto_".$value])) {
          ${"indicador_producto_" .$value} = $form_state->getUserInput()["indicador_producto_".$value];
        }
    	// Impacto o proceso 2
    	${"impacto_meta_" .$value} = isset($form_state->getUserInput()["impacto_meta_".$value]) ? floatval($form_state->getUserInput()["impacto_meta_".$value]) : 0;
    
    	if (isset($form_state->getUserInput()["impacto_indicador_".$value])) {
          ${"impacto_indicador_" .$value} = $form_state->getUserInput()["impacto_indicador_".$value];
        }
    
    	//Meta producto 2    	
    	${"producto2_meta_" .$value} = isset($form_state->getUserInput()["producto2_meta_".$value]) ? floatval($form_state->getUserInput()["producto2_meta_".$value]) : 0;
    
    	if (isset($form_state->getUserInput()["producto2_indicador_".$value])) {
          ${"producto2_indicador_" .$value} = $form_state->getUserInput()["producto2_indicador_".$value];
        }
         ${"presupuesto_" .$value} = isset($form_state->getUserInput()["presupuesto_".$value]) ? $form_state->getUserInput()["presupuesto_".$value] : 0;
    /*
           ${"enero_".$value} = $form_state->getUserInput()["cumpl_enero_".$value]; 

           ${'febrero_'.$value} = $form_state->getUserInput()["cumpl_febrero_".$value]; 

           ${'marzo_'.$value} = $form_state->getUserInput()["cumpl_marzo_".$value]; 

           ${'abril_'.$value} = $form_state->getUserInput()["cumpl_abril_".$value]; 

           ${'mayo_'.$value} = $form_state->getUserInput()["cumpl_mayo_".$value]; 

           ${'junio_'.$value} = $form_state->getUserInput()["cumpl_junio_".$value]; 

           ${'julio_'.$value} = $form_state->getUserInput()["cumpl_julio_".$value]; 

           ${'agosto_'.$value} = $form_state->getUserInput()["cumpl_agosto_".$value]; 

           ${'septiembre_'.$value} = $form_state->getUserInput()["cumpl_septiembre_".$value]; 

           ${'octubre_'.$value} = $form_state->getUserInput()["cumpl_octubre_".$value]; 

           ${'noviembre_'.$value} = $form_state->getUserInput()["cumpl_noviembre_".$value]; 

           ${'diciembre_'.$value} = $form_state->getUserInput()["cumpl_diciembre_".$value]; 

           $sum = ${"enero_".$value} + ${'febrero_'.$value} + ${'marzo_'.$value} + ${'abril_'.$value} + ${'mayo_'.$value} + ${'junio_'.$value} + ${'julio_'.$value} + ${'agosto_'.$value} + ${'septiembre_'.$value} + ${'octubre_'.$value} + ${'noviembre_'.$value} + ${'diciembre_'.$value};
    */
        
          if ((!is_null(${"proc_mnc_" .$value})) || (!is_null(${"proc_mc_" .$value})) || (!is_null(${"prod_mnc_" .$value})) || (!is_null(${"prod_mc_" .$value}))) {

            //$dateTime = \DateTime::createFromFormat('Y-m-d',$annio.'-'.$mesnum.'-01');
            //$newDateString = $dateTime->format('Y-m-d');
            
            // Crear el arreglo con toda la informacion de nodo para luego grabar
            $array_saving = array(
                'type' => 'plan_de_accion_concesion',
                'title' => '',
                'langcode' => 'es',
                'uid' => array($user_id),
                'status' => 1,
                'field_concesion' => $plan,
                'field_linea' => array($idSubprograma),
                //'field_fecha_plan_operativo' => array($newDateString),
                'field_proc_interna'=> array(${"proc_mnc_" . $value}), //mpnc, No contratada (Valor de la meta proceso que se va a realizar sin contratar externo)  
                'field_proc_externo'=> array(${"proc_mc_" .$value}), //mpc, Contratado (Valor de la meta proceso que se va a realizar contratando externo) 
                'field_meta_sesiones'=> array(${"proc_mnc_" . $value} + ${"proc_mc_" .$value}), // Meta proceso
                'field_indicador_proceso' => (!empty(${"indicador_proceso_" .$value})) ? ${"indicador_proceso_" .$value} : array(),
                //'field_meta_extension_externa'=> array(${$mes."_prod_mnc_" .$value} + ${$mes."_prod_mc_" .$value}), // Extension proceso
                'field_prod_interno'=> array(${"prod_mnc_" .$value}), //mprodnc, No contratada (Valor de la meta producto que se va a realizar sin contratar externo) 
                'field_prod_externo'=> array(${"prod_mc_" .$value}), //mprodc, Contratado (Valor de la meta producto que se va a realizar contratando externo) 
                'field_indicador_producto' => (!empty(${"indicador_producto_" .$value})) ? ${"indicador_producto_" .$value} : array(),
            	'field_producto_2'=> array(${"producto2_meta_" . $value}), // Meta proceso
            	'field_indicador_producto_2' => (!empty(${"producto2_indicador_" .$value})) ? ${"producto2_indicador_" .$value} : array(),
                'field_numero_asistentes'=> array(${"prod_mnc_" .$value} + ${"prod_mc_" .$value}), 
            	'field_impacto_planeado' => array(${"impacto_meta_" .$value}), 
            	'field_indicador_impacto' => (!empty(${"impacto_indicador_" .$value})) ? ${"impacto_indicador_" .$value} : array(),
                //'field_meta_extension_producto'=> array(), // Extension producto
                'field_recurso_invertido'=> array(str_replace('.', '', ${"presupuesto_" .$value})),
               /*
                'field_porc_plan_enero' => array(${"enero_" .$value}),
                'field_porc_plan_febrero' => array(${"febrero_" .$value}),
                'field_porc_plan_marzo' => array(${"marzo_" .$value}),
                'field_porc_plan_abril' => array(${"abril_" .$value}),
                'field_porc_plan_mayo' => array(${"mayo_" .$value}),
                'field_porc_plan_junio' => array(${"junio_" .$value}),
                'field_porc_plan_julio' => array(${"julio_" .$value}),
                'field_porc_plan_agosto' => array(${"agosto_" .$value}),
                'field_porc_plan_sep' => array(${"septiembre_" .$value}),
                'field_porc_plan_octubre' => array(${"octubre_" .$value}),
                'field_porc_plan_nov' => array(${"noviembre_" .$value}),
                'field_porc_plan_dic' => array(${"diciembre_" .$value}),
                'field_total_porcentaje_ejecucion' => array($sum),
                */
            );
            
            $node = Node::create($array_saving);
            
            // Grabar contenido del arreglo en un nodo
            $node->save();
            
        
          }else{

          	\Drupal::messenger()->addMessage(
           $this->t("Plan de acción con el programa Id: $key NO fue guardado porque hay campos vacío en las metas de Proceso o Producto.", 'error')
         	);
          }
     
    } // Fin foreach
    /*
    foreach ($form_state->getUserInput() as $key => $value) {
        //drupal_set_message($key . ': ' . $value) ;  
    } 
    */
    // Datos básicos del filtro
    foreach ($form_state->getValues() as $key => $value) {
      
    	\Drupal::messenger()->addMessage(
           $this->t($key . ':values ' . $value)
         );
      }

   
  $form_state->setRebuild(TRUE); 
 
  
}

// Por cada mes si existen datos bloquear, sino normal.
public function campos($output2, $parameter ){

global $base_url;
$base_url_parts = parse_url($base_url); 
$host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
$planactual = $parameter['plan'];
$anniosel = $parameter['annio'];

$output3 = array();

foreach ($output2 as $key => $value) {
  
  //$uri =  "http://localhost/estadisticas-biblored/webservices/planaccionanual/2019/479/232/55";
  //$uri =  "http://localhost/estadisticas-biblored/webservices/planaccion/".año."/plan/linea/biblioteca";
  // ** $uri =  $host."/webservices/planaccion/".$planactual."/".$value['id'];
  $uri =  "https://intranet.biblored.net/sinbad/webservices/planaccion/".$planactual."/".$value['id'];
  
  $output_uri = "";
  
  $proc_mnc = "";
  $proc_mc = "";
  $prod_mnc ="";
  $prod_mc = "";
  $proc_mnc_value = "";
  $proc_mc_value = "";
  $prod_mnc_value = "";
  $prod_mc_value = "";
  $porc_enero = "";
  $porc_febrero = "";
  $porc_marzo = "";
  $porc_abril = "";
  $porc_mayo = "";
  $porc_junio = "";
  $porc_julio = "";
  $porc_agosto = "";
  $porc_septiembre = "";
  $porc_octubre = "";
  $porc_noviembre = "";
  $porc_diciembre = "";
  $presupuesto = "";
  

  // Convertir json     
  try {
      $response = \Drupal::httpClient()->get($uri, array('headers' => array('Accept' => 'text/plain')));
      $data = (string) $response->getBody();
      if (empty($data)) {
        return FALSE;
      }
    }
    catch (RequestException $e) {
      return FALSE;
    }

  $output_uri = Json::decode($data); 
  
  $hay_info_01 = 0;
  $proc_mnc_sw = 0;
  $proc_mc_sw = 0;
  $prod_mnc_sw = 0;
  $prod_mc_sw = 0;
  
  foreach ($output_uri as $key_uri => $valor_uri) {
      
      $presupuesto_sw = 0;
      $enero_sw = 0;
      $febrero_sw = 0;
      $marzo_sw = 0;
      $abril_sw = 0;
      $mayo_sw = 0;
      $junio_sw = 0;
      $julio_sw = 0;
      $agosto_sw = 0;
      $septiembre_sw = 0;
      $octubre_sw = 0;
      $noviembre_sw = 0;
      $diciembre_sw = 0;
      $impacto_meta_value = 0; // (*)

      if (!empty($valor_uri['field_proc_interna'])){
        $proc_mnc_sw = 1;
        $proc_mnc_value = $valor_uri['field_proc_interna'];
       
      }
      if (!empty($valor_uri['field_proc_externo'])){
        $proc_mc_sw = 1;
        $proc_mc_value = $valor_uri['field_proc_externo'];
        
      }
      if (!empty($valor_uri['field_prod_interno'])){
        $prod_mnc_sw = 1;
        $prod_mnc_value = $valor_uri['field_prod_interno'];
      }
      if (!empty($valor_uri['field_prod_externo'])){
        $prod_mc_sw = 1;
        $prod_mc_value = $valor_uri['field_prod_externo'];
      }
      if (!empty($valor_uri['field_recurso_invertido'])){
        $presupuesto_sw = 1;
        $presupuesto = $valor_uri['field_recurso_invertido'];
      }
      // (proceso)
      if (!empty($valor_uri['field_impacto_planeado'])){
        $impacto_meta_value = $valor_uri['field_impacto_planeado'];
      }
    // (producto)
    if (!empty($valor_uri['field_producto_2'])){
        $producto2_meta_value = $valor_uri['field_producto_2'];
      }
      

      $porc_enero = $valor_uri['field_porc_plan_enero'];
      $porc_febrero = $valor_uri['field_porc_plan_febrero'];
      $porc_marzo = $valor_uri['field_porc_plan_marzo'];
      $porc_abril = $valor_uri['field_porc_plan_abril'];
      $porc_mayo = $valor_uri['field_porc_plan_mayo'];
      $porc_junio = $valor_uri['field_porc_plan_junio'];
      $porc_julio = $valor_uri['field_porc_plan_julio'];
      $porc_agosto = $valor_uri['field_porc_plan_agosto'];
      $porc_septiembre = $valor_uri['field_porc_plan_sep'];
      $porc_octubre = $valor_uri['field_porc_plan_octubre'];
      $porc_noviembre = $valor_uri['field_porc_plan_nov'];
      $porc_diciembre = $valor_uri['field_porc_plan_dic'];
    //}
    }
  
  $proc_mnc = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
        ),
        '#id' => 'proc_mnc_'.$value['id'],
        '#name' => 'proc_mnc_'.$value['id'],
        '#value' => $proc_mnc_value,
      ),
    );

  
  $proc_mc = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
        ),
        '#id' => 'proc_mc_'.$value['id'],
        '#name' => 'proc_mc_'.$value['id'],
        '#value' => $proc_mc_value,
        //'#title' => 'MPC',
      ), 
    );
  
  $prod_mnc = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',

        ),
        '#id' => 'prod_mnc_'.$value['id'],
        '#name' => 'prod_mnc_'.$value['id'],
        '#value' => $prod_mnc_value,
      ), 
    );
  // Indicador proceso
  $proc_indicador = array(
    'data' => array(
      '#type' => 'select',
      '#attributes' => array(
        'style'=>'width: auto;',
      ),
      '#id' => 'indicador_proceso_'.$value['id'],
      '#name' => 'indicador_proceso_'.$value['id'],
      '#options' => array($value['ind_proceso'] => $value['ind_proceso_name']),
      '#default_value' => array($value['ind_proceso'] => $value['ind_proceso_name']),
    ), 
  );
  $prod_mc = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'prod_mc_'.$value['id'],
        '#name' => 'prod_mc_'.$value['id'],
        '#value' => $prod_mc_value,
      ), 
    );
  // Indicador producto
  $prod_indicador = array(
    'data' => array(
      '#type' => 'select',
      '#attributes' => array(
        'style'=>'width: auto;',
      ),
      '#id' => 'indicador_producto_'.$value['id'],
      '#name' => 'indicador_producto_'.$value['id'],
      '#options' => array($value['ind_producto'] => $value['ind_producto_name']),
      '#default_value' => array($value['ind_producto'] => $value['ind_producto_name']),
    ), 
  );
  // (*)
  $impacto_meta = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
        ),
        '#id' => 'impacto_meta_'.$value['id'],
        '#name' => 'impacto_meta_'.$value['id'],
        '#value' => isset($impacto_meta_value)? $impacto_meta_value : 0,
      ), 
    );
  // (*)
  $impacto_indicador = array(
    'data' => array(
      '#type' => 'select',
      '#attributes' => array(
        'style'=>'width: auto;',
      ),
      '#id' => 'impacto_indicador_'.$value['id'],
      '#name' => 'impacto_indicador_'.$value['id'],
      '#options' => array($value['ind_impacto'] => $value['ind_impacto_name']),
      '#default_value' => array($value['ind_impacto'] => $value['ind_impacto_name']),
    ), 
  );
$producto2_meta = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
        ),
        '#id' => 'producto2_meta_'.$value['id'],
        '#name' => 'producto2_meta_'.$value['id'],
        '#value' => isset($producto2_meta_value)? $producto2_meta_value : 0,
      ), 
    );
  // (*)
  $producto2_indicador = array(
    'data' => array(
      '#type' => 'select',
      '#attributes' => array(
        'style'=>'width: auto;',
      ),
      '#id' => 'producto2_indicador_'.$value['id'],
      '#name' => 'producto2_indicador_'.$value['id'],
      '#options' => array($value['ind_producto2'] => $value['ind_producto2_name']),
      '#default_value' => array($value['ind_producto2'] => $value['ind_producto2_name']),
    ), 
  );
  $presupuesto_data = array(
      'data' => array(
        '#type' => 'textfield',
        '#size' => 15,
        '#attributes' => array(
          'style'=>'width: 140px;',
          'type' => 'number',
          'class' => array('numero'),
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#attached' => array(
            'library' => array(
              'planaccion/planaccion',
            ),
          ),
        '#id' => 'presupuesto_'.$value['id'],
        '#name' => 'presupuesto_'.$value['id'],
        '#value' => $presupuesto,
      ), 
    );
    
  $cump_enero = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_enero_'.$value['id'],
        '#name' => 'cumpl_enero_'.$value['id'],
        '#value' => $porc_enero,
      ), 
    );
  $cump_febrero = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_febrero_'.$value['id'],
        '#name' => 'cumpl_febrero_'.$value['id'],
        '#value' => $porc_febrero,
      ), 
    );
  $cump_marzo = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_marzo_'.$value['id'],
        '#name' => 'cumpl_marzo_'.$value['id'],
        '#value' => $porc_marzo,
      ), 
    );
  $cump_abril = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_abril_'.$value['id'],
        '#name' => 'cumpl_abril_'.$value['id'],
        '#value' => $porc_abril,
      ), 
    );
   $cump_mayo = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_mayo_'.$value['id'],
        '#name' => 'cumpl_mayo_'.$value['id'],
        '#value' => $porc_mayo,
      ), 
    );
  $cump_junio = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_junio_'.$value['id'],
        '#name' => 'cumpl_junio_'.$value['id'],
        '#value' => $porc_junio,
      ), 
    );
  $cump_julio = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_julio_'.$value['id'],
        '#name' => 'cumpl_julio_'.$value['id'],
        '#value' => $porc_julio,
      ), 
    );
  $cump_agosto = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_agosto_'.$value['id'],
        '#name' => 'cumpl_agosto_'.$value['id'],
        '#value' => $porc_agosto,
      ), 
    );
  $cump_septiembre = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_septiembre_'.$value['id'],
        '#name' => 'cumpl_septiembre_'.$value['id'],
        '#value' => $porc_septiembre,
      ), 
    );
  $cump_octubre = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_octubre_'.$value['id'],
        '#name' => 'cumpl_octubre_'.$value['id'],
        '#value' => $porc_octubre,
      ), 
    );
  $cump_noviembre = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_noviembre_'.$value['id'],
        '#name' => 'cumpl_noviembre_'.$value['id'],
        '#value' => $porc_noviembre,
      ), 
    );
    
  $cump_diciembre = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cumpl_diciembre_'.$value['id'],
        '#name' => 'cumpl_diciembre_'.$value['id'],
        '#value' => $porc_diciembre,
      ), 
    ); 
  $cump = array(
      'data' => array(
        '#type' => 'number',
        '#size' => 10,
        '#attributes' => array(
          'style'=>'width: 80px;',
          'type' => 'number',
          //'disabled' => !empty($hay_info_02)? 'disabled':"",
        ),
        '#id' => 'cump_'.$value['id'],
        '#name' => 'cump_'.$value['id'],
        '#value' => "000",
      ), 
    ); 
  // Deshabiltar campos Verificar si los 2 primeros campos tienen datos en nodo
  if ($proc_mnc_sw == 1 || $proc_mc_sw == 1 || $prod_mnc_sw == 1 || $prod_mc_sw == 1){
    $proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');  
    $proc_mc['data']['#attributes'] = array('disabled' => 'disabled');  
    $prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
    $prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
    $presupuesto_data['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_enero['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_febrero['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_marzo['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_abril['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_mayo['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_junio['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_julio['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_agosto['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_septiembre['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_octubre['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_noviembre['data']['#attributes'] = array('disabled' => 'disabled');
    $cump_diciembre['data']['#attributes'] = array('disabled' => 'disabled');
  }

  $output3[$value['id']] = array(
    'id'=> $value['id'],
    'progaccion'=> $value['progaccion'],
    'nombre'=> $value['nombre'], 
    'proc_mnc' => $proc_mnc,    
    'proc_mc' => $proc_mc,
    'proc_indicador' => $proc_indicador,
    'prod_mnc' => $prod_mnc,
    'prod_mc' => $prod_mc,
    'prod_indicador' => $prod_indicador,
    'impacto_meta' => $impacto_meta,
    'impacto_indicador' => $impacto_indicador,
    'prod2' => $producto2_meta,
    'prod2_indicador' => $producto2_indicador,
    'presupuesto' => $presupuesto_data,
    'proceso' => array(
      'data' => array(
          '#type' => 'textfield',
          '#size' => 10,
          '#id' => 'totalproc_'.$value['id'],
          '#attributes' => [
            'class' => array('totalproc'),
            'readonly' => array('readonly'),
          ],
          '#name' => 'totalproc_'.$value['id'],
        ),
    ),
  'producto' => array(
      'data' => array(
          '#type' => 'textfield',
          '#size' => 10,
          '#id' => 'totalprod_'.$value['id'],
          '#attributes' => [
            'class' => array('totalprod'),
            'readonly' => array('readonly'),
          ],
          '#name' => 'totalprod_'.$value['id'],
        ),
      ),
    'cump' => array(
      'data' => array(
          '#type' => 'button',
          '#attributes' => [
            'onclick' => 'return false;',
            'class' => array('sumarplanesaccion'),
          ],
          '#attached' => array(
            'library' => array(
              'planaccion/planaccion',
            ),
          ),
          '#id' => 'sub_'.$value['id'],
          '#name' => 'sub_'.$value['id'],
          '#value' => 'Calcular',
        ),
    ),

    'cump_enero' => $cump_enero,
    'cump_febrero' => $cump_febrero,
    'cump_marzo' => $cump_marzo,
    'cump_abril' => $cump_abril,
    'cump_mayo' => $cump_mayo,
    'cump_junio' => $cump_junio,
    'cump_julio' => $cump_julio,
    'cump_agosto' => $cump_agosto,
    'cump_septiembre' => $cump_septiembre,
    'cump_octubre' => $cump_octubre,
    'cump_noviembre' => $cump_noviembre,
    'cump_diciembre' => $cump_diciembre,
    'calc_porc' => array(
      'data' => array(
          '#type' => 'textfield',
          '#size' => 10,
          '#id' => 'totalcumpl_'.$value['id'],
          '#attributes' => [
            'class' => array('totalcumpl'),
            'readonly' => array('readonly'),
          ],
          '#name' => 'totalcumpl_'.$value['id'],
        ),
    ),  
    'btn_cumpl' => array(
      'data' => array(
          '#type' => 'button',
          '#attributes' => [
            'onclick' => 'return false;',
            'class' => array('sumarcumplimiento'),
          ],
          '#attached' => array(
            'library' => array(
              'planaccion/planaccion',
            ),
          ),
          '#id' => 'cump_'.$value['id'],
          '#name' => 'cumpl_'.$value['id'],
          '#value' => 'Calcular % Cumplimiento',
        ),
    ),
  
);

}

return $output3;
}

/**
 * Get options for second field.
 */
public function getOptions(FormStateInterface $form_state) {

$linea_misional = $form_state->getValue('linea');

$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = $linea_misional, $max_depth = 1, $load_entities = FALSE);

$subprogramas = array();

  if ($form_state->getValue('linea') != "All") {
    foreach ($terms as $key => $value) {
      $termino = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($value->tid);
  
      $estado = $termino->field_suprimir_activar->value;
      if ($estado == 0){
      $subprogramas[$value->tid] = $value->name;
      }
    }
    $options = $subprogramas;
  }
  else {
    $options = [
      '' => 'Debe seleccionar una línea primero'
    ];
  }
return $options;
}
/**
 * Get options for second field.
 */
public function getOptionsPlanes(FormStateInterface $form_state) {

$statistics = new FuncionesController;

$options = $statistics->planes($form_state->getValue('concesion'));  

//$form['filtro']['planes']['#options'] = $options;

return $options;

}

/**
 * Get options for second field.
 */
public function getOptionsBibliotecas(FormStateInterface $form_state) {

$bib = new EvEndpoint;

$options = $bib->bibliotecas_sistema($form_state->getValue('biblioteca'));  

return $options;

}

function getPlanes($form, FormStateInterface $form_state) {

  //$statistics = new FuncionesController;
  //$options = $statistics->planes($form_state->getValue('concesion'));  
  //$form['filtro']['planes']['#options'] = $options;
  
  return $form['filtro']['planes'];
}

function getProgramas($form, FormStateInterface $form_state) {

$statistics = new FuncionesController;

//$options = $statistics->programas($form_state->getValue('linea'));
/*
$linea_misional = $form_state->getValue('linea');

if ($linea_misional) {

  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = 5, $max_depth = 1, $load_entities = FALSE);
}

if ($terms) {
  //$programas['All'] = $this->t('Todos');
  foreach ($terms as $key => $value) {
     $programas[$value->tid] = $value->name; 
  }

}else{

  $programas[''] = $this->t('No hay programas para esta linea');

}
*/
//$options = array('1'=> "Subprograma 1", '2'=>"Subprograma 2");

//$form['filtro']['programas']['#options'] = $options;

return $form['filtro']['programas'];
}



function encabezadomeses(){

  $array_tit = array();
  
      $output = array(
        'proc_mnc' => [
            'data' => 'META PROCESO NO CONTRATADO',
            'rowspan' => '2',
            'class' => array('mayusc'),
            'title' => t('META PROCESO NO CONTRATADO'),
            ],
        'proc_mc'  => [
            'data' => 'META PROCESO CONTRATADO',
            'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => 'META PROCESO CONTRATADO',
            ],
        'proc_indicador' => [
          'data' => 'INDICADOR PROCESO',
          'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => 'INDICADOR DE PROCESO',
        ],
        'impacto_meta' => [
          'data' => 'META PROCESO 2',
          'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => 'META PROCESO 2',
        ],
        'impacto_indicador' => [
          'data' => 'INDICADOR PROCESO 2',
          'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => 'INDICADOR DE PROCESO 2',
        ],
        'prod_mnc' => [
            'data' => 'META PRODUCTO NO CONTRATADO',
            'rowspan' => '2',
           'class' => array('mayusc'),
              'title' => 'META PRODUCTO NO CONTRATADO',
          ],
        'prod_mc'  => [
            'data' => 'META PRODUCTO CONTRATADO',
            'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => 'META PRODUCTO CONTRATADO',
        ],
        'prod_indicador' => [
          'data' => 'INDICADOR PRODUCTO',
          'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => 'INDICADOR DE PRODUCTO',
        ],
        'prod2'  => [
            'data' => 'META PRODUCTO 2',
            'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => 'META PRODUCTO 2',
        ],
        'prod2_indicador' => [
          'data' => 'INDICADOR PRODUCTO 2',
          'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => 'INDICADOR DE PRODUCTO 2',
        ],
        'presupuesto'  => [
            'data' => 'PRESUPUESTO ASIGNADO',
            'rowspan' => '2',
            'class' => array('mayusc'),
          'title' => 'PRESUPUESTO ASIGNADO',
          ],
        'proceso' => [
              'data' => 'TOTALES META PROCESO',
              'rowspan' => '2',
              'class' => array('mayusc'),
              'title' => 'TOTALES META PROCESO'
        ],
        'producto' => [
          'data' => 'TOTALES META PRODUCTO',
          'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => 'TOTALES META PRODUCTO'
        ],
        'cump' => [
          'data' => 'CALCULAR',
          'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => '***'
        ],
        'cump_enero'  => [
              'data' => '% CUMPL ENERO',
              'rowspan' => '2'],
      'cump_febrero'  => [
                    'data' => '% CUMPL FEB',
                    'rowspan' => '2'],
      'cump_marzo'  => [
                    'data' => '% CUMPL MARZO',
                    'rowspan' => '2'],
      'cump_abril'  => [
                    'data' => '% CUMPL ABRIL',
                    'rowspan' => '2'],
      'cump_mayo'  => [
                    'data' => '% CUMPL MAYO',
                    'rowspan' => '2'],
      'cump_junio'  => [
                    'data' => '% CUMPL JUNIO',
                    'rowspan' => '2'],
      'cump_julio'  => [
                    'data' => '% CUMPL JULIO',
                    'rowspan' => '2'],
      'cump_agosto'  => [
                    'data' => '% CUMPL AGOSTO',
                    'rowspan' => '2'],
      'cump_septiembre'  => [
                    'data' => '% CUMPL SEPTIEMBRE',
                    'rowspan' => '2'],
      'cump_octubre'  => [
                    'data' => '% CUMPL OCTUBRE',
                    'rowspan' => '2'],
      'cump_noviembre'  => [
                    'data' => '% CUMPL NOVIEMBRE',
                    'rowspan' => '2'],
      'cump_diciembre'  => [
                    'data' => '% CUMPL DICIEMBRE',
                    'rowspan' => '2'],

      'calc_porc' => [
          'data' => 'TOTAL % CUMPLIMIENTO',
          'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => '***'
        ],
      'btn_cumpl' => [
          'data' => 'TOTAL % CUMPLIMIENTO',
          'rowspan' => '2',
          'class' => array('mayusc'),
          'title' => '***'
        ],
        );
      $array_tit = $output;
      
    //}
    /*$output_sub = [
            'proceso' => [
              'data' => 'TOTALES META PROCESO',
              'rowspan' => '2',
              'class' => array('mayusc'),
              'title' => 'TOTALES META PROCESO'
            ],
            'producto' => [
              'data' => 'TOTALES META PRODUCTO',
              'rowspan' => '2',
              'class' => array('mayusc'),
              'title' => 'TOTALES META PRODUCTO'
            ],
          ];*/
      $array_tit = array_merge($array_tit);
return $array_tit;
}

}
?>