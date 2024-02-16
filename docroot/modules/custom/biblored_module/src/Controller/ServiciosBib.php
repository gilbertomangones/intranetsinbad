<?php
/**
 * @file
 * Contains \Drupal\biblored_module\Controller\ServiciosBib.
 */
 
namespace Drupal\biblored_module\Controller;
 
use Drupal\Core\Controller\ControllerBase;
 
class ServiciosBib extends ControllerBase {
  public function content() {
  /*	
  $db ="(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP) (HOST = 192.168.200.21) (PORT = 1521) )(CONNECT_DATA = (SID = ALEPH22)))";
	$conn = ocilogon('ALEPH','ALEPH',$db);
	if (!$conn){
	    $e = ocierror();
	    trigger_error(htmlentities($e['No se pudo establecer la conexión'], ENT_QUOTES), E_USER_ERROR);
	}
	$stid = oci_parse($conn, 'SELECT COUNT(*) FROM BIB00.Z303');
	oci_execute($stid);
 */
    return array(
      '#type' => 'markup',
      '#markup' => "hola mundo",
    );
  }
}

?>