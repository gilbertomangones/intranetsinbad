<?php
/**
* @file
* Contains Drupal\programacionform\form\ProgramacionForm
*/

namespace Drupal\biblored_module\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

use \Drupal\node\Entity\Node;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Render\FormattableMarkup; 
use Drupal\taxonomy\Entity\Term;

class ProgramacionForm extends FormBase
{
  public function getFormId() {
    return 'biblored_module_programacionform'; //nombremodule_nombreformulario
  }

  public function buildForm(array $form, FormStateInterface $form_state) {


  	$vid = 'nodos_bibliotecas';
 
     $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
     $bibliotecas[0] = "Ninguno";
     foreach($terms as $term) {
         //$tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];
         if ($term->depth == 1) {
             // Array con todas las bibliotecas
               $term_data[] = array(
                   "id" => $term->tid,
                   "name" => $term->name,
                   //'tid_biblioteca_agenda' => \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'],
               );

             // Creando un array con la bibliotecas
             
            $bibliotecas[$term->tid] = $term->name;
             
         }

     }

     $form['biblioteca'] = array (
       '#type' => 'select',
       '#title' => ('Biblioteca'),
       '#options' => $bibliotecas, 
    );

    // In case of "December incre 1 month and 1 year"
     setlocale(LC_ALL,"es_ES");
     $current_year = date("Y");
     $mes_actual = date("n");

     if ( $mes_actual == 12){
        $current_month = '1';
        $current_year = date('Y') + 1;
     }else{
        $current_month = date("n") + 1;
     }
    
    $currentmonth = date("n");
    $next_month = $current_month;

    $month[0] = 1;
    // RESTRICCION DE LOS PRIMERO 5 DIAS DEL MES ACTUAL SE PODRÁ PROGRAMAR LA MALLA
    
    $plazo = \Drupal::config('Configuraciones.settings')->get('plazomalla');
    if ($plazo == "" || $plazo > 30 || $plazo < 1){
      $plazo = 5;
    }
    if (date("j") <= $plazo) {

      $month[1] = $currentmonth;  
    }
    $month[2] = $next_month;

    $year[0] = "Ninguno";
            
    $year[$current_year] = $current_year;

    foreach ($month as $key => $value) {
    
      switch ($value) {
      case '1':
      $label_mes = "Enero";
      break;
      case '2':
      $label_mes = "Febrero";
      break;
      case '3':
      $label_mes = "Marzo";
      break;
      case '4':
      $label_mes = "Abril";
      break;
      case '5':
      $label_mes = "Mayo";
      break;
      case '6':
      $label_mes = "Junio";
      break;
      case '7':
      $label_mes = "Julio";
      break;
      case '8':
      $label_mes = "Agosto";
      break;
      case '9':
      $label_mes = "Septiembre";
      break;
      case '10':
      $label_mes = "Octubre";
      break;
      case '11':
      $label_mes = "Noviembre";
      break;
      case '12':
      $label_mes = "Diciembre";
      break;
      case '0':
      $label_mes = "Seleccionar mes";
      break;
      }

      $month_enable[$value] = $label_mes;
      
    }
    $storage = $form_state->getStorage();
    

    $form['month'] = array (
     '#type' => 'select',
     '#title' => ('Mes'),
     '#options' => $month_enable,
    );

     $form['year'] = array (
       '#type' => 'select',
       '#title' => ('Año'),
       '#options' => $year,
     );

    $vid_linea = 'areas';
      // Obtener solo los tid del nivel 1  
    $terms_lineas =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_linea, $parent = 0, $max_depth = 1, $load_entities = FALSE);
      
    $lineas[0] = "Ninguno";
    foreach($terms_lineas as $linea) {
      //$tid_linea_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($linea->tid)->get('field_tid_linea_agenda')->getValue()[0]['value'];
        $term_data_linea[] = array(
          "name" => $linea->name,
          //'tid_linea_agenda' => $tid_linea_agenda,
          "id" => $linea->tid,
        );        
        $lineas[$linea->tid] = $linea->name; 
    }

    $form['linea'] = array(
       '#type' => 'select',
       '#title' => 'Línea',
       '#description' => 'Seleccione Línea Misional',
       '#options' => $lineas,
      ); 

    /*
    $header = array(
      'nid'     => t('Id actividad'),
      'programa'  => 'Programa',
      'cantidad' => 'Cantidad',
      'biblioteca' => 'Biblioteca',
      'linea' => 'Línea Misional',
      'mes' => 'Mes',
      'anno' => 'Año',
    ); */
    $header  = array(
      'id'        => "ID",
      'programa'  => "PROGRAMA",
      'cantidad'  => "CANTIDAD",
      //'biblioteca'=> "BIBLIOTECA",
      //'linea'     => "LÍNEA",
      //'mes'       => "MES",
      //'anno'      => "AÑO",
    );
    $form['programas'] = array(
        '#type' => 'button',
        '#value' => t('Buscar'),
        //'#prefix' => '<div id="user-email-result"></div>',
        '#ajax' => array(
          'callback' => '::changeOptionsAjax',
          'event' => 'click',
          'wrapper' => 'formprogramas',
          'progress' => array(
            'type' => 'throbber',
            'message' => 'Obtener programación',
          ),
          'method' => 'replace',
        ),
      );
  
