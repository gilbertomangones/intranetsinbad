<?php
/**
* @file
* Contains Drupal\agendaform\form\MallainternaForm
*/
namespace Drupal\biblored_module\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\Core\Controller\ControllerBase;
use Drupal\biblored_module\Controller\EvEndpoint;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Url;
use Drupal\Core\Link;



/**
* 
*/
class MallainternaForm extends FormBase
{

/**
* {@inheritdoc}
*/
public function getFormId() {
  return 'biblored_module_mallainternaform'; //nombremodule_nombreformulario
}

/**
* {@inheritdoc}
*/

public function buildForm(array $form, FormStateInterface $form_state) {
 
  global $base_url;
  $host_agenda = \Drupal::config('Configuraciones.settings')->get('uribase');
  $output = array();
  $statistics = new EvEndpoint;
  $conc ="";
  $base_url_parts = parse_url($base_url); 
  $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
  
  // BIBLIOTECAS CON CODIGOS DE AGENDA
  $vid = 'nodos_bibliotecas';
  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
  $bibliotecas[0] = "Ninguno";  
  foreach($terms as $term) {    
       //$tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];  
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
 
  $vid_linea = 'areas';
  // Obtener solo los tid del nivel 1  
  $terms_lineas =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_linea, $parent = 0, $max_depth = 1, $load_entities = FALSE);
  $lineas[0] = "Ninguno";
  foreach($terms_lineas as $linea) {
    $tid_linea_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($linea->tid)->get('field_tid_linea_agenda');
    
    $linea_agenda = $tid_linea_agenda->getValue();
    
    $termino = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($linea->tid);
    $categoria_actividad = $termino->get('field_categoria_actividad')->getValue(); //field_categoria_actividad->value;
    $estado = $termino->field_suprimir_activar->value;

    $sw = 0;
    // Mostrar solo los misionales
    foreach ($categoria_actividad  as $value) {
      if ($value['target_id'] == '1274') {
          $sw = 1;
        
          break;
        }
    }
  
  /*$term_data_linea[] = array(
                 "id" => $linea->tid,
                 "name" => $linea->name,
                 'tid_linea_agenda' => $tid_linea_agenda,
             );*/
  
    if (($estado == 0 && $sw ==1)){
      
        //$tid_linea_agenda = $linea_agenda[0]['value'];
       
        $lineas[$linea->tid] = $linea->name;    
     }

  }

  $form['linea'] = array (
     '#type' => 'select',
     '#title' => ('Línea Misional'),
     '#options' => $lineas,
     '#validated' => TRUE,
   );

   // Adicionar solo las bibliotecas que tengan equivalencia (tid_biblioteca_agenda != null) en el vocabulario nodo_bibliotecas

   $form['biblioteca'] = array(
         '#type' => 'select',
         '#title' => t('Espacio'),
         '#validated' => TRUE,
         '#options' => $bibliotecas,
         '#ajax' => [
            'callback' => '::getBibliotecas',
            'wrapper' => 'bibliotecas-wrapper',
            'method' => 'replace',
            'effect' => 'fade',
          ],
        );
   /*
   if (!empty($form_state->getValue('biblioteca'))) {
    $form['biblioteca']['#default_value'] = $form_state->getValue('biblioteca');
    $form['biblioteca']['#ajax'] = [
            'callback' => '::getBibliotecas',
            'wrapper' => 'bibliotecas-wrapper',
            'method' => 'replace',
            'effect' => 'fade',
          ];
   }*/
   $tipo_espacio = $form_state->getValue('biblioteca');
  switch ($tipo_espacio) {
      case '2077':
        $name_espacio = 'Nombre estrategia móvil';
        break;
      case '352':
        $name_espacio = 'Nombre biblioteca';
        break;
      case '353':
        $name_espacio = 'Nombre bibloestación';
        break;
      case '354':
        $name_espacio = 'Nombre PPP';
        break;
      case '645':
        $name_espacio = 'Nombre Ruralidad';
        break;
      default:
        $name_espacio = 'Nombre';
        break;
    }
   $form['biblioteca_2'] = [
          '#type' => 'select',
          '#title' => $name_espacio,
          '#required'=> TRUE,
        //adicionada de siau  
        //'#options' => $this->getOptionsBibliotecas($form_state),  
          '#validated' => TRUE,
          //'#default_value' => isset($form_state->getValues()['biblioteca_2'])?$form_state->getValues()['biblioteca_2']:"",
          //'#options' =>  array(1=>"Demo"),
          //'#empty_option' => $this->t('Bibliotecas'),
          '#prefix' => '<div id="bibliotecas-wrapper">',
          '#suffix' => '</div>',
      ];
      /*
  if (!empty($form_state->getValue('biblioteca_2'))) {
    $form['biblioteca_2']['#default_value'] = $form_state->getValue('biblioteca_2');
   } */

  for ($i=1; $i <= 12; $i++) { 
   switch ($i) {
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
   }
    if ($i<=9 && $i >0){
      $month['0'.$i] = $label_mes;
    }else{
      $month[$i] = $label_mes;
    }
  }

  $form['month'] = array (
     '#type' => 'select',
     '#title' => ('Mes'),
     '#options' => $month,
     '#required'=> TRUE,
   );

  if ($form_state->getValue('month')){
  $form['month']['#default_value'] = $form_state->getValue('month');
  }
  for ($i=2018; $i <= date('Y', strtotime('+1 year')); $i++) { 
    $year[$i] = $i; 
  }
   $form['year'] = array (
     '#type' => 'select',
     '#title' => ('Año'),
     '#options' => $year,
     '#required'=> TRUE,
   );
   if ($form_state->getValue('year')) {
    $form['year']['#default_value'] = $form_state->getValue('year');
   }
  
   $form['actions'] = [
        '#type' => 'actions',
        'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Enviar'),
        ],
    ];

