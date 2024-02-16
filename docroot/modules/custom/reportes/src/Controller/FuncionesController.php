<?php
/**
 * @file
 * Contains \Drupal\reportes\Controller\FuncionesController.
 */
 
namespace Drupal\reportes\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term; // Para obtener name term taxonomy
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;  
 
class FuncionesController extends ControllerBase {
	/**
   * [getNameTerm Obtener el nombre de un término a partir de un tid]
   * @param  [type] $tid [término identificador]
   * @return [type]      [Nombre del término]
   */
  public function nombreTermino($tid=null) {
  	$name = "";
    
    if (isset($tid)) {
      $term = Term::load($tid);
      $name = $term->getName();  
    }
    
    return $name;
  }

  public function programas($linea_misional){
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = $linea_misional, $max_depth = 1, $load_entities = FALSE);
       
      if ($terms) {
        $programas[''] = $this->t('Seleccionar');
        foreach($terms as $prog) {
            $programas[$prog->tid] = $prog->name; 
        }
      }else{
        $programas[''] = $this->t('No hay programas para esta linea');
      }
    return $programas;
  }

  public function subprogramas($programa){
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = $programa, $max_depth = 1, $load_entities = FALSE);
    
    if ($terms){      
      //$subprogramas['All'] = $this->t('Todos'); 
      $subprogramas[''] = $this->t('Seleccionar'); 
      foreach($terms as $prog) {
            $subprogramas[$prog->tid] = $prog->name; 
        }
    }else{
      $subprogramas[''] = $this->t('No hay subprogramas para este programa');  
    }
      
    return $subprogramas;
    
  }
public function subprogramastable($programa){
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('areas', $parent = $programa, $max_depth = 1, $load_entities = FALSE);
    $subprogramas = array();
    $output3 = array();
	$codigo_accion = "";

    if ($terms){      
        foreach($terms as $prog) {
        $termino = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($prog->tid);
    		$codigo_accion = $termino->field_codigo_programa->value;  
        $estado = $termino->field_suprimir_activar->value;        	
        $indicador_proceso = isset($termino->field_indicador_proceso->getValue()[0]) ? $termino->field_indicador_proceso->getValue()[0]['target_id'] : null;
        $indicador_producto = isset($termino->field_indicador_producto->getValue()[0]) ? $termino->field_indicador_producto->getValue()[0]['target_id'] : null;

        $indicador_impacto = isset($termino->field_impacto->getValue()[0]) ? $termino->field_impacto->getValue()[0]['target_id'] : null; // (*)
        $indicador_producto2 = isset($termino->field_indicador_producto_2->getValue()[0]) ? $termino->field_indicador_producto_2->getValue()[0]['target_id'] : null; // (*)
        	
            if ($estado == 0){
            	$subprogramas[$prog->tid]['name'] = $prog->name; 
            	$subprogramas[$prog->tid]['progaccion'] = !empty($codigo_accion) ? $codigo_accion : "---";
            	$subprogramas[$prog->tid]['indicador_proceso'] = $indicador_proceso;
              	$subprogramas[$prog->tid]['indicador_proceso_name'] = isset($termino->field_indicador_proceso[0]) ? $termino->field_indicador_proceso[0]->entity->label() : "";
              	$subprogramas[$prog->tid]['indicador_producto'] = $indicador_producto;
              	$subprogramas[$prog->tid]['indicador_producto_name'] = isset($termino->field_indicador_producto[0]) ? $termino->field_indicador_producto[0]->entity->label() : "";
                // (Proceso 2)
                $subprogramas[$prog->tid]['indicador_impacto'] = $indicador_impacto;
                $subprogramas[$prog->tid]['indicador_impacto_name'] = isset($termino->field_impacto[0]) ? $termino->field_impacto[0]->entity->label() : "";
            	// (Producto 2)
            	$subprogramas[$prog->tid]['field_indicador_producto_2'] = $indicador_producto2;
                $subprogramas[$prog->tid]['indicador_producto2_name'] = isset($termino->field_indicador_producto_2[0]) ? $termino->field_indicador_producto_2[0]->entity->label() : "";
            }
        }
    }
    
    foreach ($subprogramas as $key => $value) {
      $output3[] = array(
        'id'=> $key, 
        'progaccion'=> $value['progaccion'], 
        'nombre'=> $value['name'], 
        'ind_proceso' => $value['indicador_proceso'], 
        'ind_proceso_name' => $value['indicador_proceso_name'], 
        'ind_producto' => $value['indicador_producto'], 
        'ind_producto_name' => $value['indicador_producto_name'],
        // (proceso 2)
        'ind_impacto' => $value['indicador_impacto'], 
        'ind_impacto_name' => $value['indicador_impacto_name'],
    	// (producto 2)
        'ind_producto2' => $value['field_indicador_producto_2'], 
      	'ind_producto2_name' => $value['indicador_producto2_name'],
        );
    }

    //$subp[] = array('id'=>"11222", 'nombre'=> "Gilbert");
    
    return $output3;
    
  }

  public function entidades($ids){
    try {
        $response = \Drupal::httpClient()->get($ids, array('headers' => array('Accept' => 'text/plain')));
        $data = (string) $response->getBody();
        if (empty($data)) {
          return FALSE;
        }
      }
      catch (RequestException $e) {
        return FALSE;
      }
      
      $output = Json::decode($data); 

      return $output;
  }

