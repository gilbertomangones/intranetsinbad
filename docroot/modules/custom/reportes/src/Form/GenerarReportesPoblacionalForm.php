<?php
/**
 * @file
 * Contains \Drupal\reportes\Form\GenerarReportesPoblacionalForm.
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
/**
 * Implements an reports module form.
 */
class GenerarReportesPoblacionalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reportes_generarreportespoblacionalform'; //nombremodule_nombreformulario  
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // BIBLIOTECAS
    $vid = 'nodos_bibliotecas';
 
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $bibliotecas['All'] = "Todos";
   
    foreach($terms as $term) {
      
         $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];
         
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
     $form['biblioteca'] = array(
     '#type' => 'select',
     '#title' => 'Espacio',
     '#description' => 'Espacio',
     '#options' => $bibliotecas,
     '#required'=> true,
     '#ajax' => [
        'callback' => '::getBibliotecas',
        'wrapper' => 'bibliotecas-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    );
    if ($form_state->getValue('biblioteca')){
    $form['biblioteca']['#default_value'] = $form_state->getValue('biblioteca');
    }
    $form['biblioteca_2'] = [
          '#type' => 'select',
          '#title' => 'Biblioteca',
          '#required'=> true,
          '#validated' => TRUE,
          //'#options' =>  array(1=>"Demo"),
          //'#empty_option' => $this->t('Bibliotecas'),
          '#prefix' => '<div id="bibliotecas-wrapper">',
          '#suffix' => '</div>',
      ];

    if ($form_state->getValue('biblioteca_2')){
      $form['biblioteca_2']['#default_value'] = $form_state->getValue('biblioteca_2');
    }

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
      //'#options' =>  array(),
      '#empty_option' => $this->t('Programas'),
      '#default_value' => '',
      '#prefix' => '<div id="programas-wrapper">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::getSubprogramas',
        'wrapper' => 'programas-wrapper2',
        'method' => 'replace',
        'effect' => 'fade',
      ],
  ];

  $form['dep']['subprogramas'] = [
      '#type' => 'select',
      '#title' => 'Subprogramas',
      '#validated' => TRUE,    
      //'#options' =>  array(),
      '#empty_option' => $this->t('Subprogramas'),
      //'#default_value' => '',
      '#prefix' => '<div id="programas-wrapper2">',
      '#suffix' => '</div>',
  ];

  $form['fechainicial'] = array(
    '#title' => t('Entre Fecha inicial'),
    '#type' => 'date',
    '#required' => true,
  );
  $form['fechafinal'] = array(
    '#title' => t('y Fecha final'),
    '#type' => 'date',
    'required' => true,
  );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    ];

    $form['exp'] = [
      '#title' => $this->t('Reports'),
      '#type' => 'link',
      '#url' => 'javascript:;',
      '#attributes' => array('id' => 'exportar'),
    ];

    $values = $form_state->getValues();
    
    if (!empty($values)) {
      
      $form['exportar'] = [
        '#type' => 'processed_text',
        '#text' => "<a id='exportar' href='javascript:;'>Exportar</a>",
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
          'subprograma' => t('Subprograma'),
          'total' => t('Total asistentes'),
          'cero5'  => t('0-5 años'),
          'doce'=> t('6-12 años'),  
          'diez7' => t('13 a 17 años'),
          'veinte8' => t('18 a 28 años '),
          'cinco9' => t('29-59 años'),
          'sesenta' => t('60 años en adelante'),
          'masc' => t('Maculino'),
          'fem' => t('Femenino'),
          'transg' => t('Transgénero'),
        ];
         
      // RESULTADOS
      $i = 0;
      
      foreach ($resultados as $key => $record) {
        
        $tabla[] = [
           'subprograma' => $record['subprograma'],
           'total' => $record['total'], 
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
        ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*if (($form_state->getValue('biblioteca')) {
      $form_state->setErrorByName('biblioteca', $this->t('Debe seleccionar una Biblioteca'));
    } */  
    
  }

  /**
   * {@inheritdoc}
   */ 
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    global $base_url;
    $fechaini = $form_state->getValue('fechainicial');
    $fechafin = $form_state->getValue('fechafinal');
    $linea = $form_state->getValue('linea');
    $programa = $form_state->getValue('programas');
    $subprograma = $form_state->getValue('subprogramas');
    $bibli =  $form_state->getValue('biblioteca_2');
    $output1 = array();
    $union  = array();
    $bibli = 0;  
    $bibli =  $form_state->getValue('biblioteca_2');
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
    $valor_linea = 0;
    $output_final = "";
    $num_subprogramas = 0;
    
    if (($linea != "" || $linea =="All") && ($programa == "All" || $programa == "") && $subprograma =="") {
      $valor_linea = $linea;
    }elseif (($linea != "" || $linea !="All") && ($programa != "All") && $subprograma == "All"){
      $valor_linea = $programa;
    }elseif($linea != "All" && $programa != "All" && $subprograma != "All"){
      $valor_linea = $subprograma;
    }
    
    $fecha_busqueda = "&fecha%5Bmin%5D=".$fechaini."&fecha%5Bmax%5D=".$fechafin;
    $porc_cumpl = 0;

    //$linea = $form_state->getValue('linea');
    //$bibli =  $form_state->getValue('biblioteca');

    $statistics = new FuncionesController;
    $host = \Drupal::request()->getHost();   
    $base_url_parts = parse_url($base_url);

    $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
    
    if ($valor_linea != ""){
      $sentencia_linea = "&linea=".$valor_linea;
    }
    $endpoint = $host."/json/actividadespoblacional?bib=".$bibli.$sentencia_linea.$fecha_busqueda;
    
    
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

    if (!empty($data)) {
      
      $output = Json::decode($data); 
      foreach ($output as $value) {
        $entity_ids2[] = $value['nid'];
      } 
      
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids2);

      $num_subprogramas = count($nodes);

    if ($num_subprogramas>0) {
        $grupo_subprograma = 0;
        $i = 0;
      
      foreach ($nodes as $key => $value) {
        
        $nid = $value->nid->value;
        if ($grupo_subprograma == 0){   
             
          $field_linea_tid = $value->field_linea[0]->target_id;
          
          $subprograma = !empty($field_linea_tid)?$statistics->nombreTermino($field_linea_tid):"";
          $grupo_subprograma = !empty($field_linea_tid)?$field_linea_tid:"*";
          
        }elseif ($field_linea_tid == $grupo_subprograma){
            $field_linea_tid = $value->field_linea[0]->target_id;
            $subprograma = (!empty($field_linea_tid))?$statistics->nombreTermino($field_linea_tid):"";
            $grupo_subprograma = $field_linea_tid;
          }else{
            
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

            $field_linea_tid = $value->field_linea[0]->target_id;
            $subprograma = (!empty($field_linea_tid))?$statistics->nombreTermino($field_linea_tid):"";
            
            
            $grupo_subprograma = $field_linea_tid;

          }

          $field_numero_asistentes_0_5_  = $value->field_numero_asistentes_0_5_->value;
          $field_numero_asistentes_6_12_ = $value->field_numero_asistentes_6_12_->value;
          $field_numero_asistentes_13_18_= $value->field_numero_asistentes_13_18_->value;
          $field_numero_asistentes_19_27_= $value->field_numero_asistentes_19_27_->value;
          $field_numero_asistentes_28_60 = $value->field_numero_asistentes_28_60->value;
          $field_numero_asistentes_61_mas= $value->field_numero_asistentes_61_mas->value;
          $field_numero_asistentes = $value->field_numero_asistentes->value;
          $field_participantes_de_genero_ma = $value->field_participantes_de_genero_ma->value;
          $field_participantes_de_genero_fe = $value->field_participantes_de_genero_fe->value;
          $field_participantes_de_genero_tr = $value->field_participantes_de_genero_tr->value;

          $output_reporte[$field_linea_tid][] = array(
              'biblioteca' => "",//$statistics->nombreTermino($field_biblioteca),
              'subprograma' => $subprograma, 
              'total' => $field_numero_asistentes,
              'cero5'  => $field_numero_asistentes_0_5_,
              'doce'=> $field_numero_asistentes_6_12_,  
              'diez7' => $field_numero_asistentes_13_18_,
              'veinte8' => $field_numero_asistentes_19_27_,
              'cinco9' => $field_numero_asistentes_28_60,
              'sesenta' => $field_numero_asistentes_61_mas,
              'masc' => $field_participantes_de_genero_ma,
              'fem' => $field_participantes_de_genero_fe,
              'transg' => $field_participantes_de_genero_tr,
            );

        } // foreach
        

        foreach ($output_reporte as $key => $value2) {
            
            $total = 0;
            foreach ($value2 as $key2 => $value3) {
              $subprograma = $value3['subprograma'];
              $total += $value3['total'];
              $cero5 += $value3['cero5'];
              $doce += $value3['doce'];
              $diez7 += $value3['diez7'];
              $veinte8 += $value3['veinte8'];
              $cinco9 += $value3['cinco9'];
              $sesenta += $value3['sesenta'];
              $masc += $value3['masc'];
              $fem += $value3['fem'];
              $transg += $value3['transg'];
            }
            
            $output_final[$key] = array(
              'biblioteca' => "",//$statistics->nombreTermino($field_biblioteca),
              'subprograma' => $subprograma,
              'total' => $total,
              'cero5'  => $cero5,
              'doce'=> $doce,  
              'diez7' => $diez7,
              'veinte8' => $veinte8,
              'cinco9' => $cinco9,
              'sesenta' => $sesenta,
              'masc' => $masc,
              'fem' => $fem,
              'transg' => $transg,
            );
            
        }

    } // if
    } // data
      
      $form_state->set('field_count',$output_final);
      
      $form_state->setRebuild(); // Esta es la clave

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

  function getSubprogramas($form, FormStateInterface $form_state) {
    $statistics = new FuncionesController;
    $form['dep']['subprogramas']['#options'] = $statistics->subprogramas($form_state->getValue('programas'));
    
    return $form['dep']['subprogramas'];
  }

  function getBibliotecas($form, FormStateInterface $form_state) {

    $statistics = new EvEndpoint;
    $options = $statistics->bibliotecas_sistema($form_state->getValue('biblioteca'));  
    $form['biblioteca_2']['#options'] = $options;
    
    return $form['biblioteca_2'];
  }


}