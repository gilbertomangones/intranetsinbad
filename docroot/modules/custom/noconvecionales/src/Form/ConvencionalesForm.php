<?php
/**
* @file
* Contains Drupal\convencionalesform\form\ConvencionalesForm
*/
namespace Drupal\noconvencionales\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\Core\Controller\ControllerBase;
use Drupal\malla\Controller\Noconvencionales;
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
class ConvencionalesForm extends FormBase
{
  const SETTINGS = 'malla.settings';

/**
* {@inheritdoc}
*/
public function getFormId() {
	return 'malla_mallaform'; //nombremodule_nombreformulario
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
  $planactual = $host."/json/planactual";
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
    // Mostrar solo no convencionales
    if ($linea->tid == 182) {
  	  $term_data_linea[] = array(
  	    "name" => $linea->name,
  	    //'tid_linea_agenda' => $tid_linea_agenda,
  	    "id" => $linea->tid,
  	  );        
  	  $lineas[$linea->tid] = $linea->name; 
    }

	}

	// BIBLIOTECAS
  $vid = 'nodos_bibliotecas';
  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
  $bibliotecas['All'] = "Todos"; 
  foreach($terms as $term) {  
      if ($term->tid != 352){
        if ($term->depth == 0) { // 0 PARA EL PADRE
            
           // Array con todas las bibliotecas
               $term_data[] = array(
                   "id" => $term->tid,
                   "name" => $term->name,
               );
               $bibliotecas[$term->tid] = $term->name;
        }
      }
   }

 $year[] = "Ninguno";
    for ($i=2018; $i <= date('Y'); $i++) { 
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
                      'class' => array('col-md-6'),
                    ),
        '#prefix' => '<div class="col-md-6" id="planes-wrapper01">',
        '#suffix' => '</div>',
        '#validated' => 'true',
      );
	$form['filtro']['planes'] = [
	    '#type' => 'select',
	    '#title' => t('Planes'),
	    //'#required'=> TRUE,
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
  //$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = 5, $max_depth = 1, $load_entities = FALSE);
  
	$form['filtro']['linea'] = array(
     '#type' => 'select',
     '#title' => 'Línea',
     '#description' => 'Línea Misional',
     '#options' => $lineas,
     '#ajax' => [
        'callback' => [$this, 'getProgramas'], //'::getProgramas',
        'wrapper' => 'programas-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
      //'#empty_option' => $this->t('Bibliotecas'),
      '#prefix' => '<div class="col-md-6 mallalinea">',
      '#suffix' => '</div>',
    ); 

    $form['filtro']['programas'] = [
      '#type' => 'select',
      '#title' => t('Estrategia'),
      '#validated' => TRUE,
      '#options' => $this->getOptions($form_state),
      '#prefix' => '<div class="col-md-6" id="programas-wrapper">',
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
    
  
 
  $form['filtro']['biblioteca'] = array(
     '#type' => 'select',
     '#title' => $this->t('Espacios'),
     '#description' => 'Espacio',
     '#options' => $bibliotecas,
     '#ajax' => [
        'callback' => '::getBibliotecas',
        'wrapper' => 'bibliotecas-wrapper-espacio',
        'method' => 'replace',
        'effect' => 'fade',
      ],
      '#prefix' => '<div class="col-md-6 mallaespacio">',
      '#suffix' => '</div>',
    );

  $form['filtro']['biblioteca_2'] = [
      '#type' => 'select',
      '#title' => $this->t('Biblioteca'),
      //'#required' => FALSE,
      '#options' => $this->getOptionsBibliotecas($form_state),
      '#validated' => TRUE,
      '#default_value' => isset($form_state->getValues()['biblioteca_2'])?$form_state->getValues()['biblioteca_2']:"",
      //'#empty_option' => $this->t('Bibliotecas'),
      '#prefix' => '<div class="col-md-6" id="bibliotecas-wrapper-espacio">',
      '#suffix' => '</div>',
  ];
  $form['filtro']['actions1'] = [
        '#type' => 'actions',
        'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Buscar Programas'),
        ],
        '#prefix' => '<div class="btn-suprogramas">',
        '#suffix' => '</div>',
    ];
  
  $input = $form_state->getUserInput();  
  
  if (isset($input['op']) && $input['op'] === 'Buscar Programas') {

    $statistics = new FuncionesController;
    $output2 = $statistics->subprogramastable($form_state->getValue('programas'));  
    
    $params = array(
      'plan' => $form_state->getValue('planes'),
      'biblioteca' => $form_state->getValue('biblioteca_2'),
      'annio' => $form_state->getValue('year'), 
    );
    $output3 = $this->campos($output2, $params);
    //var_dump($output3);
    
    $header2  = array(
        'id'        => "ID",
        'nombre' => "Nombre",
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
  /*
  $form['subprogramasbtn'] = array(
        '#type' => 'button',
        '#value' => t('Guardar'),
        //'#prefix' => '<div id="user-email-result"></div>',
        '#ajax' => array(
          'callback' => '::changeOptionsAjax',
          'event' => 'click',
          'wrapper' => 'formprogramas',
          'progress' => array(
            'type' => 'throbber',
            'message' => 'Obtener subprogramas',
          ),
          'method' => 'replace',
        ),
      );
  */
  
    $form['actions2'] = [
        '#type' => 'actions',
        'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Guardar Malla de Programación'),
            '#submit' => array([$this, 'saveplan']),
        ],
    ];
  
  return $form;
}
/**
* {@inheritdoc}
*/
public function validateForm(array &$form, FormStateInterface $form_state) {
  /*if ($form_state->getValue('biblioteca_2') == 0) {
    $form_state->setErrorByName('biblioteca_2', $this->t('Seleccionaer una biblioteca'));
  }*/
}
/**
* {@inheritdoc}
*/
public function submitForm(array &$form, FormStateInterface $form_state) {
  
	$statistics = new FuncionesController;
	       \Drupal::messenger()->addMessage(
          $this->t('Valores: /@planes /@biblioteca /@programas',  
      [ '@planes' => $form_state->getValue('planes'),
      '@biblioteca' => $form_state->getValue('biblioteca'),
      '@programas' => $form_state->getValue('programas'),
      ])
         );
    $form['planes']['options'] = array($form_state->getValue('planes'));
    $form_state->setRebuild();
    $form_state->setStorage([]);   
  }

