<?php

namespace Drupal\alertas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Database\Connection;
use \Drupal\Core\Database\Database;
use Drupal\biblored_module\Controller\EvEndpoint;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Markup;

/**a
 * An example controller.
 */
class AlertasEndpoint extends ControllerBase {
	/**
   * {@inheritdoc}
   */
  public function get() {
   global $base_url;
   $base_url_parts = parse_url($base_url); 
   $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
  
   $total = array();
    //$fechainicial = date("Y-m-01");
    //$fechafinal = date("Y-m-t");
    $fechainicial = date("2019-12-01");
    $fechafinal = date("2019-12-31");
  //echo $fechainicial;
  //echo $fechafinal;
    $fecha_busqueda = "fecha%5Bmin%5D=".$fechainicial."&fecha%5Bmax%5D=".$fechafinal;
  	$uri_bibliotecas =  $host . "/ws/alertasbibliotecas?".$fecha_busqueda;
  	$uri_bibloestaciones = $host . "/ws/alertasbibloestaciones?".$fecha_busqueda;
  	// Servicio de Malla
	$uri_malla_bibliotecas =  $host . "/ws/alertasplanmalla?".$fecha_busqueda;
    //echo $uri_malla_bibliotecas;
  	// Convertir json     
    try {
        $response = \Drupal::httpClient()->get($uri_bibliotecas, array('headers' => array('Accept' => 'text/plain')));
        $data = (string) $response->getBody();
        if (empty($data)) {
          return FALSE;
        }
      }
      catch (RequestException $e) {
        return FALSE;
      }
	  // Malla
	  try {
        $response = \Drupal::httpClient()->get($uri_malla_bibliotecas, array('headers' => array('Accept' => 'text/plain')));
        $data_malla = (string) $response->getBody();
        if (empty($data_malla)) {
          return FALSE;
        }
      }
      catch (RequestException $e) {
        return FALSE;
      }
    
  	$output = Json::decode($data);  
    $output_malla = Json::decode($data_malla);  
  
  $resultado = [];
	
  foreach($output as $dato) {
		$resultado[$dato['field_biblioteca']][] = $dato;
	}
  	$count = 0;
  
  	foreach ($resultado as $bib=>$record){
    	$alertas_act[$bib] = array('cantidad' => count($record), 'idbiblioteca' => $record[0]['field_biblioteca'], 'biblioteca'=>  $record[0]['field_biblioteca_1'] );
    	$total[$bib][0] = array('cantidad' => count($record), 'idbiblioteca' => $record[0]['field_biblioteca'], 'biblioteca'=>  $record[0]['field_biblioteca_1'] );
    }
  	//*********************//
    $resultado_malla = [];
	foreach($output_malla as $dato) {
		$resultado_malla[$dato['field_biblioteca']][] = $dato;
	}
  	$count = 0;
  	foreach ($resultado_malla as $bib=>$record1){
    	$alertas_malla[$bib] = array('cantidad' => count($record1), 'idbiblioteca' => $record1[0]['field_biblioteca'], 'biblioteca'=>  $record1[0]['field_biblioteca_1'] );
    	$total[$bib][1] = array('cantidad' => count($record1), 'idbiblioteca' => $record1[0]['field_biblioteca'], 'biblioteca'=>  $record1[0]['field_biblioteca_1'] );
    
    }


    $element['#contenido'] = $total;
    $element['#theme'] = 'theme_alertas';

    return $element;
  }
  /**
   * Obtener numero de asistentes para el caso de actividad_ejecutada
   * Obtener producto 1 (field_avance_meta_producto) para el caso de misionales
   */ 
  public function get_num_asistentes($plan, $program) {
    $result = 0;
    $term = Term::load($program);
  	
    if (!empty($term)) {
      $categoria = $term->get('field_categoria_actividad')->target_id;
      // Programas especiales
      if ($categoria == 1275) {
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_linea', 'fl', 'fl.entity_id = n.nid');
        $query->innerJoin('node__field_avance_meta_producto', 'fas', 'fas.entity_id = n.nid');
        $query->addExpression('sum(fas.field_avance_meta_producto_value)', 'suma');
        $query->condition('n.type', 'actividades_misionales');
        $query->condition('fdc.field_concesion_target_id ', $plan);
        $query->condition('fl.field_linea_target_id ', $program);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
      	
      } else {
        // Obtener cantidad de nodes dependiendo del Plan
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_linea', 'fl', 'fl.entity_id = n.nid');
        $query->innerJoin('node__field_numero_asistentes', 'fas', 'fas.entity_id = n.nid');
        $query->addExpression('sum(fas.field_numero_asistentes_value)', 'suma');
        $query->condition('n.type', 'actividad_ejecutada');
        $query->condition('fdc.field_concesion_target_id ', $plan);
        $query->condition('fl.field_linea_target_id ', $program);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
      }
    }
    return $result;
  }

