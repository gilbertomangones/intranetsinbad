<?php

namespace Drupal\graficas\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
/**
 * Provides a resource to get view modes by entity and bundle.
 * @RestResource(
 *   id = "graficas_get_rest_resource",
 *   label = @Translation("Graficas Get Rest Resource"),
 *   uri_paths = {
 *     "canonical" = "/vb-rest"
 *   }
 * )
 */
class GraficasGetRestResource extends ResourceBase {
  /**
   * A current user instance which is logged in the session.
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $loggedUser;
  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $config
   *   A configuration array which contains the information about the plugin instance.
   * @param string $module_id
   *   The module_id for the plugin instance.
   * @param mixed $module_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A currently logged user instance.
   */
  public function __construct(
    array $config,
    $module_id,
    $module_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($config, $module_id, $module_definition, $serializer_formats, $logger);

    $this->loggedUser = $current_user;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $config, $module_id, $module_definition) {
    return new static(
      $config,
      $module_id,
      $module_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('graficas'),
      $container->get('current_user')
    );
  }
  /**
   * Responds to GET request.
   * Returns a list of taxonomy terms.
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   * Throws exception expected.
   */
  public function get() {
    // Implementing our custom REST Resource here.
    // Use currently logged user after passing authentication and validating the access of term list.
    if (!$this->loggedUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }	
	/*
    $vid = 'franja';
	$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
	foreach ($terms as $term) {
	  $term_result[] = array(
	    'id' => $term->tid,
		'name' => $term->name
	  );
	}
    */

  /*
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('field_numero_asistentes_0_5_', 'fna05', 'fna05.entity_id = n.nid');
    $query->innerJoin('field_numero_asistentes_6_12_', 'fna612', 'fna612.entity_id = n.nid');
    $query->innerJoin('field_numero_asistentes_13_18_', 'fna1318', 'fna1318.entity_id = n.nid');
    */
    // Implementing our custom REST Resource here.
    // Use currently logged user after passing authentication and validating the access of term list.
    if (!$this->loggedUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }	
	
    $vid = 'franja';
	$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
	foreach ($terms as $term) {
	  $term_result[] = array(
	    'id' => $term->tid,
		'name' => $term->name
	  );
	}
    
    
    //MONTH(DATE_ADD(CURDATE(),INTERVAL -1 MONTH))
    
    $current_time = \Drupal::time()->getCurrentTime();   
    $date_today = \Drupal::service('date.formatter')->format($current_time, 'custom', 'Y-m-d');
    $fecha = strtotime('-1 months', strtotime($date_today));
    $nuevafecha = date('Y-m' , $fecha);

    $nueva_fecha_inicial = date('Y-m-01' , $fecha);
    $nueva_fecha_final = date('Y-m-t' , $fecha);
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_numero_asistentes_0_5_', 'fna05', 'fna05.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('sum(fna05.field_numero_asistentes_0_5__value)', 'suma_0_5');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    $result_franjas[]= array(
      'franja' => 'franja_0_5',
      'total' => $result['suma_0_5']
    ); 

    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_numero_asistentes_6_12_', 'fna612', 'fna612.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('sum(fna612.field_numero_asistentes_6_12__value)', 'suma_6_12');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    $result_franjas[]= array(
      'franja' => 'franja_6_12',
      'total' => $result['suma_6_12']
    ); 
    
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_numero_asistentes_13_18_', 'fna1318', 'fna1318.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('sum(fna1318.field_numero_asistentes_13_18__value)', 'suma_13_18');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();

    $result_franjas[]= array(
      'franja' => 'franja_13_18',
      'total' => $result['suma_13_18']
    );
    
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_numero_asistentes_19_27_', 'fna1927', 'fna1927.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('sum(fna1927.field_numero_asistentes_19_27__value)', 'suma_19_27');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
  
    $result_franjas[]= array(
      'franja' => 'franja_19_27',
      'total' => $result['suma_19_27']
    );
    
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_numero_asistentes_28_60', 'fna2860', 'fna2860.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('sum(fna2860.field_numero_asistentes_28_60_value)', 'suma_28_60');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    $result_franjas[]= array(
      'franja' => 'franja_28_60',
      'total' => $result['suma_28_60']
    );
    
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_numero_asistentes_61_mas', 'fna61mas', 'fna61mas.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('sum(fna61mas.field_numero_asistentes_61_mas_value)', '');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    $result_franjas[]= array(
      'franja' => 'franja_61_mas',
      'total' => $result['suma_61_mas']
    );
    
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_no_reporta_edad', 'fnoreporta', 'fnoreporta.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('sum(fnoreporta.field_no_reporta_edad_value)', 'suma_noreporta');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    $result_franjas[]= array(
      'franja' => 'franja_noreporta',
      'total' => $result['suma_noreporta']
    );

    $response = new ResourceResponse($result_franjas);
    $response->addCacheableDependency($result_franjas);
    return $response;
  }

}

