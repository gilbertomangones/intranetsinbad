<?php

namespace Drupal\biblored_reports\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Database\Connection;
use \Drupal\Core\Database\Database;
use Drupal\biblored_reports\Controller\BackupsData;
/**
 * An backup controller.
 */
class BackupsData extends ControllerBase {
	/**
   * {@inheritdoc}
   */
  public function get() {
  	
  	$activities = new BackupsData;
  	// Obetener el campo linea del tipo de contenido actividades y hacer un distinc

  	return 0;
  }
  /**
   * return array
   * Concessions first level of the taxonomy "Concesion".
   */
  public function getConcessions() {
  	
  	$vid_concession = 'concesion';
	$terms_plan =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_concession, $parent = 0, $max_depth = 1, $load_entities = FALSE);
	$concession['_none'] = "Ninguno";
	foreach ($terms_plan as $key => $term) {
		$concession[$term->tid] = $term->name;
	}
	
	return $concession;
  }
  /**
   * Getting options plans from $id concession
   * Return array
   */
  public function getPlansConcession($id){
  	$output = array();
  	$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('concesion', $parent = $id, $max_depth = 1, $load_entities = FALSE);
	if ($terms) {
		$output['_none'] = "Seleccionar"; 
		foreach($terms as $plan) {
		    $output[$plan->tid] = $plan->name;             
		}
	}	
	return $output;
  }
  /**
   * Getting all lines in field backups lines
   * return array
   * id Plan de concesión selected
   * plan selected
   */
  public function getLinesdependents($plan){
  	$output = [];

  	$sql_line = "select DISTINCT bnl.field_backup_name_linea_value, bil.field_backup_id_linea_value, fc.field_concesion_target_id
    from node__field_backup_id_linea bil 
    inner join node__field_backup_name_linea bnl on bnl.entity_id = bil.entity_id 
    inner join node__field_concesion fc on fc.entity_id = bil.entity_id
    where bil.bundle = 'actividad_ejecutada' and fc.field_concesion_target_id = '$plan' 
    order by bnl.field_backup_name_linea_value asc";

		$database = \Drupal::database();
		$query = $database->query($sql_line);
		$result = $query->fetchAll();

	if ($result) {
		$output['_none'] = "Ninguno"; 
		foreach ($result as $key => $value) {
			$output[$value->field_backup_id_linea_value] = $value->field_backup_name_linea_value;
		}
	}	
	return $output;
  }

   /* Getting all lines in field backups estrategias
   * return array
   */
  public function getStrategyDependents($plan, $line){
  	$output = [];
    
  	$sql_strategy = "select DISTINCT bnl.field_backup_name_linea_value, bil.field_backup_id_linea_value, fc.field_concesion_target_id, fbie.field_backup_id_estrategia_value, fbne.field_backup_name_estrategia_value
    from node__field_backup_id_linea bil 
    inner join node__field_backup_name_linea bnl on bnl.entity_id = bil.entity_id 
    inner join node__field_concesion fc on fc.entity_id = bil.entity_id
    inner join node__field_backup_id_estrategia fbie on fbie.entity_id = bil.entity_id
    inner join node__field_backup_name_estrategia fbne on fbne.entity_id = bil.entity_id
    where bil.bundle = 'actividad_ejecutada' and fc.field_concesion_target_id = '$plan' and bil.field_backup_id_linea_value = '$line'
    order by bnl.field_backup_name_linea_value asc";

		$database = \Drupal::database();
		$query = $database->query($sql_strategy);
		$result = $query->fetchAll();

    if ($result) {
      $output['_none'] = "Ninguno"; 
      foreach ($result as $key => $value) {
      	$output[$value->field_backup_id_estrategia_value] = $value->field_backup_name_estrategia_value;
      }
    }	

	   return $output;
  }
  
  public function getProgramsDependents($plan, $line, $strategy){
    $output = [];
    
    $sql_strategy = "select DISTINCT bnl.field_backup_name_linea_value, bil.field_backup_id_linea_value, fc.field_concesion_target_id, fbie.field_backup_id_estrategia_value, fbne.field_backup_name_estrategia_value, fbip.field_backup_id_programa_value, fbnp.field_backup_name_programa_value
    from node__field_backup_id_linea bil 
    inner join node__field_backup_name_linea bnl on bnl.entity_id = bil.entity_id 
    inner join node__field_concesion fc on fc.entity_id = bil.entity_id
    inner join node__field_backup_id_estrategia fbie on fbie.entity_id = bil.entity_id
    inner join node__field_backup_name_estrategia fbne on fbne.entity_id = bil.entity_id
    inner join node__field_backup_id_programa fbip on fbip.entity_id = bil.entity_id
    inner join node__field_backup_name_programa fbnp on fbnp.entity_id = bil.entity_id
    where bil.bundle = 'actividad_ejecutada' and fc.field_concesion_target_id = '$plan' and bil.field_backup_id_linea_value = '$line' and fbie.field_backup_id_estrategia_value = '$strategy'
    order by fbnp.field_backup_name_programa_value asc";

    $database = \Drupal::database();
    $query = $database->query($sql_strategy);
    $result = $query->fetchAll();

    if ($result) {
      $output['_none'] = "Ninguno"; 
      foreach ($result as $key => $value) {
        $output[$value->field_backup_id_programa_value] = $value->field_backup_name_programa_value;
      }
    } 

     return $output;
  }
  

}