$host_local = $host_agenda;//$base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
$planactual = $host."/json/planactual";
 
 // EN CASO QUE LA CONSULTA DEVUELVA RESULTADO O TRAIGA DE LA AGENDA, MOSTRAR 
//if ($form_state->getValue('biblioteca') && $form_state->getValue('linea')) {
if ($form_state->getValue('biblioteca')) {
  $output_planactual = $statistics->serviciojson($planactual);
  $tid_planactual = $output_planactual[0]['field_concesion'];
  $resultados = $form_state->get('field_count');
  //$resultados_nodos_existentes = $form_state->get('field_count_existe');
  $form_state->set('field_count', $resultados);
  //$form_state->set('field_count_existe', $resultados_nodos_existentes);
  $header = [
      'id' => t('ID'),
      'actividad' => t('Actividad'),
      'biblioteca' => t('Biblioteca'),
      'fecha' => t('Fecha'),
      'hora' => t('Hora'),
      'descripcion' => t('Descripción'),
      'programa' => t('Programa'),
    ]; 
  $sumtime = 0;
  //var_dump($resultados);
  $cont = 0;
  foreach ($resultados as $key => $result) {
    $time_start = microtime(true);
    // PREGUNTAR SI EXISTE (PROGRAMA, FECHA COMPLETA, BIBLIOTECA) EN EL T. CONTENIDO ACT. EJEC    
       $id_agenda  = $result['nid'];
       
       $bib_equiv  = $result['field_biblioteca'];
       $prog_equiv = $result['field_linea'];  
  	   $franja = $result['field_franja'];
  	   $grupopoblacional = $result['field_grupo_poblacional'];
  	   
  
       $fecha_equiv = $result['field_fecha_plan_operativo'];
       $descripcion = urlencode($result['field_descripcion_corta']);
       $titulo = urlencode($result['title']);
       /*
       $tipoactividad = $result['field_tipo_de_actividad'];
       $franja = $result['field_publico'];
       $field_hora_ini = $result['field_hora_ini'];
       */
       $fecha_evento = $result['field_fecha_plan_operativo'];
       
       $tid_programa = "";
       $line = "";
       $tid_bib = "";
      // SABER EL ID DEL PROGRAMA EN LOCAL (PROGRAMA AGENDA == PROGRAMA LOCAL)
      // $programa_local = $host."/json/programa?tid=".$prog_equiv;  
       // echo $programa_local;
       //$output_prog = $statistics->serviciojson($programa_local);
       
       $error_agenda =  false;
       $revision = "";
       // capturar programa predeterminado de cada programa
       
  	   $conc = "&edit[field_concesion][widget]=".$result['field_plan'];
      
   /*
      if ($franja == '275')
            $tipofranja = 113; // Adolescentes (13 a 17 años) 
      elseif ($franja == '276')
            $tipofranja = 113; // Adultos (29-59 años)
      elseif ($franja == '71')
            $tipofranja = 113; // Jóvenes (18 a 28 años)
      elseif ($franja == '70')
           $tipofranja = 79; // Primera Infancia (0-5 años)
      elseif ($franja == '72')
             $tipofranja = 111; // Público Infantil (6-12 años)
      elseif ($franja == '73')
            $tipofranja = 114; // Personas mayores (60 años en adelante) 
      elseif ($franja == '159')
            $tipofranja = 116; // Toda la familia
      elseif ($franja == '158')
            $tipofranja = 112; // Todo público
       
       $act = "";
       switch ($tipoactividad) {
         case '262':
            $actividad = 69;
            $act = "&edit[field_tipo_actividad_relizada]=".$actividad;
           break;
         case '263':
            $actividad = 68;
            $act = "&edit[field_tipo_actividad_relizada]=".$actividad;
           break;
          default:
            $actividad = 0;
       }
       
       switch ($bib_equiv) {
         case '22': // Venecia *
           $tid_bib = 53;
           break; 
         case '21': // Usaquen *
           $tid_bib = 63;
           break;  
           case '20': // Sumapaz *
           $tid_bib = 59;
           break;  
           case '19': // Suba *
           $tid_bib = 62;
           break;  
           case '18': // Rafael uribe *
           $tid_bib = 49;
           break;  
           case '16': // Perdomo *
           $tid_bib = 58;
           break;  
           case '17': // Puente aranda *
           $tid_bib = 50;
           break;  
           case '160': // Marichuela *
           $tid_bib = 117;
           break;  
           case '13': // La victoria *
           $tid_bib = 51;
           break;  
           case '12': // Peña *
           $tid_bib = 52;
           break;  
           case '23': // Virgilio *
           $tid_bib = 66;
           break;  
           case '11': // Giralda *
           $tid_bib = 67;
           break;  
           case '15': // Ferias *
           $tid_bib = 65;
           break;  
           case '14': // timiza *
           $tid_bib = 54;
           break;  
           case '10': // Julio mario santo domingo *
           $tid_bib = 61;
           break;  
           case '9': // Tunal Gabriel Garcia *
           $tid_bib = 60;
           break;  
           case '8': // Tintal *
           $tid_bib = 55;
           break; 
           case '161': // Parque *
           $tid_bib = 290;
           break; 
           case '7': // Deòprtes campin *
           $tid_bib = 64;
           break; 
           case '6': // restrepo *
           $tid_bib = 48;
           break; 
           case '5': // Bosa *
           $tid_bib = 56;
           break; 
           case '4': // Arborizadora *
           $tid_bib = 57;
           break; 
           case '192': // Pasquilla *
           $tid_bib = 473;
           break;
           case '388': // biblioteca en mi casa  *
           $tid_bib = 954;
           break;
           case '515': // Participacion  *
           $tid_bib = 1762;
           break;
           case '523': // el mirador  *
           $tid_bib = 1806;
           break; 
       }
       */
       $existe  = "";      

       // VALIDAR SI ACTIVIDAD EXISTE DENTRO DEL ARRAY DE ESTADISTICAS
       /*
       $query = \Drupal::entityQuery('node')
          ->condition('status', 1) //published or not
          ->condition('type', 'actividad_ejecutada') //content type
            ->condition('field_id_actividad_agenda', $id_agenda);
        $nids = $query->execute();
       */
  	   //$output_prog = $statistics->serviciojson($programa_local);
  	   
       $error_agenda =  false;
       $revision = "";
  	   // capturar programa predeterminado de cada programa
       /*
       if (!empty($output_prog)){
       	$term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($output_prog[0]['tid']);
        plan_pred = $term->get('field_plan')->getValue();
        $plan = isset($plan_pred[0]) ? $plan_pred[0]['target_id']: '';
          if($plan){
              $conc = "&edit[field_concesion][widget]=".$plan;
          }
       }
       */
		$tid_linea = $result['field_linea_id'];
        $tid_biblioteca = $result['field_biblioteca_id'];
  		$line = "&edit[field_linea][widget]=".$tid_linea;
  		$fecha_ingresada = date('Y-m-d', strtotime($fecha_evento));
  		$fecha_act = "&edit[field_fecha_realizada_act][widget][0][value][date]=".$fecha_ingresada;
  		
  		$modalidad = $result['field_canal'];
  		if ($modalidad == 'presencial') {
        	$mod = "&modalidad=1423";
        }else{
        	$mod = "&modalidad=1424";
        }
  		$hora_inicio = $result['field_hora_inicio'];

		$hhmmss = '00:00:00';
  		switch ($hora_inicio) {
  			case '6:00 am':
  				$hhmmss = '06:00:00';
  				break;
  			case '6:30 am':
  				$hhmmss = '06:30:00';
  				break;
  			case '7:00 am':
  				$hhmmss = '07:00:00';
  				break;
  			case '7:30 am':
  				$hhmmss = '07:30:00';
  				break;
  			case '8:00 am':
  				$hhmmss = '08:00:00';
  				break;
  			case '8:30 am':
  				$hhmmss = '08:30:00';
  				break;
  			case '9:00 am':
  				$hhmmss = '09:00:00';
  				break;
  			case '10:00 am':
  				$hhmmss = '10:00:00';
  				break;
  			case '10:30 am':
  				$hhmmss = '10:30:00';
  				break;
  			case '11:00 am':
  				$hhmmss = '11:00:00';
  				break;
  			case '11:30 am':
  				$hhmmss = '11:30:00';
  				break;
  			case '12:00 pm':
  				$hhmmss = '12:00:00';
  				break;
  			case '12:30 pm':
  				$hhmmss = '12:30:00';
  				break;
  			case '1:00 pm':
  				$hhmmss = '13:00:00';
  				break;
  			case '1:30 pm':
  				$hhmmss = '13:30:00';
  				break;
  			case '2:00 pm':
  				$hhmmss = '14:00:00';
  				break;
  			case '2:30 pm':
  				$hhmmss = '14:30:00';
  				break;
  			case '3:00 pm':
  				$hhmmss = '15:00:00';
  				break;
  			case '3:30 pm':
  				$hhmmss = '15:30:00';
  				break;
  			case '4:00 pm':
  				$hhmmss = '16:00:00';
  				break;
  			case '4:30 pm':
  				$hhmmss = '16:30:00';
  				break;
  			case '5:00 pm':
  				$hhmmss = '17:00:00';
  				break;
  			case '5:30 pm':
  				$hhmmss = '17:30:00';
  				break;
  			case '6:00 pm':
  				$hhmmss = '18:00:00';
  				break;
  			case '6:30 pm':
  				$hhmmss = '18:30:00';
  				break;
  			case '7:00 pm':
  				$hhmmss = '19:00:00';
  				break;
  			case '7:30 pm':
  				$hhmmss = '19:30:00';
  				break;
  			case '8:00 pm':
  				$hhmmss = '20:00:00';
  				break;
  			default:
  				$hhmmss = '00:00:00';
  				break;
  		}
  		$fecha_act_time = "&edit[field_fecha_realizada_act][widget][0][value][time]=".$hhmmss;
  		
        $link_actividad = $host."/node/add/actividad_ejecutada/?edit[title][widget][0][value]=".$titulo."&edit[field_biblioteca][widget]=". $tid_biblioteca.$line.$conc.$fecha_act.$fecha_act_time.$mod."&field_franja=".$franja."&edit[field_fecha_programada_agenda][widget][0][value]=".$fecha_evento."&edit[body][widget][0][value]=".$descripcion."&idmalla=".$id_agenda."&edit[field_grupo_poblacional][widget]=".$grupopoblacional;

        //$link_actividad = $host."/node/add/actividad_ejecutada/?edit[title][widget][0][value]=".$titulo."&edit[field_biblioteca][widget]=". $tid_bib.$line.$conc."&edit[field_id_actividad_agenda][widget][0][value]=".$id_agenda."&edit[field_fecha_programada_agenda][widget][0][value]=".$fecha_evento.$act."&edit[field_franja][widget]=".$tipofranja."&edit[body][widget][0][value]=".$descripcion;
        $url = Url::fromUri($link_actividad);
        
 
        $link_options = array(
          'attributes' => array(
            'class' => array(
              //'use-ajax',
              'my-second-class mimalla',
            ),
            //'data-dialog-type'=>'modal',
          ),
        );
        $url->setOptions($link_options);              
        $link = Link::fromTextAndUrl(t( $result['title'].$revision ), $url )->toString();   
         
        $plazo = \Drupal::config('Configuraciones.settings')->get('plazomalla');
         
         $sw = 0;
         $actual = strtotime(date("Y-n-d"));
         
         $mes_anterior_ = date("Y-n-d", strtotime("-1 month", $actual));

         $mes_anterior = date("n", strtotime($mes_anterior_));

         $mes_actual = date("n");

         $mes_seleccionado   = $form_state->getValue('month');
         
         $annio_seleccionado = $form_state->getValue('year');

         

         if (date("j") <= $plazo) {
           // Se puede editar el mes actual o mes anterior
           //if ($mes_seleccionado <= $mes_anterior || $mes_seleccionado == $mes_actual) {
           if ($mes_seleccionado == $mes_anterior || $mes_seleccionado == $mes_actual) {
             $output[] = [
               'id'=>$result['nid'],
               'actividad' =>$link,
               'biblioteca' => $result['field_biblioteca'], 
               'fecha' => $result['field_fecha_plan_operativo'],
               'hora' => $result['field_hora_inicio'],
               'descripcion' => $result['field_descripcion_corta'],
               'programa' => $result['field_linea'],
             ];
           }else{
             $link = $result['title'];
             $output[] = [
               'id'=>$result['nid'],
               'actividad' =>$link . " (Vencido)",
               'biblioteca' => $result['field_biblioteca'],  
               'fecha' => $result['field_fecha_plan_operativo'], 
               'hora' => $result['field_hora_inicio'],
               'descripcion' => $result['field_descripcion_corta'],
               'programa' => $result['field_linea'],
             ];
             $mensaje = "Tiempo caducado";
           }
         }else{
           if ($mes_seleccionado  == date("n")){
              
              $output[] = [
               'id'=>$result['nid'],
               'actividad' =>$link,
               'biblioteca' => $result['field_biblioteca'], 
               'fecha' => $result['field_fecha_plan_operativo'],
               'hora' => $result['field_hora_inicio'],
               'descripcion' => $result['field_descripcion_corta'],
               'programa' => $result['field_linea'],
             ];  
           }else{
             //$link = $result['title'];
             $output[] = [
               'id'=>$result['nid'],
               'actividad' =>$link,
               'biblioteca' => $result['field_biblioteca'], 
               'fecha' => $result['field_fecha_plan_operativo'],
               'hora' => $result['field_hora_inicio'],
               'descripcion' => $result['field_descripcion_corta'],
               'programa' => $result['field_linea'],
             ];
             $mensaje = "Tiempo caducado";
           }
         }
        
        
      $cont++;
    }     
        $form['counting'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => 'Número de registros encontrados:' . $cont,
    ];
        $form['table'] = [
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $output,
        '#empty' => t('No actividades encontradas'),
        ];
    
}

 return $form;
}