    $output[] = array(
              'id' => "",
              'programa' => "Null",
              'cantidad'  => "Null",
              'biblioteca'=> "Null",
              'linea'     => "Null",
              'mes'       => "Null",
              'anno'      => "Null",
            );
   $form['table'] = array(
    //'#id'    => 'formprogramas',
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $this->obtenerProgramasExistente($form_state),
    '#multiple' => FALSE,
    '#prefix' => '<div id="formprogramas">',
    '#suffix' => '</div>',
    '#empty' => $this->t('Aun no existen programas'),
    '#attributes' => array('class' => array('programas-asignados')),
  );
  
  	$form['addprograma'] = array(
      '#type' => 'button',
      '#value' => t('Adicionar más Programas'),
      //'#prefix' => '<div id="user-email-result"></div>',
      '#ajax' => array(
        'callback' => '::addOptionsAjax',
        'event' => 'click',
        'wrapper' => 'formmasprogramas',
        'method' => 'replace',
        'progress' => array(
          'type' => 'throbber',
          'message' => 'Adicionar programación',
        ),
        
      ),
    );



    $form['adicionados'] = [
      '#prefix' => '<div id="form-test-add"></div>',
    ]; 
	
	$header2 = array(
	  //'id' 		=> t('Id actividad'),
	  'name' 	=> 'Programa',
    'metas'  => t('META PROCESO'),
    'publico' => t('META PRODUCTO'),
	);
	
	$form['table2'] = array(
    '#type' => 'tableselect',
    '#header' => $header2,
    '#options' => $this->programasDisponibles($form_state),  // The array we made a bit earlier.
    '#multiple' => TRUE,
    '#prefix' => '<div id="formmasprogramas">',
    '#suffix' => '</div>',
    '#attributes' => array('class' => array('programas-disponibles')),
  );
	
  $form['metas'] = array(
    '#type' => 'value',
  );
  $form['publico'] = array(
    '#type' => 'value',
  );
  $form['guardarprogramas'] = array(
      '#type' => 'submit',
      '#value' => t('ADICIONAR PROGRAMAS SELECCIONADOS'),
      '#prefix' => '<div id="form-test-adicionados"></div>',
      /*'#ajax' => array(
        'callback' => $this->guardarProgramas($form_state),
        'event' => 'click',
          'progress' => array(
          'type' => 'throbber',
          'message' => 'Adicionar programación',
        ),
        
      ),*/
    );
   
    return $form;
  }

  public function obtenerProgramasExistente(FormStateInterface $form_state){

        $linea_misional = 5; // Línea misional
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
        	->accessCheck(TRUE)
            ->condition('type', 'programacion_o_malla')
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

          $output[] = array(
              'id' => array('data' => new FormattableMarkup('<a href=":link">@name</a>', 
                      [':link' => $base_url.'/node/'.$nid.'/edit', 
                      '@name' => 'Editar']),
                    ),
              'programa' => isset($title_field) ? $title_field : t("No title"),
              'cantidad'  => $node->get('field_meta_sesiones')->value,
              'biblioteca'=> isset($bib) ? $bib :"",
              'linea'     => isset($prog) ? $prog : "",
              'mes'       => isset($mes) ? $mes :"",
              'anno'      => isset($node->get('field_ano_actividad_programada')->value) ? $node->get('field_ano_actividad_programada')->value : "",
            );
        }

    return $output;
  }


