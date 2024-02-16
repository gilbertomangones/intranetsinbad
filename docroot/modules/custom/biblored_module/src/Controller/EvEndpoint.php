<?php

namespace Drupal\biblored_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Database\Connection;
use \Drupal\Core\Database\Database;
/**
 * An example controller.
 */
class EvEndpoint extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function get() {

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

  public function conteo(){

    $element = null; 
    $result = null;
    $user = "postgres";
    $password = "developer2014";
    $dbname = "nautillus";
    $port = "5432";
    $host = "192.168.200.24";
    
   $cadenaConexion = "host=$host port=$port dbname=$dbname user=$user password=$password";

   $conexion = pg_connect($cadenaConexion); // or die("Error en la Conexión: ".pg_last_error());

    if(!$conexion){
        $conn = array('valor' => false );
    }else{
        $conn = array('valor' => true );
      $db = \Drupal\Core\Database\Database::getConnection('default', 'nautillus');

      //To get another database (here : 'myseconddb')      
      $result = $db->query("SELECT datos.puerta AS PUERTA, SUM(CASE WHEN movconteoid in (1,3) THEN cantidad ELSE 0 END) AS TE, datos.nombre as NOMBRE
      FROM historialconteo
      RIGHT OUTER JOIN (SELECT antena.antenaid as puerta, antena.antenanombre as nombre FROM empresa, zona, lectora, antena
      WHERE empresa.empresaid = zona.empresaid AND empresa.ciudadid = zona.ciudadid AND empresa.sucursal = zona.sucursal AND zona.zonaid = lectora.zonaid
      AND lectora.lectoraid = antena.lectoraid AND lectora.tipolectora = 'Micro PC' AND empresa.web = 'GIRAL'
      GROUP BY antena.antenaid) datos ON (historialconteo.antenaid = datos.puerta AND historialconteo.fechalect BETWEEN '2017/12/01 00:00:00' AND '2017/12/31 23:59:59'
      AND date_part('hour', historialconteo.fechalect) BETWEEN 8 AND 20) GROUP BY datos.puerta, datos.nombre");
      $result = $result->fetchAll();
     }

      $element['#contenido'] = $result;
      $element['#conectado'] = $conn;
      $element['#theme'] = 'conteosalasbib';
      
      return $element; 
  }
 public function consultas(){}
/*
  public function consultas(){
      $consulta  = array();
      $element  = null;
      $db ="(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP) (HOST = 192.168.200.21) (PORT = 1521) )(CONNECT_DATA = (SID = ALEPH22)))";
    @ $conn = ocilogon('ALEPH','ALEPH',$db);
    if (!$conn){
      //$e = oci_error();
      //trigger_error(htmlentities($e['No se pudo establecer la conexión'], ENT_QUOTES), E_USER_ERROR);
      $conn_val = array('valor' => false );
    }
    else{
      $conn_val = array('valor' => true );
      $fi = '20171201';
      $ff = '20171231';
      $w = 'VICTO';
      $fir = '20171201';
      $ffr = '20171231';

      //$sql = "select count(*) as total, z35_material from bib50.z35 where Z35_sub_library = '"+w+"' and z35_event_date between '"+fi+"' and '"+ff+"' and z35_event_type = 80 group by z35_material";
      
      $sql = "select count(*) as total, z35_material from bib50.z35 where Z35_sub_library = 'TUNAL' and z35_event_date between '20171201' and '20171231' and z35_event_type = 80 group by z35_material";
      
      $stid = ociparse($conn, $sql);      
      ociexecute($stid);      
      $row = oci_fetch_all($stid, $results, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_NUM);
      

      foreach ($results as $key => $value) {
        $consulta[trim($value[1])] = $value;
      } 
     
    }  
      $element['#contenido'] = $consulta;
      $element['#conectado'] = $conn_val;
      
      $element['#theme'] = 'consultasbib';

     return $element;

  }
*/
 public function prestamos(){}
/*
  public function prestamos(){
    $output = null;
    $element = null;

    $db ="(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP) (HOST = 192.168.200.21) (PORT = 1521) )(CONNECT_DATA = (SID = ALEPH22)))";
    @ $conn = ocilogon('ALEPH','ALEPH',$db);
    if (!$conn){
      $conn_val = array('valor' => false );
    //$e = oci_error();
    //trigger_error(htmlentities($e['No se pudo establecer la conexión'], ENT_QUOTES), E_USER_ERROR);
    }
    else{
     $conn_val = array('valor' => true );
    $fi = '20171201';
    $ff = '20171231';
    $w = 'VICTO';
    $fir = '20171201';
    $ffr = '20171231';

    //$sql = "select count(*) as total, Z30_collection as coleccion from BIB50.Z36, bib50.Z30 where Z36_sub_library = '"+w+"' and Z30_sub_library = '"+w+"' and z30_item_status != 3 and Z36_REC_KEY = Z30_REC_KEY and Z36_LOAN_DATE BETWEEN '"+fi+"' and '"+ff+"' group by Z30_collection union select count(*) as total, Z30_collection as coleccion from BIB50.Z36H, bib50.Z30 where Z36H_sub_library = '"+w+"' and Z30_sub_library = '"+w+"' and z30_item_status != 3 and Z36H_REC_KEY = Z30_REC_KEY and Z36H_LOAN_DATE BETWEEN '"+fi+"' and '"+ff+"' group by Z30_collection";

    $sql = "select count(*) as total, Z30_collection as coleccion from BIB50.Z36, bib50.Z30 where Z36_sub_library = 'VICTO' and Z30_sub_library = 'VICTO' and z30_item_status != 3 and Z36_REC_KEY = Z30_REC_KEY and Z36_LOAN_DATE BETWEEN '20171201' and '20171231' group by Z30_collection union select count(*) as total, Z30_collection as coleccion from BIB50.Z36H, bib50.Z30 where Z36H_sub_library = 'VICTO' and Z30_sub_library = 'VICTO' and z30_item_status != 3 and Z36H_REC_KEY = Z30_REC_KEY and Z36H_LOAN_DATE BETWEEN '20171201' and '20171231' group by Z30_collection";

    $stid = ociparse($conn, $sql);
    ociexecute($stid);
    $row = oci_fetch_all($stid, $results, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_NUM);

    }

    $element['#contenido'] = $results;
    $element['#conectado'] = $conn_val;
    $element['#theme'] = 'prestamos';

    
    return $element;
  }
*/
public function afiliados() {}
/*
  public function afiliados() {

    $output = "";
    $results = array();
    $results_cat = array();
    $results_2 = array();
    $results_3 = array();
    $conn_val = array();
    $element  = null;

    $db ="(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP) (HOST = 192.168.200.21) (PORT = 1521) )(CONNECT_DATA = (SID = ALEPH22)))";
    @ $conn = ocilogon('ALEPH','ALEPH',$db);
    if (!$conn){
     $conn_val = array('valor' => false );
    //$e = oci_error();
    //trigger_error(htmlentities($e['No se pudo establecer la conexión'], ENT_QUOTES), E_USER_ERROR);
    }
    else
    {
      $conn_val = array('valor' => true );
      $fi = '20171201';
      $ff = '20171231';
      $w = 'VICTO';
      $fir = '20171201';
      $ffr = '20171231';


      //$Z303_REC_KEY= utf8_decode(79701814);//utf8_decode($_POST['doc']);  
      //$query = "select Z303_REC_KEY, Z303_NAME from BIB00.z303 where Z303_REC_KEY = '$Z303_REC_KEY'";

      // Obtener F, M, NI
      
      //afiliaciones
      //$sql = "select count(Z305_REC_KEY) as Total, case Z303.Z303_GENDER when coalesce(Z303.Z303_GENDER,' ') then Z303.Z303_GENDER else 'NI' end, Z303.Z303_FIELD_3, Z305.Z305_BOR_TYPE from BIB00.Z303, BIB50.Z305, BIB00.Z308 where Z303_REC_KEY = SUBSTR(Z305_REC_KEY,1,12) and Z308_ID = Z303_REC_KEY and substr(Z308_REC_KEY,1,2) = '01' and (Z305.Z305_OPEN_DATE  BETWEEN '".$fi."' and '".$ff."' or Z303.Z303_OPEN_DATE  BETWEEN '".$fi."' and '".$ff."') and Z303.Z303_HOME_LIBRARY = '".$w."' group by Z303.Z303_GENDER, Z303.Z303_FIELD_3, Z305.Z305_BOR_TYPE order by Z303.Z303_GENDER, Z305.Z305_BOR_TYPE asc";

      //vencimientos
      $sql2 = "select count(*) as venc from BIB00.Z303, BIB50.Z305, BIB00.Z308 where Z303_REC_KEY = SUBSTR(Z305_REC_KEY,1,12) and Z308_ID = Z303_REC_KEY and substr(Z308_REC_KEY,1,2) = '01' and Z305.Z305_EXPIRY_DATE BETWEEN '".$fi."' and '".$ff."' and Z303.Z303_HOME_LIBRARY = '".$w."'";

      //Renovaciones
      $sql3 = "select count(*) as renov from BIB00.Z303, BIB50.Z305, BIB00.Z308 where Z303_REC_KEY = SUBSTR(Z305_REC_KEY,1,12) and Z308_ID = Z303_REC_KEY and substr(Z308_REC_KEY,1,2) = '01' and Z305.Z305_EXPIRY_DATE BETWEEN '".$fir."' and '".$ffr."' and Z305.Z305_OPEN_DATE < '".$fi."' and Z303.Z303_HOME_LIBRARY = '".$w."'";
      
      // Afiliaciones
      $sql = "select count(Z305_REC_KEY) as Total, Z303.Z303_GENDER 
              from BIB00.Z303, BIB50.Z305, BIB00.Z308 
              where Z303_REC_KEY = SUBSTR(Z305_REC_KEY,1,12) 
              and Z308_ID = Z303_REC_KEY and substr(Z308_REC_KEY,1,2) = '01'
              and (Z305.Z305_OPEN_DATE  BETWEEN '20171201' and '20171231' or Z303.Z303_OPEN_DATE  BETWEEN '20171201' and '20171231') 
              and Z303.Z303_HOME_LIBRARY = 'VBARC' 
              group by Z303.Z303_GENDER
              union
              select count(*) as Total, Z303.Z303_FIELD_3
              from BIB00.Z303, BIB50.Z305, BIB00.Z308 
              where Z303_REC_KEY = SUBSTR(Z305_REC_KEY,1,12) 
              and Z308_ID = Z303_REC_KEY and substr(Z308_REC_KEY,1,2) = '01'
              and (Z305.Z305_OPEN_DATE  BETWEEN '20171201' and '20171231' or Z303.Z303_OPEN_DATE  BETWEEN '20171201' and '20171231') 
              and Z303.Z303_HOME_LIBRARY = 'TUNAL' and Z303.Z303_FIELD_3 = 'DISCAPACITADO'
              group by Z303.Z303_FIELD_3";
        $sql_cat = "select count(*) as Total_cata, Z305.Z305_BOR_TYPE
              from BIB00.Z303, BIB50.Z305, BIB00.Z308 
              where Z303_REC_KEY = SUBSTR(Z305_REC_KEY,1,12) 
              and Z308_ID = Z303_REC_KEY and substr(Z308_REC_KEY,1,2) = '01'
              and (Z305.Z305_OPEN_DATE  BETWEEN '20171201' and '20171231' or Z303.Z303_OPEN_DATE  BETWEEN '20171201' and '20171231') 
              and Z303.Z303_HOME_LIBRARY = 'VICTO' and Z305.Z305_BOR_TYPE = '01'
              group by Z305.Z305_BOR_TYPE
              union
              select SUM(count(*)) as Total_catb, TO_CHAR(SUM(count(*))) as Total_catc
              from BIB00.Z303, BIB50.Z305, BIB00.Z308
              where Z303_REC_KEY = SUBSTR(Z305_REC_KEY,1,12) 
              and Z308_ID = Z303_REC_KEY and substr(Z308_REC_KEY,1,2) = '01'
              and (Z305.Z305_OPEN_DATE  BETWEEN '20171201' and '20171231' or Z303.Z303_OPEN_DATE  BETWEEN '20171201' and '20171231') 
              and Z303.Z303_HOME_LIBRARY = 'VICTO' and Z305.Z305_BOR_TYPE > '01'
              group by Z305.Z305_BOR_TYPE";

           $stid = ociparse($conn, $sql);
           ociexecute($stid);
           $row = oci_fetch_all($stid, $results, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_NUM);

           $stid_cat = ociparse($conn, $sql_cat);
           ociexecute($stid_cat);
           $row_cat = oci_fetch_all($stid_cat, $results_cat);

           $stid_2 = ociparse($conn, $sql2);
           ociexecute($stid_2);
           $row_2 = oci_fetch_all($stid_2, $results_2);

           $stid_3 = ociparse($conn, $sql3);
           ociexecute($stid_3);
           $row_3 = oci_fetch_all($stid_3, $results_3);

     
    }
      $element['#contenido'] = $results;
      $element['#cats'] = $results_cat;
      $element['#vencimientos'] = $results_2;
      $element['#renovaciones'] = $results_3;
      $element['#conectado'] = $conn_val;
      $element['#theme'] = 'servicios';

    return $element;
  }
*/
   public function bibliotecas($espacio){

    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('nodos_bibliotecas', $parent = $espacio, $max_depth = 1, $load_entities = FALSE);
      
      if ($terms) {
        
        foreach($terms as $prog) {

        $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($prog->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];
            $output[$tid_biblioteca_agenda] = $prog->name; 
            //$output[]['tid_biblioteca'] = $prog->name; 
        }
      }else{
        $output[''] = $this->t('No hay contenido existente');
      }
      return $output;
  }
	/**
   * Bibliotecas por permisos
   */
  public function bibliotecas_asignadas($espacio, $bibasignada){
    
    /*$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('nodos_bibliotecas', $parent = $espacio, $max_depth = 1, $load_entities = FALSE);
      
      if ($terms) {
        
        foreach($terms as $prog) {
          if ($prog->tid == $bibasignada){
            $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($prog->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];
            $output[$tid_biblioteca_agenda] = $prog->name; 
            //$output[]['tid_biblioteca'] = $prog->name; 
          }
        }
      }else{
        $output[''] = $this->t('No hay contenido existente');
      }
      
      return $output;*/
  		$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('nodos_bibliotecas', $parent = $espacio, $max_depth = 1, $load_entities = FALSE);
      
      if ($terms) {
        
        foreach($terms as $prog) {
          foreach ($bibasignada as $key => $value) {
              if ($prog->tid == $value['target_id']){
				$status_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($prog->tid)->status->getString();
              	if ($status_term == 1){ 
                	$tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($prog->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];
                	$output[$tid_biblioteca_agenda] = $prog->name; 
                	//$output[]['tid_biblioteca'] = $prog->name; 
                }
              }
          }
        }
      }else{
        $output[''] = $this->t('No hay contenido existente');
      }
      
      return $output;
  }
   public function bibliotecas_sistema_asignada($espacio, $bibasignada){
    
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('nodos_bibliotecas', $parent = $espacio, $max_depth = 1, $load_entities = FALSE);
      
      if ($terms) {
        
        
      
      	 foreach($terms as $prog) {
          foreach ($bibasignada as $key => $value) {
              if ($prog->tid == $value['target_id']){
				 $status_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($prog->tid)->status->getString();
              		if ($status_term == 1){ 
                		//$tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($prog->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];
                		$output[$prog->tid] = $prog->name; 
                		//$output[]['tid_biblioteca'] = $prog->name; 
                    }
              }
          }
        }
      }else{
        $output[''] = $this->t('No hay contenido existente');
      }
      
      return $output;
  }
  

public function bibliotecas_sistema($espacio){

    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('nodos_bibliotecas', $parent = $espacio, $max_depth = 1, $load_entities = FALSE);
      
      if ($terms) {
        $output[''] = 'Seleccionar biblioteca';
        foreach($terms as $prog) {   
        	$status_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($prog->tid)->status->getString();
             if ($status_term == 1){ 
            	$output[$prog->tid] = $prog->name; 
             }
        }
      }else{
        $output[''] = $this->t('No hay contenido existente');
      }
      return $output;
  }

	public function bibliotecas_aleph($espacio){
    
	if($espacio == 'All')
    	$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('nodos_bibliotecas');
    else
    	$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('nodos_bibliotecas', $parent = $espacio, $max_depth = 1, $load_entities = FALSE);
    //var_dump($terms);
    $todas = "";
    foreach($terms as $term) {
      $sigla_biblioteca = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_abreviatura_de_la_bibliote')->getValue();
    	if (!empty($sigla_biblioteca)){
    		$sigla_biblioteca = $sigla_biblioteca[0]['value'];
         	//if (!empty($sigla_biblioteca)) {
    	  	$todas .= "'".$sigla_biblioteca .":". $term->name . "',";
         }
      //echo $sigla_biblioteca;
    }
    //echo "Todas".$todas;
    $todas = substr($todas, 0, -1);
    
    $bibliotecas[$todas] = "Todos";
   
    if ($terms){
      foreach($terms as $term) {
          	$tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue();
      		$tid_biblioteca_agenda = isset($tid_biblioteca_agenda[0]) ? $tid_biblioteca_agenda[0]['value'] : null;
          
      		$sigla_biblioteca = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_abreviatura_de_la_bibliote')->getValue();
      		if (isset($sigla_biblioteca)){
    			$sigla_biblioteca = isset($sigla_biblioteca[0]) ? $sigla_biblioteca[0]['value'] : null;
      			
            	// Creando un array con la bibliotecas que tengan equivalencia (en este caso para el filtro sql necesitamos el la sigla biblioteca)
          		//if (!empty($tid_biblioteca_agenda)){
            		$bibliotecas["'".$sigla_biblioteca.":".$term->name."'"] = $term->name;
          		//}
        	}
    	}
    }
    else{
      $bibliotecas[''] = $this->t('No hay espacios asociados');
      //$bibliotecas['All'] = $this->t('Todos los espacios'); 
    }
    
    return $bibliotecas;
  }
  public function serviciojson($arr){
    try {
          $response = \Drupal::httpClient()->get($arr, array('headers' => array('Accept' => 'text/plain')));
          $data = (string) $response->getBody();
    	 
          if (empty($data)) {
            return FALSE;
          }
        }
        catch (RequestException $e) {
          return FALSE;
        }
        
        $output = Json::decode($data, true); 
		
        return $output;
  }

}