<?php
/**
 * @file
 * Contains \Drupal\reportes\Form\GenerarGrupoPoblacionalForm.
 */
namespace Drupal\reportes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\reportes\Controller\FuncionesController;
use Drupal\taxonomy\Entity\Term; // Leer campos que dependan de Términos 
use Drupal\Core\Datetime\DrupalDateTime; // Condition date
use Drupal\biblored_module\Controller\EvEndpoint;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
/**
 * Implements an reports module form.
 */
class GenerarGrupoPoblacionalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reportes_GenerarGrupoPoblacionalForm'; //nombremodule_nombreformulario  
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // BIBLIOTECAS
    $vid = 'nodos_bibliotecas';
    $bib = new EvEndpoint;
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $bibliotecas['All'] = "Todos";
   
    foreach($terms as $term) {
     //$tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda');
         
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
     $form['filtro']['biblioteca'] = array(
     '#type' => 'select',
     '#title' => t('Espacio'),
     '#description' => 'Espacio',
     '#options' => $bibliotecas,
     '#required'=> true,
     '#ajax' => [
        'callback' => '::getBibliotecas',
        'wrapper' => 'bibliotecas-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
      '#prefix' => '<div class="col-md-6">',
      '#suffix' => '</div>',
    );

    $form['filtro']['biblioteca_2'] = [
          '#type' => 'select',
          '#title' => 'Biblioteca',
          //'#validated' => TRUE,
          '#options' =>  $bib->bibliotecas_sistema($form_state->getValue('biblioteca')),
          //'#empty_option' => $this->t('Bibliotecas'),
          '#prefix' => '<div class="col-md-6" id="bibliotecas-wrapper">',
          '#suffix' => '</div>',
      ];

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
      '#prefix' => '<div class="col-md-4">',
      '#suffix' => '</div>',
      //'cardinality' => 3,
      '#required' => TRUE,
    ); 

    $form['filtro']['estrategias'] = [
      '#type' => 'select',
      '#title' => t('Estrategia'),
      //'#validated' => TRUE,
      '#options' => $this->getOptions($form_state),
      '#prefix' => '<div class="col-md-4" id="programas-wrapper">',
      '#suffix' => '</div>',
      '#default_value' => isset($form_state->getValues()['estrategias'])?$form_state->getValues()['estrategias']:"",
      //'cardinality' => 4,
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
      //'#validated' => TRUE,
      '#options' => $this->getOptionsProgramas($form_state),
      '#prefix' => '<div class="col-md-4" id="programas-wrapper1">',
      '#suffix' => '</div>',
      '#default_value' => isset($form_state->getValues()['programas'])?$form_state->getValues()['programas']:"",
      //'cardinality' => 4,
    ];

  $form['fechainicial'] = array(
    '#title' => t('Entre Fecha inicial'),
    '#type' => 'date',
    //'#required' => true,
  );
  $form['fechafinal'] = array(
    '#title' => t('y Fecha final'),
    '#type' => 'date',
    //'required' => true,
  );

  
  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => $this->t('Buscar'),
    '#button_type' => 'primary',
  ];
    
    /*$form['exp'] = [
      '#title' => $this->t('Exportar excel'),
      '#type' => 'link',
      '#url' => 'javascript:;',
      '#attributes' => array('id' => 'exportarexcel'),
    ];
	*/


    $values = $form_state->getValues();
    
    if (!empty($values)) {
      $form['exportar'] = [
      '#type' => 'processed_text',
      '#text' => "<a id='exportarexcel' href='javascript:;'>Exportar Excel</a>",
      '#format' => 'full_html',
    ];
      $resultados = $form_state->get('field_count');
      
      $form_state->set('field_count', $resultados);
      
      /*$form['results'] = [
        '#type' => 'processed_text',
        '#text' => $resultados,//$form_state->getValue('biblioteca')."hola",
        '#format' => filter_default_format()
      ];*/

      $header = [
          'biblioteca' => t('Biblioteca'),
          'subprograma' => t('Programa'),
          'cero5'  => t('0-5 años'),
          'doce'=> t('6-12 años'),  
          'diez7' => t('13 a 17 años'),
          'veinte8' => t('18 a 28 años '),
          'cinco9' => t('29-59 años'),
          'sesenta' => t('60 años en adelante'),
          'masc' => t('Masculino'),
          'fem' => t('Femenino'),
          'transg' => t('Transgénero'),
        ];
         
      // RESULTADOS
      $i = 0;
      $tabla = [];
      foreach ($resultados as $key => $record) {
        
        $tabla[] = [
           'biblioteca' => $record['biblioteca'],
           'subprograma' => $record['subprograma'],
           'cero5' => $record['cero5'],
           'doce' => $record['doce'],
           'diez7' => $record['diez7'],
           'veinte8' => $record['veinte8'],
           'cinco9' => $record['cinco9'],
           'sesenta' => $record['sesenta'],
           'masc' => $record['masc'],
           'fem' =>  $record['fem'],
           'transg' =>  $record['transg'],
         ];
         $i++;
      }

      $form['table'] = [
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $tabla,
        '#empty' => t('Sin información encontrada'),
        '#attributes' => [],
        '#prefix' => '<div id="dvData">',
        '#suffix' => '</div>',
        ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  	$espacios_bib = $form_state->getValue('biblioteca');
    if ($espacios_bib == 'All') {
      $form_state->setErrorByName('biblioteca', $this->t('Debe seleccionar una Espacio'));
    }   
    
  }

  /**
   * {@inheritdoc}
   */ 
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    global $base_url;
    $bibli = 0;
    $fechaini = $form_state->getValue('fechainicial');
    $fechafin = $form_state->getValue('fechafinal');
    $linea = $form_state->getValue('linea');
    $estrategia = $form_state->getValue('estrategias');
    $subprograma = $form_state->getValue('programas');
    $espacio = $form_state->getValue('biblioteca');
    $bibli =  $form_state->getValue('biblioteca_2');
    $output1 = array();
    $union  = array();
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
    $valor_linea = "";
    $output_final = "";
    $num_subprogramas = 0;
    $bibli_sel = "";
  	$output = [];
  	$output_final = [];
    // getting "linea"
    /* if ( ($linea != "" || $linea !="All") && ($estrategia == "All") ){
          $valor_linea = $linea;
    }elseif (($linea != "" || $linea !="All") && ($estrategia != "All") && ($subprograma == "All")) {
              $valor_linea = $estrategia;
    }elseif ($linea != "All" && $estrategia != "All" && $subprograma != "All"){
              $valor_linea = $subprograma;
    }
    */
  	if (($linea == "All") ) {
    	$valor_linea = $linea;
  	}elseif ($linea !="All" && $estrategia == "All" && ($subprograma == "All")) {
    	$valor_linea = $estrategia;
  	}elseif ($linea != "All" && $estrategia != "All" && $subprograma != "All"){
    	$valor_linea = $subprograma;
  	}
  	$valor_linea = "&linea=" . $valor_linea;
    // Getting date rang
    $fecha_busqueda = "&fecha%5Bmin%5D=".$fechaini."&fecha%5Bmax%5D=".$fechafin;
    $porc_cumpl = 0;

    if (($espacio != "All") && ($bibli == "All")){
        $bibli_sel = "bib=".$espacio; 
    }elseif ( ($espacio != "All") && ($bibli != "All") ) {
        $bibli_sel = "bib=".$bibli; 
    }
    $statistics = new FuncionesController;
    $host = \Drupal::request()->getHost();   
    $base_url_parts = parse_url($base_url);

    $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
    
    if ($valor_linea != ""){
      $sentencia_linea = "&linea=".$valor_linea;
    }
    $host_agenda = \Drupal::config('Configuraciones.settings')->get('uribase');
    $endpoint = "https://intranet.biblored.net/sinbad/json/grupopoblacional?".$bibli_sel.$sentencia_linea.$fecha_busqueda;
     try {
      $response = \Drupal::httpClient()->get($endpoint, array('headers' => array('Accept' => 'text/plain')));
      $data = (string) $response->getBody();
      if (empty($data)) {
        $datos = "";
    
        return FALSE;
      }
    }
    catch (RequestException $e) {
      return FALSE;
    }
    
    //$data = file_get_contents($endpoint);
  	$output = json_decode($data, TRUE);
  	if (!empty($output)) {
      
      //$output = Json::decode($data, true); 
       // Agrupar por Bibliotecas y Programas
       
       foreach ($output as $key => $value) {
         $biblioteca_id = $value['field_biblioteca'];
         $linea_id = $value['field_linea'];
         $array_add = array(
            'field_biblioteca' => $value['field_biblioteca'],
            'name_biblioteca' => $value['field_biblioteca_1'],
            'field_linea' => $value['field_linea'],
            'name_linea' => $value['field_linea_1'],
            'asistentes_0_5' => $value['field_numero_asistentes_0_5_'],
            'asistentes_6_12' => $value['field_numero_asistentes_6_12_'],
            'asistentes_13_18' => $value['field_numero_asistentes_13_18_'],
            'asistentes_19_27' => $value['field_numero_asistentes_19_27_'],
            'asistentes_28_60' => $value['field_numero_asistentes_28_60'],
            'asistentes_61' => $value['field_numero_asistentes_61_mas'],
            'femenino' => $value['field_participantes_de_genero_fe'],
            'masculino' => $value['field_participantes_de_genero_ma'],
            'transgenero' => $value['field_participantes_de_genero_tr'],
         );
         $track1[$biblioteca_id][$linea_id][] = $array_add;
       }
      
      $output_final = array();
      $asistentes_1 = 0;
      $total = 0;
      // Bibliotecas
      foreach ($track1 as $key => $value) {
        
        // Programas
        foreach ($value as $key2 => $value2) {
          $sum_asistentes_0_5 = 0;
          $sum_asistentes_6_12 = 0;
          $sum_asistentes_13_18 = 0;
          $sum_asistentes_19_27 = 0;
          $sum_asistentes_28_60 = 0;
          $sum_asistentes_61 = 0;
          $sum_femenino = 0;
          $sum_masculino = 0;
          $sum_transgenero = 0;
          // node c/programa
          foreach ($value2 as $key3 => $value3) {
             $biblioteca = $value3['name_biblioteca'];
             $programa = $value3['name_linea'];
             $sum_asistentes_0_5 += $value3['asistentes_0_5'];
             $sum_asistentes_6_12 += $value3['asistentes_6_12'];
             $sum_asistentes_13_18 += $value3['asistentes_13_18'];
             $sum_asistentes_19_27 += $value3['asistentes_19_27'];
             $sum_asistentes_28_60 += $value3['asistentes_28_60'];
             $sum_asistentes_61 += (int)$value3['asistentes_61'];
             $sum_femenino += $value3['femenino'];
             $sum_masculino += $value3['masculino'];
             $sum_transgenero += $value3['transgenero'];
          }
            $output_final[$key][$key2] = array(
              'biblioteca' => $key,
              'name_biblioteca' => $biblioteca,
              'subprograma' => $key2,
              'name_programa' => $programa,
              'total' => $total,
              'asistentes_0_5'  => $sum_asistentes_0_5,
              'asistentes_6_12'=> $sum_asistentes_6_12,  
              'asistentes_13_18' => $sum_asistentes_13_18,
              'asistentes_19_27' => $sum_asistentes_19_27,
              'asistentes_28_60' => $sum_asistentes_28_60,
              '61_adelante' => $sum_asistentes_61,
              'fem' => $sum_femenino,
              'masc' => $sum_masculino,
              'transg' => $sum_transgenero,
            );
        }
        //
      }
      
      $output = array();
      foreach ($output_final as $key => $value) {
            $total = 0;
            foreach ($value as $key2 => $value2) {
              $biblioteca  =   $value2['biblioteca'];
              $name_biblioteca  =   $value2['name_biblioteca'];
              $subprograma =   $value2['subprograma'];
              $name_programa =   $value2['name_programa'];
              $cero5       =   $value2['asistentes_0_5'];
              $doce        =   $value2['asistentes_6_12'];
              $diez7 = $value2['asistentes_13_18'];
              $veinte8 = $value2['asistentes_19_27'];
              $sesenta = $value2['asistentes_28_60'];
              $adelante_61 = $value2['61_adelante'];
              $fem = $value2['fem'];
              $masc = $value2['masc'];
              $transg = $value2['transg'];
              
                $output[] = array(
                  'biblioteca' => $name_biblioteca, //.'(' . $biblioteca .')',
                  'subprograma' => $name_programa, //. '(' . $subprograma .')',
                  'cero5'  => $cero5,
                  'doce'=> $doce,  
                  'diez7' => $diez7,
                  'veinte8' => $veinte8,
                  'cinco9' => $sesenta,
                  'sesenta' => $adelante_61,
                  'fem' => $fem,
                  'masc' => $masc,
                  'transg' => $transg,
              );
            }

        }
        $grupo_subprograma = 0;
        $i = 0;
      
    } // data
      
      $form_state->set('field_count',$output);
      
      $form_state->setRebuild(); // Esta es la clave

  }

  function getProgramas($form, FormStateInterface $form_state) {
    return $form['filtro']['programas'];
  }

  function getSubprogramas($form, FormStateInterface $form_state) {
    $statistics = new FuncionesController;
    $form['dep']['subprogramas']['#options'] = $statistics->subprogramas($form_state->getValue('programas'));
    
    return $form['dep']['subprogramas'];
  }

  function getBibliotecas($form, FormStateInterface $form_state) {
    return $form['filtro']['biblioteca_2']; 
}

  function getEstrategias($form, FormStateInterface $form_state) {
    return $form['filtro']['estrategias'];
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

}