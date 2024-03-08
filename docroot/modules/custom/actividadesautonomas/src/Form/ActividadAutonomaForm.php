<?php

namespace Drupal\actividadesautonomas\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\biblored_module\Controller\EvEndpoint;
use Drupal\reportes\Controller\FuncionesController;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Serialization\Json;

class ActividadAutonomaForm extends FormBase {

  public function getFormId() {
      return 'actividadesautonomas_actautonomaform'; //nombremodule_nombreformulario
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
    $host_agenda = \Drupal::config('Configuraciones.settings')->get('uribase');
/*
    $vid_linea = 'areas'; // Nombre de la taxonomía
    // Obtener solo los tid del nivel 1  
    $terms_lineas =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_linea, $parent = 0, $max_depth = 1, $load_entities = FALSE);
    $lineas[0] = "Ninguno";
     foreach($terms_lineas as $linea) {
        $term_data_linea[] = array(
          "name" => $linea->name,
          //'tid_linea_agenda' => $tid_linea_agenda,
          "id" => $linea->tid,
       );        
       $lineas[$linea->tid] = $linea->name; 
       }
    */
    // SOLO LINEAS
  $vid_linea = 'areas';
  // Obtener solo los tid del nivel 1  
  $terms_lineas =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_linea, $parent = 0, $max_depth = 1, $load_entities = FALSE);