public function checkUserEmailValidation(array $form, FormStateInterface $form_state) {
   $ajax_response = new AjaxResponse();
 
  // Check if User or email exists or not
   if (user_load_by_name($form_state->getValue(user_email)) || user_load_by_mail($form_state->getValue(user_email))) {
     $text = 'User or Email is exists';
   } else {
     $text = 'User or Email does not exists';
   }
   $ajax_response->addCommand(new HtmlCommand('#user-email-result', $text));
   return $ajax_response;
   }


  /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $linea_misional = $form_state->getValue('linea');
    $year = $form_state->getValue('year');
    $month = $form_state->getValue('month');
    $biblioteca = $form_state->getValue('biblioteca');
    $publico_esperado = 0;

    $values = $form_state->getValues();

    $meta = 0;
    
    foreach ($values['table2'] as $key => $value) {
      if ($value != 0){
        
        $meta = $values['metas'][$value];

        $publico_esperado = $values['publico'][$value];
        
        //$term_object = taxonomy_term_load($value);
      	$term_object = Term::load($value);
        
        $nombre_programa = $term_object->get('name')->value; 

        $fr = $year."-".$month."-"."02";
        $dateTime = \DateTime::createFromFormat('Y-m-d', $fr);
        $newDateString = $dateTime->format('Y-m-d\TH:i:s');

        $node = Node::create([
          'type'                            => 'programacion_o_malla',
          'title'                           => $nombre_programa,
          'field_ano_actividad_programada'  => $year,
          'field_mes'                       => $month,
          'field_meta_sesiones'             => $meta,
          'field_biblioteca'                => $biblioteca,
          'field_programa_actividad'        => $value,
          'field_publico_esperado'          => $publico_esperado,
          'field_fecha_realizada'           => $newDateString,
        ]); 
        $node->save();
      }
    }

  
 	\Drupal::messenger()->addMessage(
           $this->t('Programas o actividades guardadas')
         );
  }

  public function changeOptionsAjax(array &$form, FormStateInterface $form_state) {
     
     $form['table2']['#options'] = $this->programasDisponibles($form_state);
     //$form['table']['#options'] = $output; //$this->obtenerProgramasExistente($form_state);
     
     return $form['table'];

  }
 

  public function addOptionsAjax(array &$form, FormStateInterface $form_state) {
    return $form['table2'];
  }

 public function programasDisponibles(FormStateInterface $form_state){
 	
  $programas_disponibles = array();
  $sorted  = array();
  $linea_misional = $form_state->getValue('linea');
  $year = $form_state->getValue('year');
  $month = $form_state->getValue('month');
  $biblioteca = $form_state->getValue('biblioteca');
    // Obtener solo los programas que pertenzcan a cierta línea 
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = $linea_misional, $max_depth = 6, $load_entities = FALSE);

    
    // Crear un array con estos programas
    

	foreach ($terms as $key => $value) {

    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($value->tid);
    
    $estado = $term->field_suprimir_activar->value;
    // Obtener el ultimo tid depth de la lista
    $children =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($value->tid, 'areas');
    // ESTADO SUSPENDIDO = 1
    if ($estado == 0 && empty($children)){
      $areas[] = $value->tid;  
    }
	}

	$nids = array();

	// Consultar todas actividades PROGRAMADAS DE ACUERDO AL FILTRO APLICADO
	$query = \Drupal::entityQuery('node')
    		->accessCheck(TRUE)
   		 	->condition('type', 'programacion_o_malla')
		    ->condition('status', 1)
		    ->condition('field_programa_actividad', $areas, 'IN')
		    ->condition('field_mes', $month)//$month);
		    ->condition('field_ano_actividad_programada', $year)
		    ->condition('field_biblioteca', $biblioteca);
		    
	$nids = $query->execute();
  $programas_habilitados = array();

	if (!empty($nids)) {
	foreach ($nids as $node){

		$nodo = \Drupal::entityTypeManager()->getStorage('node')->load($node);
		
		$terms = $nodo->get('field_programa_actividad')->getValue();  
		
		$programas_habilitados[] = $terms[0]['target_id'];
	}
  
 }
	foreach($areas as $valor){ //recorremos el array1 valor por valor 
	 if(array_search($valor,$programas_habilitados) !== false){ 
	 	//y le preguntamos: esta el valor en el que estamos posicionados actualmente, en el array 2? 
	    $programas_no_disponibles[] = $valor;
	   } 
	 else {

	 	//$term_object = taxonomy_term_load($valor);
		$term_object = Term::load($valor);
	 	$programas_disponibles[$valor] = array(
	 	'id'   => $valor,
	 	'name' => $term_object->get('name')->value,
    'metas' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array('class' => array('meta')),
                    '#id' => 'metas-'.$valor,
                    '#name' => "metas[".$valor."]",
                  ),
                ),
    'publico' => array(
                  'data' => array(
                    '#type' => 'textfield',
                    '#value' => 0,
                    '#size' => 10,
                    '#attributes' => array('class' => array('publico')),
                    '#id' => 'publico-'.$valor,
                    '#name' => "publico[".$valor."]",
                  ),
                ),
	 	 ); //PROGRAMAS PARA HABILITAR O PROGRAMAR EN LA MALLA

	 }


	}

  $sorted = $this->val_sort($programas_disponibles, 'name');
  
	return $sorted;
 }

 public function val_sort($array,$key) {
  $clave = "";
  //Loop through and get the values of our specified key
  foreach($array as $k=>$v) {
    $clave = $v['id'];
    $b[$clave] = strtolower($v[$key]);
  }
  
  //print_r($b);
  
  asort($b);
  
  //echo '<br />';
  //print_r($b);
  
  foreach($b as $k=>$v) {
    
    $c[$k] = $array[$k];
  }
  //print_r($c);
  return $c;
}




}