/**
* {@inheritdoc}
*/
public function validateForm(array &$form, FormStateInterface $form_state) {
  if ($form_state->getValue('biblioteca_2') == 0 || $form_state->getValue('biblioteca_2') == "") {
    $form_state->setErrorByName('biblioteca_2', $this->t('Seleccionaer una biblioteca'));
  }
}

/**
* {@inheritdoc}
*/
public function submitForm(array &$form, FormStateInterface $form_state) {
  
  $host_agenda = \Drupal::config('Configuraciones.settings')->get('uribase');
  $statistics = new EvEndpoint;
  global $base_url;
  $base_url_parts = parse_url($base_url);
  $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
  
  $tid_biblioteca_agenda = $form_state->getValue('biblioteca_2');
  $tid_biblioteca_espacio = $form_state->getValue('biblioteca');

  $tid_linea_agenda = $form_state->getValue('linea');

  $programa_local = $host."/json/programa?tid=".$tid_linea_agenda;
  $biblioteca_local = $host."/json/bibliotecas?tid=".$tid_biblioteca_agenda;
  
  $mes =  $form_state->getValue('month');
  $year =  $form_state->getValue('year');
  
  $output = "";


  $programa_seleccionado = $statistics->serviciojson($programa_local);
  $bib_seleccionada = $statistics->serviciojson($biblioteca_local);
  
  //$tid_biblioteca_agenda = 51;
  //$tid_linea_agenda = 1593;
  $uri =  $host."/getcontentmallanoagenda/".$tid_biblioteca_agenda."/".$tid_linea_agenda."/".$year.$mes;
  
	
  //$uri =  $host."/getcontentmallanoagenda/".$year.$mes;

  //Debe buscar por año y mes
  //$uri_existe_node =  $host."/json/localagenda/".$bib_seleccionada[0]['tid']."/".$programa_seleccionado[0]['tid']."/".$year.$mes;
  //$uri_existe_node =  $host."/json/localagenda/".$bib_seleccionada[0]['tid']."/".$year.$mes;
  
  $output = $statistics->serviciojson($uri);

  //$json = file_get_contents($uri_existe_node);
  //$jo = json_decode($json);
  
  $max = $form_state->getValue('biblioteca_2');
  $form_state->set('field_count',$output);
  //$form_state->set('field_count_existe',$output_existe);
  $form_state->setRebuild(TRUE); // Esta es la clave
  
}

