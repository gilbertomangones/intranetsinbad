<?php
/**
* @file
* Contains Drupal\resumenform\form\ResumenForm
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

class ResumenForm extends FormBase
{
  public function getFormId() {
    return 'biblored_module_resumenform'; //nombremodule_nombreformulario
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
    if (date("j") <= 27) {

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
              'biblioteca' => "Null",
              'linea' => "Null",
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
  
	
  $form['metas'] = array(
    '#type' => 'value',
  );
  $form['publico'] = array(
    '#type' => 'value',
  );

    return $form;
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
  
  $tid_biblioteca_agenda = $form_state->getValue('biblioteca');
  $tid_linea_agenda = $form_state->getValue('linea');
  $mes =  $form_state->getValue('month');
  $year =  $form_state->getValue('year');


\Drupal::messenger()->addMessage(
          $this->t('Valores: @year / @month / @biblioteca / @linea',  
      [ '@year' => $form_state->getValue('year'),
      '@month' => $form_state->getValue('month'),
      '@biblioteca' => $form_state->getValue('biblioteca'),
      '@linea' => $form_state->getValue('linea'),
      ])
         );

}
public function changeOptionsAjax(array &$form, FormStateInterface $form_state) {
     
     //$form['table2']['#options'] = $this->obtenerProgramasExistente($form_state);
     //$form['table']['#options'] = $output; //$this->obtenerProgramasExistente($form_state);
     
     return $form['table'];

  }

public function obtenerProgramasExistente(FormStateInterface $form_state){

    $linea_misional = 65; // Línea misional
    $year = 2018;
    $month = '1';
    $tid_biblioteca_agenda = 65;
    //$linea_misional = $form_state->getValue('linea');
    //$year = $form_state->getValue('year');
    //$month = $form_state->getValue('month');
    //$biblioteca = $form_state->getValue('biblioteca');

    $uri =  "http://localhost/estadisticas-biblored/concesion/".$tid_biblioteca_agenda."/".$month.'/'.$year;

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
    $json = file_get_contents($uri);
    $obj = json_decode($json);
    $output = array();
    foreach($obj as $o){

    	 $output[] = array(
              'id' => '1',
              'programa'  => $o->title,
              'cantidad'  => $o->field_meta_sesiones,
              'biblioteca'=> $o->field_biblioteca,
              'linea'     => $o->field_linea,
            ); 
    }

  

  return $output;

}

}