  /**
   * Obtener numero de asistentes para el caso de actividad_ejecutada
   * Obtener producto 1 (field_avance_meta_producto) para el caso de misionales
   */ 
  public function get_num_asistentes_codprograma($plan, $program, $cod_accion) {
    $result = 0;
    $term = Term::load($program);
    if (!empty($term)) {
      $categoria = $term->get('field_categoria_actividad')->target_id;
      // Programas especiales
      if ($categoria == 1275) {
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_codigo_de_accion', 'fl', 'fl.entity_id = n.nid');
        $query->innerJoin('node__field_avance_meta_producto', 'fas', 'fas.entity_id = n.nid');
        $query->addExpression('sum(fas.field_avance_meta_producto_value)', 'suma');
        $query->condition('n.type', 'actividades_misionales');
        $query->condition('fdc.field_concesion_target_id', $plan);
        $query->condition('fl.field_codigo_de_accion_value', $cod_accion);
        $query->condition('n.status', 1);
        
        $result = $query->execute()->fetchAll();
        
      } else {
        // Obtener cantidad de nodes dependiendo del Plan
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_codigo_de_accion', 'fl', 'fl.entity_id = n.nid');
        $query->innerJoin('node__field_numero_asistentes', 'fas', 'fas.entity_id = n.nid');
        $query->addExpression('sum(fas.field_numero_asistentes_value)', 'suma');
        $query->condition('n.type', 'actividad_ejecutada');
        $query->condition('fdc.field_concesion_target_id', $plan);
        $query->condition('fl.field_codigo_de_accion_value', $cod_accion);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
      }
    }
    return $result;
  }
  /**
   * Obtener el numero de sesiones para el caso de Actividad_ejecutada
   * Obtener la sumatoria del campo proceso 1 (field_avance_meta_proceso)
   */
  public function get_num_sessions($plan, $program){
    // Getting category of program
    $result = 0;
    $term = Term::load($program);
    if (!empty($term)) {
      $categoria = $term->get('field_categoria_actividad')->target_id;

      //Queries
      // Programas especiales
      if ($categoria == 1275) {
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_linea', 'fl', 'fl.entity_id = n.nid');
        $query->innerJoin('node__field_avance_meta_proceso', 'fas', 'fas.entity_id = n.nid');
        $query->addExpression('sum(fas.field_avance_meta_proceso_value)', 'suma');
        $query->condition('n.type', 'actividades_misionales');
        $query->condition('fdc.field_concesion_target_id ', $plan);
        $query->condition('fl.field_linea_target_id ', $program);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
      }else {
        // Obtener cantidad de nodes dependiendo del Plan
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_linea', 'fl', 'fl.entity_id = n.nid');
        $query->addExpression('count(n.nid)', 'nid_count');
        $query->condition('n.type', 'actividad_ejecutada');
        $query->condition('fdc.field_concesion_target_id ', $plan);
        $query->condition('fl.field_linea_target_id ', $program);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
        
      }
    }
    
    return $result;
  }