public function saveplan(array &$form, FormStateInterface $form_state) {

   
	
        \Drupal::messenger()->addMessage(
           $this->t("Plan Espacios no convencionales se ha gurdado correctamente")
         );
      
      $user_id = $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      
      $annio = $form_state->getValues()['year'];
      
      $espacio = $form_state->getValues()['biblioteca'];

      $biblioteca_2 = $form_state->getValues()['biblioteca_2']; //Biblioteca seleccionada en el filtro
      
      $plan = $form_state->getValues()['planes'];
      
      $programa = $form_state->getValues()['programas'];
      
      $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('nodos_bibliotecas', $parent = $espacio, $max_depth = 1, $load_entities = FALSE); 
      
      
      foreach($terms as $prog) {
      
        //var_dump($form_state->getUserInput()['table']);
        // Recorrer solamente los ID de los subprogramas seleccionados (check)
        foreach ($form_state->getUserInput()['table'] as $key => $value) {
          $idSubprograma = $value;
          // Get all ppp o Bibloestaciones

          for ($i=1; $i < 13; $i++) { 
             switch ($i) {
               case 1:
                 $mes = 'ene';
                 $mesnum = '01';
                 break;
               case 2:
                 $mes = 'feb';
                 $mesnum = '02';
                 break;
               case 3:
                 $mes = 'marz';
                 $mesnum = '03';
                 break;
               case 4:
                 $mes = 'abr';
                 $mesnum = '04';
                 break;
               case 5:
                 $mes = 'may';
                 $mesnum = '05';
                 break;
               case 6:
                 $mes = 'jun';
                 $mesnum = '06';
                 break;
               case 7:
                 $mes = 'jul';
                 $mesnum = '07';
                 break;
               case 8:
                 $mes = 'ago';
                 $mesnum = '08';
                 break;
               case 9:
                 $mes = 'sep';
                 $mesnum = '09';
                 break;
               case 10:
                 $mes = 'oct';
                 $mesnum = '10';
                 break;
               case 11:
                 $mes = 'nov';
                 $mesnum = '11';
                 break;
               case 12:
                 $mes = 'dic';
                 $mesnum = '12';
                 break;
             }
              // Crear variables de cada campo y de cada subprograma seleccionado
              ${$mes."_proc_mnc_" .$value} = $form_state->getUserInput()[$mes."_proc_mnc_".$value];
              //echo $mes."_proc_mnc_".$idSubprograma ."=". ${$mes."_proc_mnc_" . $value} ."<br>";

              ${$mes."_proc_mc_" .$value} = $form_state->getUserInput()[$mes."_proc_mc_".$value];
              //echo $mes."_proc_mc_".$idSubprograma ."=". ${$mes."_proc_mc_" . $value} ."<br>";

              ${$mes."_prod_mnc_" .$value} = $form_state->getUserInput()[$mes."_prod_mnc_".$value];
              //echo $mes."_prod_mnc_".$idSubprograma ."=". ${$mes."_prod_mnc_" . $value} ."<br>";

              ${$mes."_prod_mc_" .$value} = $form_state->getUserInput()[$mes."_prod_mc_".$value];
              //echo $mes."_prod_mc_".$idSubprograma ."=". ${$mes."_prod_mc_" . $value} ."<br>";

              if (!empty(${$mes."_proc_mnc_" .$value}) || !empty(${$mes."_proc_mc_" .$value})) {

                $dateTime = \DateTime::createFromFormat('Y-m-d',$annio.'-'.$mesnum.'-01');
                $newDateString = $dateTime->format('Y-m-d');
                
                // Crear el arreglo con toda la informacion de nodo para luego grabar
                $node = Node::create(array(
                    'type' => 'plan_operativo',
                    'title' => '',
                    'langcode' => 'es',
                    'uid' => array($user_id),
                    'status' => 1,
                    'field_biblioteca' => array($prog->tid),
                    'field_concesion' => $plan,
                    'field_linea' => $idSubprograma,
                    'field_fecha_plan_operativo' => array($newDateString),
                    'field_proc_interna'=> array(${$mes."_proc_mnc_" . $value}), //mpnc, No contratada (Valor de la meta proceso que se va a realizar sin contratar externo)  
                    'field_proc_externo'=> array(${$mes."_proc_mc_" .$value}), //mpc, Contratado (Valor de la meta proceso que se va a realizar contratando externo) 
                    'field_meta_sesiones'=> array(${$mes."_proc_mnc_" . $value} + ${$mes."_proc_mc_" .$value}), // Meta proceso
                    //'field_meta_extension_externa'=> array(${$mes."_prod_mnc_" .$value} + ${$mes."_prod_mc_" .$value}), // Extension proceso
                    'field_prod_interno'=> array(${$mes."_prod_mnc_" .$value}), //mprodnc, No contratada (Valor de la meta producto que se va a realizar sin contratar externo) 
                    'field_prod_externo'=> array(${$mes."_prod_mc_" .$value}), //mprodc, Contratado (Valor de la meta producto que se va a realizar contratando externo)  
                    'field_numero_asistentes'=> array(${$mes."_prod_mnc_" .$value} + ${$mes."_prod_mc_" .$value}), // Meta producto (Automático) 
                    //'field_meta_extension_producto'=> array(), // Extension producto
                    
                ));
                
                
                // Grabar contenido del arreglo en un nodo
                
                
                $node->save();
                
                /*$batch = array(
                    'title' => t('Deleting Node...'),
                    'operations' => array(
                      array(
                        '\Drupal\batch_example\DeleteNode::deleteNodeExample',
                        array($nids)
                      ),
                    ),
                    'finished' => '\Drupal\batch_example\DeleteNode::deleteNodeExampleFinishedCallback',
                  );
                  batch_set($batch);*/

              } // Fin if
          } // Fin meses
         
        } // Fin foreach
      
      } // Fin for c/ppp o bibloestaciones

    $form_state->setRebuild(TRUE); 
    
    
  }
