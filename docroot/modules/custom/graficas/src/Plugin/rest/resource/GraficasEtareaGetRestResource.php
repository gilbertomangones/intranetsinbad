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
 *   id = "graficas_etarea_get_rest_resource",
 *   label = @Translation("Graficas Franja Etarea Get Rest Resource"),
 *   uri_paths = {
 *     "canonical" = "/vb-rest-franjas"
 *   }
 * )
 */
class GraficasEtareaGetRestResource extends ResourceBase {
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
	
    // Implementing our custom REST Resource here.
    // Use currently logged user after passing authentication and validating the access of term list.
    if (!$this->loggedUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }	

    
    $current_time = \Drupal::time()->getCurrentTime();   
    $date_today = \Drupal::service('date.formatter')->format($current_time, 'custom', 'Y-m-d');
    $fecha = strtotime('-1 months', strtotime($date_today));
    $nuevafecha = date('Y-m' , $fecha);

    // 79 0 - 5 años: Primera Infancia
    $nueva_fecha_inicial = date('Y-m-01' , $fecha);
    $nueva_fecha_final = date('Y-m-t' , $fecha);
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_franja', 'franja', 'franja.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('count(franja.field_franja_target_id)', 'cero_5');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('franja.field_franja_target_id', '79');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    
    $result_franjas[]= array(
      'franja' => 'franja 0_5 : Primera Infancia',
      'total' => $result[0]->cero_5
    ); 
    // 111 6 - 12 años: Infancia
    $nueva_fecha_inicial = date('Y-m-01' , $fecha);
    $nueva_fecha_final = date('Y-m-t' , $fecha);
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_franja', 'franja', 'franja.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('count(franja.field_franja_target_id)', 'seis_doce');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('franja.field_franja_target_id', '111');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    
    $result_franjas[]= array(
      'franja' => 'franja 6_12 : Infancia',
      'total' => $result[0]->seis_doce
    );
    // 620 13 - 17 años: Adolescencia
    $nueva_fecha_inicial = date('Y-m-01' , $fecha);
    $nueva_fecha_final = date('Y-m-t' , $fecha);
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_franja', 'franja', 'franja.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('count(franja.field_franja_target_id)', 'adolescencia');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('franja.field_franja_target_id', '620');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    
    $result_franjas[]= array(
      'franja' => 'franja 13_17 : Adolescencia',
      'total' => $result[0]->adolescencia
    );
    // 113 18 - 28 años: Juventud
    $nueva_fecha_inicial = date('Y-m-01' , $fecha);
    $nueva_fecha_final = date('Y-m-t' , $fecha);
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_franja', 'franja', 'franja.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('count(franja.field_franja_target_id)', 'juventud');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('franja.field_franja_target_id', '113');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    
    $result_franjas[]= array(
      'franja' => 'franja 18_28 : Juventud',
      'total' => $result[0]->juventud
    );

    // 621 29 - 59 años: Adultez
    $nueva_fecha_inicial = date('Y-m-01' , $fecha);
    $nueva_fecha_final = date('Y-m-t' , $fecha);
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_franja', 'franja', 'franja.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('count(franja.field_franja_target_id)', 'adultez');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('franja.field_franja_target_id', '621');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    
    $result_franjas[]= array(
      'franja' => 'franja 29_59 : Adultez',
      'total' => $result[0]->adultez
    );

      // 114 60 - años o más: Personas mayores
    $nueva_fecha_inicial = date('Y-m-01' , $fecha);
    $nueva_fecha_final = date('Y-m-t' , $fecha);
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_franja', 'franja', 'franja.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('count(franja.field_franja_target_id)', 'personas_mayores');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('franja.field_franja_target_id', '114');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    
    $result_franjas[]= array(
      'franja' => 'franja 60_mas : Personas mayores',
      'total' => $result[0]->personas_mayores
    );
    // 116 familia
    $nueva_fecha_inicial = date('Y-m-01' , $fecha);
    $nueva_fecha_final = date('Y-m-t' , $fecha);
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_franja', 'franja', 'franja.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('count(franja.field_franja_target_id)', 'familia');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('franja.field_franja_target_id', '116');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    
    $result_franjas[]= array(
      'franja' => 'franja familiar : Familia',
      'total' => $result[0]->familia
    );
    // 112 todo publico
    $nueva_fecha_inicial = date('Y-m-01' , $fecha);
    $nueva_fecha_final = date('Y-m-t' , $fecha);
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node__field_franja', 'franja', 'franja.entity_id = n.nid');
    $query->innerJoin('node__field_fecha_realizada_act', 'fecha', 'fecha.entity_id = n.nid');
    $query->addExpression('count(franja.field_franja_target_id)', 'todo_publico');
    $query->condition('n.type', 'actividad_ejecutada');
    $query->condition('franja.field_franja_target_id', '112');
    $query->condition('fecha.field_fecha_realizada_act_value', [$nueva_fecha_inicial, $nueva_fecha_final], 'BETWEEN');
    $query->condition('n.status', 1);
    $result = $query->execute()->fetchAll();
    
    $result_franjas[]= array(
      'franja' => 'franja : Todo publico',
      'total' => $result[0]->todo_publico
    );

    $response = new ResourceResponse($result_franjas);
    $response->addCacheableDependency($result_franjas);
    return $response;
  }

}