  /**
   * Función para sql por codigo del programa
   */
  public function get_num_sessions_codprograma($plan, $program, $cod_accion){
    // Getting category of program
    $result = 0;
    $term = Term::load($program);
    if (!empty($term)) {
      $categoria = $term->get('field_categoria_actividad')->target_id;

      //Queries
      // Programas especiales
      if ($categoria == 1275) {
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_codigo_de_accion', 'fl', 'fl.entity_id = n.nid');
        $query->innerJoin('node__field_avance_meta_proceso', 'fas', 'fas.entity_id = n.nid');
        $query->addExpression('sum(fas.field_avance_meta_proceso_value)', 'suma');
      	$query->condition('n.type', 'actividades_misionales');
        $query->condition('fdc.field_concesion_target_id', $plan);
        $query->condition('fl.field_codigo_de_accion_value', $cod_accion);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
        
      }else {
        // Obtener cantidad de nodes dependiendo del Plan y codigo programa
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_codigo_de_accion', 'fl', 'fl.entity_id = n.nid');
        $query->addExpression('count(n.nid)', 'nid_count');
        $query->condition('n.type', 'actividad_ejecutada');
        $query->condition('fdc.field_concesion_target_id ', $plan);
        $query->condition('fl.field_codigo_de_accion_value', $cod_accion);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
        
      }
    }
    
    return $result;
  }
  public function get_programas_plan($plan, $linea, $avance_proceso, $avance_producto) {
    //$plan = $form_state->getValue('planes');
    //$linea = $form_state->getValue('linea');
    /*
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query->condition('status', 1);
    $query->condition('type', 'plan_de_accion_concesion');
    $query->condition('field_concesion', $plan);
    //$query->condition('field_lineaa', $linea);
    
    $programs_plans = $query->execute();
    
    $programs_storage = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($programs_plans);
    */
    // Werservice
    global $base_url;
  	$output = [];
    
    $predeterminados = new EvEndpoint;
    $base_url_parts = parse_url($base_url); 
    $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
    
    //$plan_actividades = "http://intranet.biblored.net/sinbad/json/planaccion/" . $plan . '/' . $linea;
    $plan_actividades = $host."/json/planaccion/" . $plan . '/' . $linea;
   
    //echo $plan_actividades;
    /*
    $json = file_get_contents($plan_actividades);
    $json_data = json_decode($json);
    header('Content-type:text/html;charset=utf-8');

    //$output = Json::decode($json_data); 
    */

    $programs_storage = $predeterminados->serviciojson($plan_actividades);
    //var_dump($programs_storage);
    $tiempo_concesion = Term::load($plan);
    $fec_inicio = $tiempo_concesion->field_fecha_inicial_plan_accion->value;
    $fec_fin = $tiempo_concesion->field_fecha_fin_plan_accion->value;
    
    $ts1 =strtotime($fec_inicio); 
    $ts2 = strtotime($fec_fin); 
    $year1 = date('Y', $ts1); 
    $year2 = date('Y', $ts2); 
    $month1 = date('m', $ts1); 
    $month2 = date('m', $ts2); 
    $array_meses = [];

    $diff_num_meses = (($year2 - $year1) * 12) + ($month2 - $month1) + 1; // Numero de meses
    
    $inicial = (int)$month1;
    for ($i=1; $i <= $diff_num_meses; $i++) { 
      $array_meses[$inicial] = $i;
      $inicial++;
      if ($inicial >12){
        $inicial = 1;
      }
    }
    
    $mes_actual = date('n');
    
    $multiplo = isset($array_meses[$mes_actual]) ? $array_meses[$mes_actual] : null;
	//var_dump($programs_storage);
    foreach ($programs_storage as $node) {
       
      //$tid = $node->get('field_linea')->target_id; 
      $tid = $node['tid']; //Tid línea misional
      $name = $node['field_linea']; // nombre linea misional
      $name_accion = $node['field_linea_1']; // Nombre de la acción o programa 
      $categoria = $node['field_categoria_actividad']; // tid categria actividad
      $tipo = $node['field_categoria_actividad_1']; // Name categoria
      $codigo_programa = $node['field_backup_codigo_accion']; //código del programa alamcenado en el plan
      //$term = Term::load($tid);
     /* Cómo calcular?
      * 1|Avance
		2|Porcentaje
		3|Reportes
		4|I1 / I2
        5|Suma de contenidos
      */
      	$como_calcular_proceso_1 =  $node['field_como_calcular'];
        
      	$como_calcular_proceso_2 =  $node['field_como_calcular_impacto'];
    	$como_calcular_prducto_1 =  $node['field_como_calcular_producto'];
    	$como_calcular_producto_2 =  $node['field_como_calcular_producto_2'];
    	// Solo para el caso de codigo de programas agrupados en plan de acción
    	if ($como_calcular_proceso_1 == 5 || $como_calcular_proceso_2 == 5 || $como_calcular_prducto_1 == 5 || $como_calcular_producto_2 == 5) {
      		// Sumatoria de los avances ingresados
      		$meta_session_prog = $this->get_num_sessions_codprograma($plan, $tid, $codigo_programa); // Sumatoria de Proceso 1
      		$meta_num_asistentes = $this->get_num_asistentes_codprograma($plan, $tid, $codigo_programa); // Sumatoria producto 1
      		$meta_procesos_2 = $this->get_sum_proceso_2_codprograma($plan, $tid, $codigo_programa); // Sumatoria de procesos 2
      		$meta_productos_2 = $this->get_sum_producto_2_codprograma($plan, $tid, $codigo_programa); // Sumatoria de productos 2  
        }else {
      		// Sumatoria de los avances ingresados
            
      		$meta_session_prog = $this->get_num_sessions($plan, $tid); // Sumatoria de Proceso 1
      		$meta_num_asistentes = $this->get_num_asistentes($plan, $tid); // Sumatoria producto 1
        	$meta_procesos_2 = $this->get_sum_proceso_2($plan, $tid); // Sumatoria de procesos 2
      		$meta_productos_2 = $this->get_sum_producto_2($plan, $tid); // Sumatoria de productos 2
        }
      //Categoria especial o normal
      
      $suma_asist = '';
      $suma = '';
      $suma_proceso_2 = '';
      $suma_producto_2 = '';
      
      //1275, son programas o acciones con categoría de especiales.
      if ($categoria == 1275){
        if (isset($meta_num_asistentes[0]) && !empty($meta_num_asistentes[0]->suma)) {
          $suma_asist = $meta_num_asistentes[0]->suma;
          //$suma_asist = number_format($suma_asist, 2);
        }

        if (isset($meta_session_prog[0]) && !empty($meta_session_prog[0]->suma)) {
          $suma= $meta_session_prog[0]->suma;
          $suma = number_format(($suma), 2);
        } 
      
        if (isset($meta_procesos_2[0]) && !empty($meta_procesos_2[0]->suma)) {
          $suma_proceso_2= $meta_procesos_2[0]->suma;
          $suma_proceso_2 = number_format(($suma_proceso_2), 2);
        }
      
        if (isset($meta_productos_2[0]) && !empty($meta_productos_2[0]->suma)) {
          $suma_producto_2 = $meta_productos_2[0]->suma;
          $suma_producto_2 = number_format(($suma_producto_2), 2);
        }
      	
      } else {
        $suma = isset($meta_session_prog[0]) ? $meta_session_prog[0]->nid_count : '';
      	if (isset($meta_num_asistentes[0]) && !empty($meta_num_asistentes[0]->suma)) {
          $suma_asist = $meta_num_asistentes[0]->suma;
          $suma_asist = number_format($suma_asist,2, '.', ''); ///number_format(($suma_asist), 2); 
        }
      	if (isset($meta_procesos_2[0]) && !empty($meta_procesos_2[0]->suma)) {
          $suma_proceso_2= $meta_procesos_2[0]->suma;
          $suma_proceso_2 = number_format(($suma_proceso_2), 2);
        }
        if (isset($meta_productos_2[0]) && !empty($meta_productos_2[0]->suma)) {
          $suma_producto_2 = $suma_producto_2[0]->suma;
          $suma_producto_2 = number_format(($suma_producto_2), 2);
        }
      }
      
      //$meta_proceso = $node->get('field_proc_interna')->value + $node->get('field_proc_externo')->value;
      $bk_codigo_accion = $node['field_backup_codigo_accion'];
      $field_proc_interna = !empty($node['field_proc_interna']) ? $node['field_proc_interna'] : 0;
      $field_proc_externo = !empty($node['field_proc_externo']) ? $node['field_proc_externo'] : 0;
      $meta_proceso = $field_proc_interna + $field_proc_externo; // proceso 1
      $meta_proceso_2 = !empty($node['field_impacto_planeado']) ? $node['field_impacto_planeado'] : 0; // proceso 2
      $meta_proceso_presente = ($meta_proceso / $diff_num_meses) * $multiplo;
      //$ind_proceso = "ind" . $node->get('field_indicador_proceso')->value;
      $ind_proceso = $node['field_indicador_proceso'];
      //$meta_producto = $node->get('field_prod_interno')->value + $node->get('field_prod_externo')->value;
      $field_prod_interno = !empty($node['field_prod_interno']) ? $node['field_prod_interno'] : 0;
      $field_prod_externo = !empty($node['field_prod_externo']) ? $node['field_prod_externo'] : 0;
      $meta_producto = floatval($field_prod_interno) + floatval($field_prod_externo); // prod 1
      $meta_producto_2 = !empty($node['field_producto_2']) ? $node['field_producto_2'] : 0; // prod 2
      //$meta_producto_presente = ($meta_producto / $diff_num_meses) * $multiplo;
      
      //$ind_producto = "Ind";//$node->get('field_indicador_producto')->target_id;
      $ind_producto = $node['field_indicador_producto'];
      
      $field_indicador_proceso_2 = $node['field_indicador_impacto']; // indicador proceso 2
      $field_indicador_producto_2 = $node['field_indicador_producto_2']; // Indicador producto 2
      
	  $avance_proceso_porcentaje = 0;
      $avance_producto_porcentaje = 0;
      $avance_proceso_2_porcentaje = 0;
      $avance_producto_2_porcentaje = 0;
    
     	
    	
    	// Proceso 1
        
    	switch ($como_calcular_proceso_1) {
        case '1':
        	// Avance con la meta
        	$avance_proceso_porcentaje = ($meta_proceso != 0) ? number_format((floatval($suma) / floatval($meta_proceso)) * 100,2) : '';
          break;
        case '2':
          // Avance / Mostrar solo la sumatoria de %
        	$avance_proceso_porcentaje = ($meta_proceso != 0) ? number_format((floatval($suma))) : '';	 // Mostrar solo la sumatoria de %
          break;
        case '4':
          // Avance / con metas
        	$avance_proceso_porcentaje = ($meta_proceso != 0) ? number_format((floatval($suma))) : '';	 // Mostrar solo la sumatoria de %
          break;
        default:
          $avance_proceso_porcentaje = ($meta_proceso != 0) ? number_format((floatval($suma))) : '';	 // Mostrar solo la sumatoria de %
          break;
      	}
    
    	switch ($como_calcular_proceso_2) {
         case '1':
        	// Avance con la meta
        	$avance_proceso_2_porcentaje = ($meta_proceso_2 != 0) ? number_format((floatval($suma_proceso_2) / floatval($meta_proceso_2)) * 100,2) : '';
          break;
        case '2':
        	$avance_proceso_2_porcentaje = ($meta_proceso_2 != 0) ? number_format((floatval($suma_proceso_2))) : '';
        break;
        case '3':
        	$avance_proceso_2_porcentaje = ($meta_proceso_2 != 0) ? number_format((floatval($suma_proceso_2))) : '';
        break;
        case '4':
        	
        	//$avance_proceso_2_porcentaje = ($meta_proceso_2 != 0) ? number_format((floatval($suma_proceso_2))) : '';
        	$avance_proceso_2_porcentaje = (floatval($suma) / floatval($suma_proceso_2)) * 100;
        	$avance_proceso_porcentaje = "";
        	
        break;
        default:
        	$avance_proceso_2_porcentaje = ($meta_proceso_2 != 0) ? number_format((floatval($suma_proceso_2))) : '';
        break;
        
        }
      	
    	// Producto 1	
    	if ($como_calcular_prducto_1 != 2) {
        	$avance_producto_porcentaje = ($meta_producto != 0) ? number_format((floatval($suma_asist)) / floatval($meta_producto) * 100, 2) : '';	
        }elseif ($como_calcular_prducto_1 == 4){
        	$avance_producto_porcentaje = ($meta_producto != 0) ? number_format(floatval($suma_asist)) : '';
        }else{
        	$avance_producto_porcentaje = ($meta_producto != 0) ? number_format(floatval($suma_asist)) : '';
        }
    	
    
    	if ($como_calcular_producto_2 != 2){
        	$avance_producto_2_porcentaje = ($meta_producto_2 != 0) ? number_format((floatval($suma_producto_2) / floatval($meta_producto_2)) * 100, 2) : '';	
        }else {
        	$avance_producto_2_porcentaje = ($meta_producto_2 != 0) ? number_format((floatval($suma_producto_2))) : '';
        }
    	
    
      //}
    /*
      if ($avance_proceso == 50) {
        
        if ($avance_producto == 50) {
          if (($avance_proceso_porcentaje >= 0 && $avance_proceso_porcentaje <= $avance_proceso) &&
          ($avance_producto_porcentaje >= 0 && $avance_producto_porcentaje <= $avance_producto)) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } elseif ($avance_producto == 70) {
          if (($avance_proceso_porcentaje >= 0 && $avance_proceso_porcentaje <= $avance_proceso) &&
          ($avance_producto_porcentaje >= 51 && $avance_producto_porcentaje <= $avance_producto)) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        }elseif ($avance_producto == 71) {
          if (($avance_proceso_porcentaje >= 0 && $avance_proceso_porcentaje <= $avance_proceso) &&
          ($avance_producto_porcentaje > 70 )) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } elseif ($avance_producto = " ") {
          if (($avance_proceso_porcentaje >= 0 && $avance_proceso_porcentaje <= $avance_proceso)) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        }
      } elseif ($avance_proceso == 70) {
      if ($avance_producto == 50) {
          if (($avance_proceso_porcentaje >= 51 && $avance_proceso_porcentaje <= $avance_proceso) &&
          ($avance_producto_porcentaje >= 0 && $avance_producto_porcentaje <= $avance_producto)) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } elseif ($avance_producto == 70) {
      if (($avance_proceso_porcentaje >= 51 && $avance_proceso_porcentaje <= $avance_proceso) &&
          ($avance_producto_porcentaje >= 51 && $avance_producto_porcentaje <= $avance_producto)) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } elseif ($avance_producto == 71) {
          if (($avance_proceso_porcentaje >= 51 && $avance_proceso_porcentaje <= $avance_proceso) &&
          ($avance_producto_porcentaje > 70 )) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } elseif ($avance_producto = '') {
          if (($avance_proceso_porcentaje >= 51 && $avance_proceso_porcentaje <= $avance_proceso)) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        }
      } elseif ($avance_proceso == 71) {
        if ($avance_producto == 50) {
          if (($avance_proceso_porcentaje >= $avance_proceso) &&
          ($avance_producto_porcentaje >= 0 && $avance_producto_porcentaje <= $avance_producto)) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } elseif ($avance_producto == 70) {
          if (($avance_proceso_porcentaje >= $avance_proceso) &&
          ($avance_producto_porcentaje >= 51 && $avance_producto_porcentaje <= $avance_producto)) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } elseif ($avance_producto == 71) {
          if (($avance_proceso_porcentaje >= $avance_proceso) &&
          ($avance_producto_porcentaje > 70 )) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } elseif ($avance_producto = '') {
          if ($avance_proceso_porcentaje >= $avance_proceso) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        }
      } else {
        if ($avance_producto == 50) {
          if ($avance_producto_porcentaje >= 0 && $avance_producto_porcentaje <= $avance_producto) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } elseif ($avance_producto == 70) {
          if ($avance_producto_porcentaje >= 51 && $avance_producto_porcentaje <= $avance_producto) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } elseif ($avance_producto == 71) {
          if ($avance_producto_porcentaje > 70 ) {
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
          }
        } else {      
            $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_presente, $ind_proceso, $meta_producto, $meta_producto_presente, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje);
        }
      }
      */
    	
      $output[] = $this->print_record($name, $tipo, $meta_proceso, $meta_proceso_2, $ind_proceso, $meta_producto, $meta_producto_2, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje, $field_indicador_proceso_2, $field_indicador_producto_2, $suma_proceso_2, $suma_producto_2, $avance_proceso_2_porcentaje, $avance_producto_2_porcentaje, $bk_codigo_accion, $name_accion);
    }
    
    return $output;

  }
  