function getBibliotecas($form, FormStateInterface $form_state) {

    $statistics = new EvEndpoint;
  $biblioteca = "";
  $options = "";
  
    //$options = $statistics->bibliotecas($form_state->getValue('biblioteca'));  
  $sw = 0;
    // Validar rol y biblioteca asignada, para obtener todas o la biblioteca asignada
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id()); // Usuario actual
    //$user = \Drupal\user\Entity\User::load(1333);
    $biblioteca = $user->get('field_biblioteca_o_nodo')->getValue();
  
    $cod_biblioteca = $biblioteca[0]['target_id'];
    
    $current_user = \Drupal::currentUser();

    $roles_excluidos = array("authenticated", "administrator");
    $roles = $current_user->getRoles();
    //var_dump($roles); Ludotecario
    foreach ($roles as $key => $value) {
      if ($value == 'promotores_biblioteca' || 
          $value == 'coordinador_biblioteca' || 
          $value == 'profesional_biblioteca' || $value == 'administrator')
           {
          // Para el caso que hay usuarios al cual no se les ha asignado una biblioteca
          $sw = 1;
      } // Fin If
    }//Fin foreach

    if ($sw == 1){
      //if (!empty($cod_biblioteca)){
      if (!empty($biblioteca)){
        $espacio = $form_state->getValue('biblioteca');
        //echo "Biblioteca asignada:".$cod_biblioteca;      
        //$options = $statistics->bibliotecas_asignadas($form_state->getValue('biblioteca'), $cod_biblioteca);
        $options = $statistics->bibliotecas_sistema_asignada($espacio, $biblioteca);
        //\Drupal::logger('biblored_module')->notice("111");

      }else{
        // \Drupal::logger('biblored_module')->notice("1112");
        //echo "biblioteca no asignada";
        //$options = $statistics->bibliotecas($form_state->getValue('biblioteca'));
      }
    }else{
      $options = $statistics->bibliotecas($form_state->getValue('biblioteca'));
      \Drupal::logger('biblored_module')->notice("");
    }
    $form['biblioteca_2']['#options'] = $options;
    
    return $form['biblioteca_2'];
  }