// Por cada mes si existen datos bloquear, sino normal.
public function campos($output2, $parameter ){
  global $base_url;
  $base_url_parts = parse_url($base_url); 
  $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
  $planactual = $parameter['plan'];
  $anniosel = $parameter['annio'];
  $bibsel = $parameter['biblioteca'];
  $output3 = array();

  foreach ($output2 as $key => $value) {

    /// Preguntar si existen datos relacionados a:
    /* 
    mes y subprograma(id) dentro de este ciclo
  */
    /*
    $timezone = drupal_get_user_timezone();
    $date = new DrupalDateTime('2018-08-01T13:00:00');
    $date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    $startdate = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);

    $timezone = drupal_get_user_timezone();
    $date = new DrupalDateTime('2018-08-31T13:00:00');
    $date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    $enddate = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);

    echo "Fecha".$startdate;
    echo "Fecha 2".$enddate;

    $subprograma_actual = $value['id'];
    //consulta para enero
      $query = \Drupal::entityQuery('node')
        ->condition('status', NODE_PUBLISHED)
        ->condition('type', 'plan_operativo');
      $and = $query->andConditionGroup();
      $and->condition('field_concesion', 479);
      $query->condition($and);
      $and = $query->andConditionGroup();
      $and->condition('field_biblioteca', 48);
      $query->condition($and);
      $and = $query->andConditionGroup();
      $and->condition('field_linea', $subprograma_actual);
      $query->condition($and);
      $and = $query->andConditionGroup();
      $and->condition('field_fecha_plan_operativo', $startdate, '>=');
      $query->condition($and);
      $and = $query->andConditionGroup();
      $and->condition('field_fecha_plan_operativo', $enddate, '<=');
      $query->condition($and);
      $result = $query->execute();

      
      echo "<pre>";
      var_dump($result);
      echo "</pre>";

      Todo el contenido de un subprograma, de un año, plan, biblioteca
  */
    //$uri =  "http://localhost/estadisticas-biblored/webservices/planaccionanual/2019/479/232/55";
    //$uri =  "http://localhost/estadisticas-biblored/webservices/planaccionanual/".año."/plan/linea/biblioteca";
    //$uri =  "http://localhost/estadisticas-biblored/webservices/planaccionanual/".$anniosel."/".$planactual."/".$value['id']."/".$bibsel;
    $uri =  $host."/webservices/planaccionanual/".$anniosel."/".$planactual."/".$value['id']."/".$bibsel;

    //print $uri."<br>";
    $output_uri = "";
    
    $ene_proc_mnc = "";
    $ene_proc_mc = "";
    $ene_prod_mnc ="";
    $ene_prod_mc = "";

    $feb_proc_mnc = "";
    $feb_proc_mc = "";
    $feb_prod_mnc ="";
    $feb_prod_mc = "";

    $marz_proc_mnc = "";
    $marz_proc_mc = "";
    $marz_prod_mnc ="";
    $marz_prod_mc = "";

    $abr_proc_mnc = "";
    $abr_proc_mc = "";
    $abr_prod_mnc ="";
    $abr_prod_mc = "";

    $may_proc_mnc = "";
    $may_proc_mc = "";
    $may_prod_mnc ="";
    $may_prod_mc = "";

    $jun_proc_mnc = "";
    $jun_proc_mc = "";
    $jun_prod_mnc ="";
    $jun_prod_mc = "";

    $jul_proc_mnc = "";
    $jul_proc_mc = "";
    $jul_prod_mnc ="";
    $jul_prod_mc = "";

    $ago_proc_mnc = "";
    $ago_proc_mc = "";
    $ago_prod_mnc ="";
    $ago_prod_mc = "";

    $sep_proc_mnc = "";
    $sep_proc_mc = "";
    $sep_prod_mnc ="";
    $sep_prod_mc = "";

    $oct_proc_mnc = "";
    $oct_proc_mc = "";
    $oct_prod_mnc ="";
    $oct_prod_mc = "";

    $nov_proc_mnc = "";
    $nov_proc_mc = "";
    $nov_prod_mnc ="";
    $nov_prod_mc = "";

    $dic_proc_mnc = "";
    $dic_proc_mc = "";
    $dic_prod_mnc ="";
    $dic_prod_mc = "";
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
    //var_dump($output_uri);

    $hay_info_01 = 0;
    $hay_info_02 = 0;
    $hay_info_03 = 0;
    $hay_info_04 = 0;
    $hay_info_05 = 0;
    $hay_info_06 = 0;
    $hay_info_07 = 0;
    $hay_info_08 = 0;
    $hay_info_09 = 0;
    $hay_info_10 = 0;
    $hay_info_11 = 0;
    $hay_info_12 = 0;
    

    foreach ($output_uri as $key_uri => $valor_uri) {
      $fmes = date("m", strtotime($valor_uri['field_fecha_plan_operativo']));
      //print "mmm:".$fmes;
      if ($fmes == '01'){
        $hay_info_01 = 1;
        $ene_proc_mnc = $valor_uri['field_proc_interna'];
        $ene_proc_mc = $valor_uri['field_proc_externo'];
        $ene_prod_mnc = $valor_uri['field_prod_interno'];
        $ene_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '02'){
        $hay_info_02 = 1;
        $feb_proc_mnc = $valor_uri['field_proc_interna'];
        $feb_proc_mc = $valor_uri['field_proc_externo'];
        $feb_prod_mnc = $valor_uri['field_prod_interno'];
        $feb_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '03'){
        $hay_info_03 = 1;
        $marz_proc_mnc = $valor_uri['field_proc_interna'];
        $marz_proc_mc = $valor_uri['field_proc_externo'];
        $marz_prod_mnc = $valor_uri['field_prod_interno'];
        $marz_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '04'){
        $hay_info_04 = 1;
        $abr_proc_mnc = $valor_uri['field_proc_interna'];
        $abr_proc_mc = $valor_uri['field_proc_externo'];
        $abr_prod_mnc = $valor_uri['field_prod_interno'];
        $abr_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '05'){
        $hay_info_05 = 1;
        $may_proc_mnc = $valor_uri['field_proc_interna'];
        $may_proc_mc = $valor_uri['field_proc_externo'];
        $may_prod_mnc = $valor_uri['field_prod_interno'];
        $may_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '06'){
        $hay_info_06 = 1;
        $jun_proc_mnc = $valor_uri['field_proc_interna'];
        $jun_proc_mc = $valor_uri['field_proc_externo'];
        $jun_prod_mnc = $valor_uri['field_prod_interno'];
        $jun_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '07'){
        $hay_info_07 = 1;
        $jul_proc_mnc = $valor_uri['field_proc_interna'];
        $jul_proc_mc = $valor_uri['field_proc_externo'];
        $jul_prod_mnc = $valor_uri['field_prod_interno'];
        $jul_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '08'){
        $hay_info_08 = 1;
        $ago_proc_mnc = $valor_uri['field_proc_interna'];
        $ago_proc_mc = $valor_uri['field_proc_externo'];
        $ago_prod_mnc = $valor_uri['field_prod_interno'];
        $ago_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '09'){
        $hay_info_09 = 1;
        $sep_proc_mnc = $valor_uri['field_proc_interna'];
        $sep_proc_mc = $valor_uri['field_proc_externo'];
        $sep_prod_mnc = $valor_uri['field_prod_interno'];
        $sep_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '10'){
        $hay_info_10 = 1;
        $oct_proc_mnc = $valor_uri['field_proc_interna'];
        $oct_proc_mc = $valor_uri['field_proc_externo'];
        $oct_prod_mnc = $valor_uri['field_prod_interno'];
        $oct_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '11'){
        $hay_info_11 = 1;
        $nov_proc_mnc = $valor_uri['field_proc_interna'];
        $nov_proc_mc = $valor_uri['field_proc_externo'];
        $nov_prod_mnc = $valor_uri['field_prod_interno'];
        $nov_prod_mc = $valor_uri['field_prod_externo'];
      }
      if ($fmes == '12'){
        $hay_info_12 = 1;
        $dic_proc_mnc = $valor_uri['field_proc_interna'];
        $dic_proc_mc = $valor_uri['field_proc_externo'];
        $dic_prod_mnc = $valor_uri['field_prod_interno'];
        $dic_prod_mc = $valor_uri['field_prod_externo'];
      }
    }
    
    $enero_proc_mnc = array(
        'data' => array(
          '#type' => 'number',
          '#size' => 10,
          '#attributes' => array(
            'style'=>'width: 80px;',
          ),
          '#id' => 'ene_proc_mnc_'.$value['id'],
          '#name' => 'ene_proc_mnc_'.$value['id'],
          '#value' => $ene_proc_mnc,
        ),
      );
    $enero_proc_mc = array(
        'data' => array(
          '#type' => 'number',
          '#size' => 10,
          '#attributes' => array(
            'style'=>'width: 80px;',
          ),
          '#id' => 'ene_proc_mc_'.$value['id'],
          '#name' => 'ene_proc_mc_'.$value['id'],
          '#value' => $ene_proc_mc,
          //'#title' => 'MPC',
        ), 
      );
    $enero_prod_mnc = array(
        'data' => array(
          '#type' => 'number',
          '#size' => 10,
          '#attributes' => array(
            'style'=>'width: 80px;',
            'type' => 'number',

          ),
          '#id' => 'ene_prod_mnc_'.$value['id'],
          '#name' => 'ene_prod_mnc_'.$value['id'],
          '#value' => $ene_prod_mnc,
        ), 
      );
    $enero_prod_mc = array(
        'data' => array(
          '#type' => 'number',
          '#size' => 10,
          '#attributes' => array(
            'style'=>'width: 80px;',
            'type' => 'number',
            //'disabled' => !empty($hay_info_02)? 'disabled':"",
          ),
          '#id' => 'ene_prod_mc_'.$value['id'],
          '#name' => 'ene_prod_mc_'.$value['id'],
          '#value' => $ene_prod_mc,
        ), 
      );
      if ($hay_info_01 == 1){
        $enero_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $enero_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $enero_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $enero_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }
    // Febrero
      $febrero_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'feb_proc_mnc_'.$value['id'],
            '#name' => 'feb_proc_mnc_'.$value['id'],
            '#value' => $feb_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $febrero_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'feb_proc_mc_'.$value['id'],
            '#name' => 'feb_proc_mc_'.$value['id'],
            '#value' => $feb_proc_mc,
          ),
        );
        $febrero_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'feb_prod_mnc_'.$value['id'],
            '#name' => 'feb_prod_mnc_'.$value['id'],
            '#value' => $feb_prod_mnc,
          ),
        );
      $febrero_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'feb_prod_mc_'.$value['id'],
            '#name' => 'feb_prod_mc_'.$value['id'],
            '#value' => $feb_prod_mc,
          ),
        );
      if ($hay_info_02 == 1){
        $febrero_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $febrero_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $febrero_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $febrero_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }
    
    // Marzo
      $marzo_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'marz_proc_mnc_'.$value['id'],
            '#name' => 'marz_proc_mnc_'.$value['id'],
            '#value' => $marz_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $marzo_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'marz_proc_mc_'.$value['id'],
            '#name' => 'marz_proc_mc_'.$value['id'],
            '#value' => $marz_proc_mc,
          ),
        );
        $marzo_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'marz_prod_mnc_'.$value['id'],
            '#name' => 'marz_prod_mnc_'.$value['id'],
            '#value' => $marz_prod_mnc,
          ),
        );
      $marzo_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'marz_prod_mc_'.$value['id'],
            '#name' => 'marz_prod_mc_'.$value['id'],
            '#value' => $marz_prod_mc,
          ),
        );
      if ($hay_info_03 == 1){
        $marzo_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $marzo_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $marzo_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $marzo_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }

    // Abril
      $abril_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'abr_proc_mnc_'.$value['id'],
            '#name' => 'abr_proc_mnc_'.$value['id'],
            '#value' => $abr_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $abril_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'abr_proc_mc_'.$value['id'],
            '#name' => 'abr_proc_mc_'.$value['id'],
            '#value' => $abr_proc_mc,
          ),
        );

      $abril_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'abr_prod_mnc_'.$value['id'],
            '#name' => 'abr_prod_mnc_'.$value['id'],
            '#value' => $abr_prod_mnc,
          ),
        );
      $abril_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'abr_prod_mc_'.$value['id'],
            '#name' => 'abr_prod_mc_'.$value['id'],
            '#value' => $abr_prod_mc,
          ),
        );
      if ($hay_info_04 == 1){
        $abril_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $abril_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $abril_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $abril_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }

      // Mayo
      $mayo_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'may_proc_mnc_'.$value['id'],
            '#name' => 'may_proc_mnc_'.$value['id'],
            '#value' => $may_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $mayo_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'may_proc_mc_'.$value['id'],
            '#name' => 'may_proc_mc_'.$value['id'],
            '#value' => $may_proc_mc,
          ),
        );

      $mayo_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'may_prod_mnc_'.$value['id'],
            '#name' => 'may_prod_mnc_'.$value['id'],
            '#value' => $may_prod_mnc,
          ),
        );
      $mayo_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'may_prod_mc_'.$value['id'],
            '#name' => 'may_prod_mc_'.$value['id'],
            '#value' => $may_prod_mc,
          ),
        );
      if ($hay_info_05 == 1){
        $mayo_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $mayo_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $mayo_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $mayo_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }

      // Junio
      $junio_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'jun_proc_mnc_'.$value['id'],
            '#name' => 'jun_proc_mnc_'.$value['id'],
            '#value' => $jun_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $junio_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'jun_proc_mc_'.$value['id'],
            '#name' => 'jun_proc_mc_'.$value['id'],
            '#value' => $jun_proc_mc,
          ),
        );

      $junio_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'jun_prod_mnc_'.$value['id'],
            '#name' => 'jun_prod_mnc_'.$value['id'],
            '#value' => $jun_prod_mnc,
          ),
        );
      $junio_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'jun_prod_mc_'.$value['id'],
            '#name' => 'jun_prod_mc_'.$value['id'],
            '#value' => $jun_prod_mc,
          ),
        );
      if ($hay_info_06 == 1){
        $junio_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $junio_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $junio_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $junio_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }

      // Julio
      $julio_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'jul_proc_mnc_'.$value['id'],
            '#name' => 'jul_proc_mnc_'.$value['id'],
            '#value' => $jul_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $julio_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'jul_proc_mc_'.$value['id'],
            '#name' => 'jul_proc_mc_'.$value['id'],
            '#value' => $jul_proc_mc,
          ),
        );

      $julio_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'jul_prod_mnc_'.$value['id'],
            '#name' => 'jul_prod_mnc_'.$value['id'],
            '#value' => $jul_prod_mnc,
          ),
        );
      $julio_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'jul_prod_mc_'.$value['id'],
            '#name' => 'jul_prod_mc_'.$value['id'],
            '#value' => $jul_prod_mc,
          ),
        );
      if ($hay_info_07 == 1){
        $julio_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $julio_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $julio_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $julio_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }

      // Agosto
      $agosto_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'ago_proc_mnc_'.$value['id'],
            '#name' => 'ago_proc_mnc_'.$value['id'],
            '#value' => $ago_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $agosto_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'ago_proc_mc_'.$value['id'],
            '#name' => 'ago_proc_mc_'.$value['id'],
            '#value' => $ago_proc_mc,
          ),
        );

      $agosto_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'ago_prod_mnc_'.$value['id'],
            '#name' => 'ago_prod_mnc_'.$value['id'],
            '#value' => $ago_prod_mnc,
          ),
        );
      $agosto_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'ago_prod_mc_'.$value['id'],
            '#name' => 'ago_prod_mc_'.$value['id'],
            '#value' => $ago_prod_mc,
          ),
        );
      if ($hay_info_08 == 1){
        $agosto_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $agosto_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $agosto_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $agosto_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }

      // Septiembre
      $septiembre_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'sep_proc_mnc_'.$value['id'],
            '#name' => 'sep_proc_mnc_'.$value['id'],
            '#value' => $sep_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $septiembre_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'sep_proc_mc_'.$value['id'],
            '#name' => 'sep_proc_mc_'.$value['id'],
            '#value' => $sep_proc_mc,
          ),
        );

      $septiembre_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'sep_prod_mnc_'.$value['id'],
            '#name' => 'sep_prod_mnc_'.$value['id'],
            '#value' => $sep_prod_mnc,
          ),
        );
      $septiembre_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'sep_prod_mc_'.$value['id'],
            '#name' => 'sep_prod_mc_'.$value['id'],
            '#value' => $sep_prod_mc,
          ),
        );
      if ($hay_info_09 == 1){
        $septiembre_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $septiembre_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $septiembre_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $septiembre_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }

      // Octubre
      $octubre_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'oct_proc_mnc_'.$value['id'],
            '#name' => 'oct_proc_mnc_'.$value['id'],
            '#value' => $oct_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $octubre_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'oct_proc_mc_'.$value['id'],
            '#name' => 'oct_proc_mc_'.$value['id'],
            '#value' => $oct_proc_mc,
          ),
        );

      $octubre_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'oct_prod_mnc_'.$value['id'],
            '#name' => 'oct_prod_mnc_'.$value['id'],
            '#value' => $oct_prod_mnc,
          ),
        );
      $octubre_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'oct_prod_mc_'.$value['id'],
            '#name' => 'oct_prod_mc_'.$value['id'],
            '#value' => $oct_prod_mc,
          ),
        );
      if ($hay_info_10 == 1){
        $octubre_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $octubre_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $octubre_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $octubre_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }

      // Noviembre
      $noviembre_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'nov_proc_mnc_'.$value['id'],
            '#name' => 'nov_proc_mnc_'.$value['id'],
            '#value' => $nov_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $noviembre_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'nov_proc_mc_'.$value['id'],
            '#name' => 'nov_proc_mc_'.$value['id'],
            '#value' => $nov_proc_mc,
          ),
        );

      $noviembre_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'nov_prod_mnc_'.$value['id'],
            '#name' => 'nov_prod_mnc_'.$value['id'],
            '#value' => $nov_prod_mnc,
          ),
        );
      $noviembre_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'nov_prod_mc_'.$value['id'],
            '#name' => 'nov_prod_mc_'.$value['id'],
            '#value' => $nov_prod_mc,
          ),
        );
      if ($hay_info_11 == 1){
        $noviembre_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $noviembre_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $noviembre_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $noviembre_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }

      // Noviembre
      $diciembre_proc_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'dic_proc_mnc_'.$value['id'],
            '#name' => 'dic_proc_mnc_'.$value['id'],
            '#value' => $dic_proc_mnc,
            //'#title' => 'feb'
          ),
        );
    
      $diciembre_proc_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
            ),
            '#id' => 'dic_proc_mc_'.$value['id'],
            '#name' => 'dic_proc_mc_'.$value['id'],
            '#value' => $dic_proc_mc,
          ),
        );

      $diciembre_prod_mnc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'dic_prod_mnc_'.$value['id'],
            '#name' => 'dic_prod_mnc_'.$value['id'],
            '#value' => $dic_prod_mnc,
          ),
        );
      $diciembre_prod_mc = array(
        'data' => array(
            '#type' => 'number',
            '#size' => 10,
            '#attributes' => array(
              'style'=>'width: 80px;',
              'type' => 'number',
              
            ),
            '#id' => 'dic_prod_mc_'.$value['id'],
            '#name' => 'dic_prod_mc_'.$value['id'],
            '#value' => $dic_prod_mc,
          ),
        );
      if ($hay_info_12 == 1){
        $diciembre_proc_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $diciembre_proc_mc['data']['#attributes'] = array('disabled' => 'disabled');
        $diciembre_prod_mnc['data']['#attributes'] = array('disabled' => 'disabled');
        $diciembre_prod_mc['data']['#attributes'] = array('disabled' => 'disabled');
      }
      
    $output3[$value['id']] = array(
      'id'=> $value['id'], 
      'nombre'=> $value['nombre'], 
      'ene_proc_mnc' => $enero_proc_mnc,    
      'ene_proc_mc' => $enero_proc_mc,
      'ene_prod_mnc' => $enero_prod_mnc,
      'ene_prod_mc' => $enero_prod_mc,
      //****** FEB ******
      'feb_proc_mnc' => $febrero_proc_mnc,              
      'feb_proc_mc' => $febrero_proc_mc,
      'feb_prod_mnc' => $febrero_prod_mnc,
      'feb_prod_mc' => $febrero_prod_mc,
      //****** Marzo ******

      'marz_proc_mnc' => $marzo_proc_mnc,              
      'marz_proc_mc' => $marzo_proc_mc,
      'marz_prod_mnc' => $marzo_prod_mnc,
      'marz_prod_mc' => $marzo_prod_mc,
      //****** Abril ******

      'abril_proc_mnc' => $abril_proc_mnc,             
      'abril_proc_mc' => $abril_proc_mc,
      'abril_prod_mnc' => $abril_prod_mnc,
      'abril_prod_mc' => $abril_prod_mc,
      //****** MAYO ******

      'mayo_proc_mnc' => $mayo_proc_mnc,             
      'mayo_proc_mc' => $mayo_proc_mc,
      'mayo_prod_mnc' => $mayo_prod_mnc,
      'mayo_prod_mc' => $mayo_prod_mc,
      //****** junio ******

      'junio_proc_mnc' => $junio_proc_mnc,
      'junio_proc_mc' => $junio_proc_mc,
      'junio_prod_mnc' => $junio_prod_mnc,
      'junio_prod_mc' => $junio_prod_mc,

      //****** julio ******

      'julio_proc_mnc' => $julio_proc_mnc,           
      'julio_proc_mc' => $julio_proc_mc,
      'julio_prod_mnc' => $julio_prod_mnc,
      'julio_prod_mc' => $julio_prod_mc,

      //****** agosto ******

      'agosto_proc_mnc' => $agosto_proc_mnc,          
      'agosto_proc_mc' => $agosto_proc_mc,
      'agosto_prod_mnc' => $agosto_prod_mnc,
      'agosto_prod_mc' => $agosto_prod_mc,
      //****** sept ******

      'sept_proc_mnc' => $septiembre_proc_mnc,           
      'sept_proc_mc' => $septiembre_proc_mc,
      'sept_prod_mnc' => $septiembre_prod_mnc,
      'sept_prod_mc' => $septiembre_prod_mc,

      //****** oct ******

      'oct_proc_mnc' => $octubre_proc_mnc,            
      'oct_proc_mc' => $octubre_proc_mc,
      'oct_prod_mnc' => $octubre_prod_mnc,
      'oct_prod_mc' => $octubre_prod_mc,
      //****** nov ******

      'nov_proc_mnc' => $noviembre_proc_mnc,          
      'nov_proc_mc' => $noviembre_proc_mc,
      'nov_prod_mnc' => $noviembre_prod_mnc,
      'nov_prod_mc' => $noviembre_prod_mc,
      //****** Dic ******

      'dic_proc_mnc' => $diciembre_proc_mnc,            
      'dic_proc_mc' => $diciembre_proc_mc,
      'dic_prod_mnc' => $diciembre_prod_mnc,
      'dic_prod_mc' => $diciembre_prod_mc,

      'subtotal' => array(
        'data' => array(
            '#type' => 'button',
            '#attributes' => [
              'onclick' => 'return false;',
              'class' => array('sumar'),
            ],
            '#attached' => array(
              'library' => array(
                'malla/malla',
              ),
            ),
            '#id' => 'sub_'.$value['id'],
            '#name' => 'sub_'.$value['id'],
            '#value' => 'Calcular',
          ),
      ),
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
            '#attached' => array(
              'library' => array(
                'malla/malla',
              ),
            ),
            '#name' => 'totalprod_'.$value['id'],
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
        $subprogramas[$value->tid] = $value->name;
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

public function changeOptionsAjax(array &$form, FormStateInterface $form_state) {
     //$form['table']['#options'] = $this->subprogramas($form_state->getValue('programas'));
     //$form['table']['#options'] = $this->obtenerProgramasExistente($form_state);
     //$form['table']['#options'] = $output; //$this->obtenerProgramasExistente($form_state);
     $statistics = new FuncionesController;
     $output2 = $statistics->subprogramastable(463);
     //return $form['table'];
     
    $output3 = array(
      'demo',
    );
  $form['subprogramas']['#options'] = $output3;

  return $form['subprogramas'];
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

function getBibliotecas($form, FormStateInterface $form_state) {

  //$bib = new EvEndpoint;
  //$options = $bib->bibliotecas_sistema($form_state->getValue('biblioteca'));  
  //$form['filtro']['biblioteca_2']['#options'] = $options;
  
  return $form['filtro']['biblioteca_2']; 
}

function getSubprogramas(array &$form, FormStateInterface $form_state) {
  $statistics = new FuncionesController;
  $trigger = $form_state->getTriggeringElement();
  //$form['dep']['subprogramas']['#options'] = $statistics->subprogramas($form_state->getValue('programas'));
  //$listado = $statistics->subprogramastable($form_state->getValue('programas'));
  $programa = $form_state->getValue('programas');
  $output2 = $statistics->subprogramastable(463);
  
 
  $options1[0] = array(
    'id'=>'1', 
    'nombre'=>"dos"
  );
  $options2[1] = array(
    'id'=>'1', 
    'nombre'=>"tres"
  );  

  

  $header2  = array(
      'id'        => "IDent",
      'nombre' => "Name",
  );
  //$form['texto']['table']['#options'] = $output3;//
  
  $form['table']['#type'] = 'tableselect';

  $form['table']['#header'] = $header2;
  $form['table']['#options'] = array_merge($options1,$options2);
  
  $form['texto']['table']['#multiple'] = true;
  
  //$form['datos']['#value'] = $output3; 
  //$statistics->subprogramastable($form_state->getValue('programas'));
    
  return $form['texto'];
  
}
function encabezadomeses(){
  
    $array_tit = array();
    
      for ($i=1; $i < 13; $i++) { 
        switch ($i) {
          case 1:
            $mes = 'ene';
            $tit_mes = 'ENERO';
            break;
          case 2:
            $mes = 'feb';
            $tit_mes = 'FEBRERO';
            break;
          case 3:
            $mes = 'marz';
            $tit_mes = 'MARZO';
            break;
          case 4:
            $mes = 'abril';
            $tit_mes = 'ABRIL';
            break;
          case 5:
            $mes = 'mayo';
            $tit_mes = 'MAYO';
            break;
          case 6:
            $mes = 'junio';
            $tit_mes = 'JUNIO';
            break;
          case 7:
            $mes = 'julio';
            $tit_mes = 'JULIO';
            break;
          case 8:
            $mes = 'agosto';
            $tit_mes = 'AGOSTO';
            break;  
          case 9:
            $mes = 'sept';
            $tit_mes = 'SEPTIEMBRE';
            break;
          case 10:
            $mes = 'oct';
            $tit_mes = 'OCTUBRE';
            break;
          case 11:
            $mes = 'nov';
            $tit_mes = 'NOVIEMBRE';
            break;
          case 12:
            $mes = 'dic';
            $tit_mes = 'DICIEMBRE';
            break;
        }
        $output = array(
          $mes.'_proc_mnc' => [
              'data' => $tit_mes.' MPROCNC',
              'rowspan' => '2',
              'class' => array('mayusc'),
              'title' => $tit_mes,
              ],
          $mes.'_proc_mc'  => [
              'data' => $tit_mes.' MPROCC',
              'rowspan' => '2'],
          $mes.'_prod_mnc' => [
              'data' => $tit_mes.' MPRODNC',
              'rowspan' => '2'],
          $mes.'_prod_mc'  => [
              'data' => $tit_mes.' MPRODC',
              'rowspan' => '2'],
          );
        $array_tit = array_merge($array_tit, $output);
        
      }
      $output_sub = [
              'subtotal' => [
                'data' => 'CALCULAR',
                'rowspan' => '2',
                'class' => array('mayusc'),
                'title' => 'CALCULAR'
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
            ];
        $array_tit = array_merge($array_tit, $output_sub);
  return $array_tit;
}
public function obtenerProgramasExistente(FormStateInterface $form_state){
		$programas = $form_state->getValue('programas');
		
        /*$linea_misional = 5; // Línea misional
        $linea_misional = $form_state->getValue('linea');
        $year = $form_state->getValue('year');
        $month = $form_state->getValue('month');
        $biblioteca = $form_state->getValue('biblioteca');

        // Obtener solo los programas que pertenzcan a cierta línea 
        $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = $linea_misional, $max_depth = 5, $load_entities = FALSE);
        // Crear un array con estos programas
        foreach ($terms as $key => $value) {
        $areas[] = $value->tid;
        }
        $nids = array();
        // Consultar todas actividades PROGRAMADAS DE ACUERDO AL FILTRO APLICADO
        $query = \Drupal::entityQuery('node')
            ->condition('type', 'metas_concesion')
            ->condition('status', 1)
            ->condition('field_programa_actividad', $areas, 'IN')
            ->condition('field_mes', $month)//82)//$month);
            ->condition('field_ano_actividad_programada', $year)
            ->condition('field_biblioteca', $biblioteca);
 
        $nids = $query->execute();
        $output[] = array();
        global $base_url;
		
        foreach ($nids as $key => $nid) {

          $node = \Drupal\node\Entity\Node::load($nid);
          
          $nid = $node->get('nid')->value;
          
          $title_field = $node->get('title')->value;

          $bib = $node->get('field_biblioteca')->getValue()[0]['target_id'];
          
          $prog = $node->get('field_programa_actividad')->getValue()[0]['target_id'];

          $mes = $node->get('field_mes')->getValue()[0]['target_id'];
		*/
          $variable = "";
          
          $output[] = array(
              'id' => "NID",
              'ene_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#value' => 1,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                    '#title' => 'ene_proc_mnc',
                  ),
                ),              
              'ene_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'ene_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'ene_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              
              //****** FEB ******

              'feb_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'feb_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'feb_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'feb_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              //****** Marzo ******

              'marz_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'marz_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'marz_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'marz_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              //****** Abril ******

              'abril_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'abril_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'abril_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'abril_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              //****** MAYO ******

              'mayo_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'mayo_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'mayo_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'mayo_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              //****** junio ******

              'junio_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'junio_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'junio_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'junio_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),

              //****** julio ******

              'julio_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'julio_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'julio_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'julio_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),

              //****** agosto ******

              'agosto_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'agosto_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'agosto_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'agosto_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              //****** sept ******
              
              'sept_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'sept_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'sept_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'sept_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),

              //****** oct ******

              'oct_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'oct_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'oct_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'oct_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              //****** nov ******

              'nov_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'nov_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'nov_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'nov_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              //****** Dic ******

              'dic_proc_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => 'metas[".$valor."]',
                  ),
                ),              
              'dic_proc_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'dic_prod_mnc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
              'dic_prod_mc' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                    	'style'=>'width: 10px;'
                    ),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),

                'sum' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array(
                      'style'=>'width: 10px;'
                    ),
                    '#id' => 'sum-'.$valor,
                    '#name' => "sum[".$valor."]",
                  ),
                ),

              //'linea'     => isset($prog) ? $prog : "",
              //'mes'       => isset($mes) ? $mes :"",
              //'anno'      => isset($node->get('field_ano_actividad_programada')->value) ? $node->get('field_ano_actividad_programada')->value : "",
            );
          
        return $output;
        }

}
?>