  public function print_record($name, $tipo, $meta_proceso, $meta_proceso_2, $ind_proceso, $meta_producto, $meta_producto_2, $ind_producto, $suma, $suma_asist, $avance_proceso_porcentaje, $avance_producto_porcentaje, $field_indicador_proceso_2, $field_indicador_producto_2, $suma_proceso_2, $suma_producto_2, $avance_proceso_2_porcentaje, $avance_producto_2_porcentaje, $bk_codigo_accion, $name_accion) {
   
   $proc_1_color = str_replace(',', '', $avance_proceso_porcentaje);
   if ($proc_1_color >= 0 && $proc_1_color <= 50 && $proc_1_color != "") {
      $class_alerta_proc = "alertaroja";
    } elseif ($proc_1_color >= 51 && $proc_1_color <=70 && $proc_1_color != "") {
      $class_alerta_proc = "alertaamarilla";
    }elseif ($proc_1_color>=71 && $proc_1_color != "") {
      $class_alerta_proc = "alertaverde";
    } else {
      $class_alerta_proc = "";
    }
	$prod_1_color = str_replace(',', '', $avance_producto_porcentaje);
    if ($prod_1_color >= 0 && $prod_1_color <= 50 && $prod_1_color != "") {
      $class_alerta_prod = "alertaroja";
    } elseif ($prod_1_color >= 51 && $prod_1_color <=70 && $prod_1_color != "") {
      $class_alerta_prod = "alertaamarilla";
    }elseif ($prod_1_color >=71 && $prod_1_color != "") {
      $class_alerta_prod = "alertaverde";
    } else {
      $class_alerta_prod = "";
    }
  	$proc_2_color = str_replace(',', '', $avance_proceso_2_porcentaje);
   if ($proc_2_color >= 0 && $proc_2_color <= 50 && $proc_2_color != "") {
      $class_alerta_proc_2 = "alertaroja";
    } elseif ($proc_2_color >= 51 && $proc_2_color <=70 && $proc_2_color != "") {
      $class_alerta_proc_2 = "alertaamarilla";
    }elseif ($proc_2_color>=71 && $proc_2_color != "") {
      $class_alerta_proc_2 = "alertaverde";
    } else {
      $class_alerta_proc_2 = "";
    }
  	$prod_2_color = str_replace(',', '', $avance_producto_2_porcentaje);
  	if ($prod_2_color >= 0 && $prod_2_color <= 50 && $prod_2_color != "") {
      $class_alerta_prod_2 = "alertaroja";
    } elseif ($prod_2_color >= 51 && $prod_2_color <=70 && $prod_2_color != "") {
      $class_alerta_prod_2 = "alertaamarilla";
    }elseif ($prod_2_color >=71 && $prod_2_color != "") {
      $class_alerta_prod_2 = "alertaverde";
    } else {
      $class_alerta_prod_2 = "";
    }
  
   return array(
      'programa' => htmlspecialchars_decode($name),
   	  'programa_accion' => htmlspecialchars_decode($name_accion),
      'codigo_accion' => $bk_codigo_accion,
      'tipo' => $tipo,
      'meta_proceso' => $meta_proceso,
      'ind_proceso' => $ind_proceso,
      'meta_producto' => $meta_producto,
      'ind_producto' => $ind_producto,
      'meta_impacto' => $meta_proceso_2,
      'ind_impacto' => $field_indicador_proceso_2, // Ind proceso 2
      'meta_producto_2' => $meta_producto_2,
      'ind_producto_2' => $field_indicador_producto_2, // Ind producto 2
      'cant_sesiones' => $suma ,
      'cant_asistentes' => empty($suma_asist) ? 0 : $suma_asist,
   	  'calculo_avance_proceso_2' => $suma_proceso_2,
   	  'calculo_avance_producto_2' => $suma_producto_2,
      //'porc_proceso' => number_format(($meta_session_prog[0]->nid_count / $meta_proceso) * 100, 2) . "%" ,
      'porc_proceso' => [Markup::create('<span class="' . $class_alerta_proc . '">' . $avance_proceso_porcentaje . (!empty($avance_proceso_porcentaje) ? "%":"") . ' </span>')], 
      'porc_producto' => [Markup::create('<span class="' . $class_alerta_prod . '">' . $avance_producto_porcentaje . (!empty($avance_producto_porcentaje) ? "%":"") . '</span>')], 
   	  'porc_proceso_2' => [Markup::create('<span class="' . $class_alerta_proc_2 . '"> ' . $avance_proceso_2_porcentaje . (!empty($avance_proceso_2_porcentaje) ? "%":"") .'</span>')], 
      'porc_producto_2' => [Markup::create('<span class="' . $class_alerta_prod_2 . '"> ' . $avance_producto_2_porcentaje . (!empty($avance_producto_2_porcentaje) ? "%":"") .' </span>')], 
    );
    
  }
  public function obtenerAlertas($plan){

    $output = array();
    $default_plan = "plan=".$plan;
    // Obtener cantidad de nodes dependiendo del Plan
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
    $query->innerJoin('node__field_biblioteca', 'bib', 'bib.entity_id = n.nid');
    $query->addExpression('count(n.nid)', 'nid_count');
    $query->addExpression('bib.field_biblioteca_target_id', 'biblioteca');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('fdc.field_concesion_target_id ', $plan);
    $query->condition('n.status', 1);
    $query->groupBy('bib.field_biblioteca_target_id')->orderBy('bib.field_biblioteca_target_id', 'ASC');
    $result = $query->execute()->fetchAll();
    

    foreach ($result as $bib=>$record){
      $term = Term::load($record->biblioteca);
      $name = $term->getName();
      $total[$record->biblioteca][0] = array(
        'id' => 1, 
        'espacio' => $record->biblioteca, 
        'meta'=> "***", 
        'cantidad' => $record->nid_count
      );
    }
    
    // Plan operativo  
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
    $query->innerJoin('node__field_biblioteca', 'bib', 'bib.entity_id = n.nid');
    $query->innerJoin('node__field_meta_sesiones', 'ms', 'ms.entity_id = n.nid');
    //field_meta_sesiones 
    $query->addExpression('sum(ms.field_meta_sesiones_value)', 'suma');
    $query->addExpression('bib.field_biblioteca_target_id', 'biblioteca');
    $query->condition('n.type', 'plan_operativo');
    $query->condition('fdc.field_concesion_target_id ', $plan);
    $query->condition('n.status', 1);
    $query->groupBy('bib.field_biblioteca_target_id')->orderBy('bib.field_biblioteca_target_id', 'ASC');
    $result = $query->execute()->fetchAll();
      
    foreach ($result as $bib=>$record){
      $total[$record->biblioteca][1] = array(
        'id' => 1, 
        'espacio' => $record->biblioteca, 
        'meta'=> $record->suma, 
        'cantidad' => "**"
      );
    }
    // Resumen
    $output = array();
    foreach ($total as $num => $record_totales){
      $term = Term::load($record_totales[0]['espacio']);
      $name = $term->getName();
      $meta = isset($record_totales[1]) ? $record_totales[1]['meta'] : 0;
      $num_act_realizadas =  $record_totales[0]['cantidad'];
      $porc = 0;
      if ($meta != 0) {
        $porc = ($num_act_realizadas/$meta)*100;
      }
      $output[] = array(
        'id'=>0, 
        'espacio'=> $name, 
        'meta'=> $meta, 
        'cantidad'=>$num_act_realizadas, 
        'porc' => $porc . '%',
      );
    }
    
    return $output;
  }