  $terms_programas =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_linea, $parent = 0, $max_depth = 1, $load_entities = FALSE);
  $lineas[''] = "--Seleccionar--";
  foreach($terms_lineas as $linea) {
  //$tid_linea_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($linea->tid)->get('field_tid_linea_agenda')->getValue()[0]['value'];
    $termino = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($linea->tid);
    $estado = $termino->field_suprimir_activar->value;
    
    $categoria_actividad = $termino->get('field_categoria_actividad')->getValue(); //field_categoria_actividad->value;
    
    $sw = 0;
    // Mostrar solo los misionales
    foreach ($categoria_actividad  as $value) {
      if ($value['target_id'] == '1274') {
          $sw = 1;
          break;
        }
    }
  if ($estado == 0 && $sw ==1) { //&& $categoria_actividad == '1274') {
          $term_data_linea[] = array(
            "name" => $linea->name,
            //'tid_linea_agenda' => $tid_linea_agenda,
            "id" => $linea->tid,
          );        
          $lineas[$linea->tid] = $linea->name; 
      }
  }
    
  // Autonomos
  $vid_linea = 'areas';
  // Obtener solo los tid del nivel 1  
  $terms_lineas =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_linea);
  
  
  foreach($terms_lineas as $linea) {
    
  $tid_autonomo = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($linea->tid);
  
  
  $autonomo = $tid_autonomo->get('field_autonomo')->getValue();
  $termino = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($linea->tid);
  $estado = $termino->field_suprimir_activar->value;
  if ($estado == 0){
     $term_data_linea[$linea->tid] = $linea->name;
  }
  }
    // BIBLIOTECAS
    $vid = 'nodos_bibliotecas';
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $bibliotecas[''] = "--Seleccionar--"; 
    foreach($terms as $term) {  
          if ($term->depth == 0) { // 0 PARA EL PADRE
              
             // Array con todas las bibliotecas
                 $term_data[] = array(
                     "id" => $term->tid,
                     "name" => $term->name,
                 );
                 $bibliotecas[$term->tid] = $term->name;
          }
     }
  //In this case, display only current concesion/plan, 
  global $base_url;
  $base_url_parts = parse_url($base_url); 
  // ** $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
  // ** $end_point_planactual = $host."/json/planactual";
  $end_point_planactual = "https://intranet.biblored.net/sinbad/json/planactual";
  $datos = $this->file_get_contents_curl($end_point_planactual);
  
  //$datos = file_get_contents($end_point_planactual);
  $cat_facts = json_decode($datos, TRUE);
  $tid_plan_actual = $cat_facts[0]['field_concesion'];
  /*
  $parent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($tid_plan_actual);
  $concesion['_none'] = "Seleccionar Concesión";
  foreach ($parent as $term) {
    $concesion[$term->tid->value] = $term->getName();
  }*/
  $concesion['_none'] = "Seleccionar Concesión";  
  foreach ($tid_plan_actual as $tidplan){
    $parent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($tidplan); 
    foreach ($parent as $term) {
      //Condicion temporal
      //if ($term->tid->value == 1602) { //solo para siau
        $concesion[$term->tid->value] = $term->getName();
        //}
    }
  } 
  // PIn this case, display all Concesions
  /*   
  $vid_concesion = 'concesion';
  $terms_plan =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_concesion, $parent = 0, $max_depth = 1, $load_entities = FALSE);
  $concesion['All'] = "Ninguna";
  foreach ($terms_plan as $key => $term) {
      $concesion[$term->tid] = $term->name;
  }
   */
    /*
  $form['filtro']['autonomos'] = [
      '#type' => 'select',
      '#title' => 'Programas Autónomos',
      '#validated' => TRUE,
      '#options' => $term_data_linea,
      '#default_value' => isset($form_state->getValues()['autonomos'])?$form_state->getValues()['autonomos']:"", 
      '#prefix' => '<div class="">',
    '#suffix' => '</div>',
      '#required'=> TRUE,
    ];
  */
  asort($lineas);
  $form['filtro']['linea'] = array(
       '#type' => 'select',
       '#title' => 'Línea',
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
        '#prefix' => '<div class=" mallalinea">',
        '#suffix' => '</div>',
    ); 

  $form['filtro']['programas'] = [
      '#type' => 'select',
      '#title' => 'Estrategia',
      '#validated' => TRUE,
      '#attributes' => array(
                      'style'=>'',
                      'class' => array(''),
                    ),
      '#options' => $this->getOptions($form_state),
      '#prefix' => '<div class="" id="programas-wrapper">',
      '#suffix' => '</div>',
      //'#default_value' => isset($form_state->getValues()['programas'])?$form_state->getValues()['programas']:"",
      '#ajax' => [
        'callback' => [$this, 'getSubprogramas'], //'::getProgramas',
        'wrapper' => 'programas-wrapper1',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => array('message' => 'En proceso...', 'type' => 'throbber'),
      ],
    ];

    $form['filtro']['autonomos'] = [
      '#type' => 'select',
      '#title' => 'Acciones autónomas',
      '#validated' => TRUE,
      '#required'=> TRUE,
      '#attributes' => array(
                      'style'=>'',
                        'class' => array(''),
                    ),
      '#options' => $this->getOptionsSubprogramas($form_state),
      '#prefix' => '<div class="col-md-4" id="programas-wrapper1">',
      '#suffix' => '</div>',
      //'#default_value' => isset($form_state->getValues()['autonomos'])?$form_state->getValues()['autonomos']:"", 
    ];
    asort($bibliotecas);
    $form['filtro']['biblioteca'] = array(
     '#type' => 'select',
     '#title' => $this->t('Espacios'),
     '#options' => $bibliotecas,
     '#ajax' => [
        'callback' => [$this, 'getBibliotecas'], //'::getProgramas',
        'wrapper' => 'bibliotecas-wrapper-espacio',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    '#prefix' => '<div class="">',
  '#suffix' => '</div>',
    );
  $form['filtro']['biblioteca_2'] = [
      '#type' => 'select',
      '#title' => $this->t('Biblioteca'),
      '#required'=> TRUE,
      '#options' => $this->getOptionsBibliotecas($form_state),
      '#validated' => TRUE,
      //'#default_value' => isset($form_state->getValues()['biblioteca_2'])?$form_state->getValues()['biblioteca_2']:"",
      //'#empty_option' => $this->t('Bibliotecas'),
      '#prefix' => '<div class="" id="bibliotecas-wrapper-espacio">',
      '#suffix' => '</div>',
  ];
   /*
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
        '#prefix' => '<div class="col-md-6" id="planes-wrapper01">',
        '#suffix' => '</div>',
        '#validated' => 'true',
       '#attributes' => array(
                      'style'=>'width=100%!important',
                    ),
      );
  $form['filtro']['planes'] = [
      '#type' => 'select',
      '#title' => t('Plan'),
      '#required'=> TRUE,
      '#validated' => TRUE,
        '#options' => $this->getOptionsPlanes($form_state),
        '#default_value' => isset($form_state->getValues()['planes'])?$form_state->getValues()['planes']:"",
            //'#options' => $options2,//array($tid_planactual => 'Plan actual'),
      '#prefix' => '<div class="col-md-6" id="planes-wrapper">',
      '#suffix' => '</div>',
  ];
  
       */ 
      
  $form['filtro']['actions'] = [
        '#type' => 'actions',
        'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Ingresar actividad autónoma'),
        ],
        '#prefix' => '<div class="btn-ingresar">',
        '#suffix' => '</div>',
    ]; 
      return $form;
    }

    /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('autonomos') == "") {
      $form_state->setErrorByName('autonomos', $this->t('Debe seleccionar una acción'));
    }
  /*
    if ($form_state->getValue('biblioteca_2') == 0) {
      $form_state->setErrorByName('biblioteca_2', $this->t('Debe seleccionar una biblioteca'));
    }
    if ($form_state->getValue('plan') == 0) {
      $form_state->setErrorByName('plan', $this->t('Debe seleccionar un plan de conceión'));
    }*/
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

      // Display the results.
      
      // Call the Static Service Container wrapper
      // We should inject the messenger service, but its beyond the scope of this example.
      $messenger = \Drupal::messenger();
      //$messenger->addMessage('Title: '.$form_state->getValue('title'));
      //$messenger->addMessage('Accept: '.$form_state->getValue('accept'));
      
      //$subprograma = $form_state->getValue('subprogramas');
      $accion_autonoma = $form_state->getValue('autonomos');
      $biblioteca = $form_state->getValue('biblioteca_2');
      $plan = $form_state->getValue('planes');
    $term = Term::load($accion_autonoma);
    $name = $term->getName();
    //predeterminar plan de acuerdo al programa.
  	  $tid = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($accion_autonoma);
  	  $plan_pred = $tid->get('field_plan')->getValue();
  	  
      $params['query'] = [
        'subprograma' => $form_state->getValue('autonomos'),
        'biblioteca' => $form_state->getValue('biblioteca_2'),
        'plan' => isset($plan_pred[0])? $plan_pred[0]['target_id']: '', //$form_state->getValue('planes')
          'namesubp' => $name,
      ];
  	/*
      $params['query'] = [
        'subprograma' => $form_state->getValue('autonomos'),
        'biblioteca' => $form_state->getValue('biblioteca_2'),
        'plan' => $form_state->getValue('planes'),
          'namesubp' => $name,
      ];*/
      // Redirect to home
      $form_state->setRedirectUrl(Url::fromUri('internal:' . '/node/add/actividad_ejecutada', $params));
        //$form_state->setRedirectUrl(Url::fromUri("http://localhost/estadisticas-biblored/node/add/actividad_ejecutada?edit%5Btitle%5D=Leo%20con%20mi%20beb%C3"));
    } 

  function getProgramas($form, FormStateInterface $form_state) {

      return $form['filtro']['programas'];
  }

  function getSubprogramas($form, FormStateInterface $form_state) {

      return $form['filtro']['autonomos'];
  }

  function getBibliotecas($form, FormStateInterface $form_state) {

      return $form['filtro']['biblioteca_2'];
  }

  function getPlanes($form, FormStateInterface $form_state) {

      return $form['filtro']['planes'];
  }

  public function getOptions(FormStateInterface $form_state) {

    $linea_misional = $form_state->getValue('linea');

    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = $linea_misional, $max_depth = 1, $load_entities = FALSE);
    
    $subprogramas = array();
      $subprogramas[] = "Ninguno";
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

    public function getOptionsSubprogramas(FormStateInterface $form_state) {

      $subprogramas[''] = "Selecione una acción";
	  $linea_misional = $form_state->getValue('programas');
      if ($linea_misional != 0) {		
		
    	$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = $linea_misional, $max_depth = 1, $load_entities = FALSE);
        foreach ($terms as $key => $value) {

          $tid_autonomo = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($value->tid);
  
          $autonomo = $tid_autonomo->get('field_autonomo')->getValue();
        
          $termino = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($value->tid);
  
          $estado = $termino->field_suprimir_activar->value;
        
          $categoria_actividad = $tid_autonomo->get('field_categoria_actividad')->getValue();
          
          $categoria = isset($categoria_actividad[0]) ? $categoria_actividad[0]['target_id'] : "";
        
    
    $sw = 0;
    // Mostrar solo los territoriales
    
  	foreach ($categoria_actividad  as $value_tax) {
    	
    	if ($value_tax['target_id'] == '1274') {
        	$sw = 1;
        	break;
        }
    }
        // Revisar primero si está activo este termino (no suspendido) y también que sea autónomo y que sea misional el tipo
        
        //if (isset($autonomo[0])){
            //&& $categoria == '1274' Para solo Misionales
            //if ($estado == 0 && $autonomo[0]['value'] == 1 ){
        	if ($estado == 0 && $sw == 1 ){
                  $subprogramas[$value->tid] = $value->name;              
            }  
          //}
        	$options = $subprogramas;  
        }
        
      }
      else {
        $options = [
          '' => 'Debe seleccionar una acción primero'
        ];
      }
    return $options;
    }

    public function getOptionsBibliotecas(FormStateInterface $form_state) {

    $statistics = new EvEndpoint;
  	$sw = 0;
    $options = "";
    //$options = $bib->bibliotecas_sistema($form_state->getValue('biblioteca'));  
    // Validar rol y biblioteca asignada, para obtener todas o la biblioteca asignada
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id()); // Usuario actual
    //$user = \Drupal\user\Entity\User::load(1333);
    $biblioteca = $user->get('field_biblioteca_o_nodo')->getValue();
    $cod_biblioteca = isset($biblioteca[0]['target_id'])?$biblioteca[0]['target_id']:"";
    
    $current_user = \Drupal::currentUser();

    $roles_excluidos = array("authenticated");
    $roles = $current_user->getRoles();
    
    //var_dump($roles); Ludotecario
    foreach ($roles as $key => $value) {
      if ($value == 'promotores_biblioteca' || 
          $value == 'coordinador_biblioteca' || 
          $value == 'profesional_biblioteca' || $value=='administrator')
           {
          // Para el caso que hay usuarios al cual no se les ha asignado una biblioteca
          $sw = 1;
      } // Fin If
    }//Fin foreach
  
    if ($sw == 1){
      if (!empty($cod_biblioteca)){
        //echo "Biblioteca asignada:".$cod_biblioteca;      
        //$options = $statistics->bibliotecas_sistema_asignada($form_state->getValue('biblioteca'), $cod_biblioteca);  
        $espacio = $form_state->getValue('biblioteca');
        //echo "Biblioteca asignada:".$cod_biblioteca;      
        //$options = $statistics->bibliotecas_asignadas($form_state->getValue('biblioteca'), $cod_biblioteca);
        $options = $statistics->bibliotecas_sistema_asignada($espacio, $biblioteca);
      }else{
        //echo "biblioteca no asignada";
        //$options = $statistics->bibliotecas($form_state->getValue('biblioteca'));
      }
    }else{
      $options = $statistics->bibliotecas_sistema($form_state->getValue('biblioteca'));
    }
   // $form['biblioteca_2']['#options'] = $options;
    
    //return $form['biblioteca_2'];
    
    return $options;
    
  }

  public function getOptionsPlanes(FormStateInterface $form_state) {
       global $base_url;
    //$host_agenda = \Drupal::config('Configuraciones.settings')->get('uribase');
      $base_url_parts = parse_url($base_url); 
      $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
      $statistics = new FuncionesController;
      $conc =  new EvEndpoint;
      $end_point_planactual = $host."/json/planactual"; 
      $datos = $this->file_get_contents_curl($end_point_planactual);
  
      //$datos = file_get_contents($end_point_planactual);
    $cat_facts = json_decode($datos, TRUE);
      $plan = $cat_facts[0]['field_concesion'];
      //var_dump($plan);
      $options = $statistics->plan_default($form_state->getValue('concesion'), $plan);  
      
    
    //$form['filtro']['planes']['#options'] = $options;

    return $options;
  
    }

 public function file_get_contents_curl($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
  curl_setopt($ch, CURLOPT_URL, $url);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
  }
}