public function changeOptionsAjax(array &$form, FormStateInterface $form_state) {
return $form['second_field'];
}

public function getOptions(FormStateInterface $form_state) {
 
$vid_programas = 'areas';
$linea = $form_state->getValue('linea');
$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_programas, $parent = $linea, $max_depth = NULL, $load_entities = FALSE);
if ($terms){ 
  foreach ($terms as $term) {
   $programas[$term->tid] = $term->name;
  }
   // ($form_state->getValue('linea') == '1')
  $options = $programas;
  return $options;
}

}
/**
* @description Getting event from ws agenda
*
**/
public function get($bib, $line, $mes, $anno) {

  $tid_biblioteca_agenda = 16;
  $tid_linea_agenda = 49;
  $mes = 11;
  $year = 2017;
  $uri =  "https://desarrollo.biblored.gov.co/api-agenda/eventos/".$tid_biblioteca_agenda."/".$tid_linea_agenda."/".$mes."/".$year;
  
  // Obtener tid bibliotca en Estadistica
  // 1. Codigo tid (Agenda) a buscar
  // 2. query en Estadisticas para obtener el equivalente Biblioteca en Estadística.
  $query = \Drupal::database()->select('taxonomy_term_field_data', 'tax');
  $query->fields('tax', ['tid']);
  $query->join('taxonomy_term__field_tid_biblioteca_agenda', 'ufd', 'ufd.entity_id = tax.tid');
  $query->condition('ufd.field_tid_biblioteca_agenda_value', $tid_biblioteca_agenda, '=');
  $tid_biblioteca = $query->execute()->fetchAssoc();

  // Inicio proceso equivalencia de programas de lineas 
  
  $query_linea = \Drupal::database()->select('taxonomy_term_field_data', 'tax');
  $query_linea->fields('tax', ['tid']);
  $query_linea->join('taxonomy_term__field_tid_linea_agenda', 'ufd', 'ufd.entity_id = tax.tid');
  $query_linea->condition('ufd.field_tid_linea_agenda_value', $tid_linea_agenda, '=');
  $tid_linea = $query_linea->execute()->fetchAssoc();

  // Fin proceso equivalencia de programaas de líneas

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

  $output = Json::decode($data);  
  $biblio = 'query';
  $element['#contenido'] = $output;
  $element['#biblioteca'] = $tid_biblioteca;
  $element['#lineamisional'] = $tid_linea;
  //$element['#bibliotca'] = $tid_biblioteca['tid'];
  $element['#theme'] = 'my_theme';
  return $element;

}

}
?>