  /**
   * Strings $vid first level
   * Getting list of term depending of vocavulary
   * return array
   */
  public function get_terms_vocabulary($vid){
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $parent = 0, $max_depth = 1, $load_entities = FALSE);
    $options[''] = "Seleccionar una opción";
    $options['All'] = "Todos";
  
    foreach ($terms as $key => $term) {
      $options[$term->tid] = $term->name;
    }
    return $options;
  }
/**
 * Obtener sumatoria del campo proceso 2 para los tipos de contenidos misionales y actividad_ejecutada
 * retorna int
 */
public function get_sum_proceso_2($plan, $program){
    // Getting category of program
    $result = 0;
    
    $term = Term::load($program);

    if (!empty($term)) {
      $categoria = $term->get('field_categoria_actividad')->target_id;
      //Queries
      // Programas especiales
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_linea', 'fl', 'fl.entity_id = n.nid');
        $query->innerJoin('node__field_avance_impacto', 'fas', 'fas.entity_id = n.nid');
        $query->addExpression('sum(fas.field_avance_impacto_value)', 'suma');
    	$group = $query->orConditionGroup()
    	->condition('n.type', 'actividad_ejecutada')
    	->condition('n.type', 'actividades_misionales');
    	$query->condition($group);
        $query->condition('fdc.field_concesion_target_id', $plan);
        $query->condition('fl.field_linea_target_id', $program);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
    }
    
    return $result;
  }