public function planes($id){
  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('concesion', $parent = $id, $max_depth = 1, $load_entities = FALSE);
    
      if ($terms) {
        $output[''] = "Seleccionar un plan"; 
        foreach($terms as $plan) {
            $output[$plan->tid] = $plan->name;             
        }
      }else{
        $output[''] = $this->t('No hay contenido existente');
      }
      return $output;
  }
/**
 * Tomar solo el plan por defecto que se asigna por un tipo de contenido Plan por defecto o plan actual
 */
public function plan_default($id, $planactual){
  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('concesion', $parent = $id, $max_depth = 1, $load_entities = FALSE);
    $output = array();
      if ($terms) {
        
        foreach($terms as $plan) {
        	//if ($plan->tid == $planactual){
            $output[$plan->tid] = $plan->name;
            //}
        }
      }else{
        $output[''] = $this->t('No hay contenido existente');
      }
	  
      return $output;
  }

public function plan_default_misional($id, $planactual){
  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('concesion', $parent = $id, $max_depth = 1, $load_entities = FALSE);
    $output = array();
      if ($terms) {
        
        foreach($terms as $plan) {
        	//if ($plan->tid == $planactual){
            $output[$plan->tid] = $plan->name;
            //}
        }
      }else{
        $output[''] = $this->t('No hay contenido existente');
      }
	  
      return $output;
  }

public function bibliotecas_sinbad(){

    $vid = 'nodos_bibliotecas';
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $bibliotecas_1[''] = "Seleccione un espacio";
    foreach($terms as $term) {  
          if ($term->depth == 0) { // 0 PARA EL PADRE
              
             // Array con todas las bibliotecas
                 $term_data[] = array(
                     "id" => $term->tid,
                     "name" => $term->name,
                 );
                 $bibliotecas_1[$term->tid] = $term->name;
          }
     }
     return $bibliotecas_1;
}

public function obtenerAlertas($plan){

    $output = array();
    $default_plan = "plan=".$plan;
	$total = [];
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
      $term = "";
      $meta = 0;
      $num_act_realizadas = "";
      $name_term = "Null";
      $porc = "";
      if (isset($record_totales[0])) {
      	$term = Term::load($record_totales[0]['espacio']);
      	$name_term = isset($term) ? $term->getName() : null;
      	$num_act_realizadas =  $record_totales[0]['cantidad'];
        $meta = isset($record_totales[1]) ? $record_totales[1]['meta'] : 0;
      	$porc = 0;
      	if ($meta != 0) {
        	$porc = number_format(($num_act_realizadas/$meta)*100, 2, '.', '');
      	}
      	$output[] = array(
        	'id'=>0, 
        	'espacio'=> $name_term, 
       	 	'meta'=> $meta, 
        	'cantidad'=> $record_totales[0]['cantidad'], 
        	'porc' => $porc,
      	);
      }
    
      //$name = $term->getName();

      
    }
    
    return $output;
  }
}

?>