public function get_sum_proceso_2_codprograma($plan, $program, $cod_accion){
    // Getting category of program
    $result = 0;
    
    $term = Term::load($program);

    if (!empty($term)) {
      $categoria = $term->get('field_categoria_actividad')->target_id;
      //Queries
      // Programas especiales
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_codigo_de_accion', 'fl', 'fl.entity_id = n.nid');
        $query->innerJoin('node__field_avance_impacto', 'fas', 'fas.entity_id = n.nid');
        $query->addExpression('sum(fas.field_avance_impacto_value)', 'suma');
    	$group = $query->orConditionGroup()
    	->condition('n.type', 'actividad_ejecutada')
    	->condition('n.type', 'actividades_misionales');
    	$query->condition($group);
        $query->condition('fdc.field_concesion_target_id', $plan);
        $query->condition('fl.field_codigo_de_accion_value', $cod_accion);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
    }
    
    return $result;
  }
public function get_sum_producto_2($plan, $program){
    // Getting category of program
    $result = 0;
    
    $term = Term::load($program);

    if (!empty($term)) {
      $categoria = $term->get('field_categoria_actividad')->target_id;
      //Queries
      // Programas especiales
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_linea', 'fl', 'fl.entity_id = n.nid');
        $query->innerJoin('node__field_valor_producto_2', 'fas', 'fas.entity_id = n.nid');
        $query->addExpression('sum(fas.field_valor_producto_2_value)', 'suma');
    	$group = $query->orConditionGroup()
    	->condition('n.type', 'actividad_ejecutada')
    	->condition('n.type', 'actividades_misionales');
    	$query->condition($group);
        $query->condition('fdc.field_concesion_target_id ', $plan);
        $query->condition('fl.field_linea_target_id ', $program);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
    }
    
    return $result;
  }

public function get_sum_producto_2_codprograma($plan, $program, $cod_accion){
    // Getting category of program
    $result = 0;
    
    $term = Term::load($program);

    if (!empty($term)) {
      $categoria = $term->get('field_categoria_actividad')->target_id;
      //Queries
      // Programas especiales
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin('node__field_concesion', 'fdc', 'fdc.entity_id = n.nid');
        $query->innerJoin('node__field_codigo_de_accion', 'fl', 'fl.entity_id = n.nid');
        $query->innerJoin('node__field_valor_producto_2', 'fas', 'fas.entity_id = n.nid');
        $query->addExpression('sum(fas.field_valor_producto_2_value)', 'suma');
    	$group = $query->orConditionGroup()
    	->condition('n.type', 'actividad_ejecutada')
    	->condition('n.type', 'actividades_misionales');
    	$query->condition($group);
        $query->condition('fdc.field_concesion_target_id', $plan);
        $query->condition('fl.field_codigo_de_accion_value', $cod_accion);
        $query->condition('n.status', 1);
        $result = $query->execute()->fetchAll();
    }
    
    return $result;
  }
}