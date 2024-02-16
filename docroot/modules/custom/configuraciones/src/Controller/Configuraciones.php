<?php

namespace Drupal\configuraciones\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Database\Connection;
use \Drupal\Core\Database\Database;
/**
 * An configuraciones controller.
 */
class configuraciones extends ControllerBase {
	
	public function actualizarnodos(){
		$array = array(151947,151948,151950,151951,151952,152423,152851,152954,152955,153032,153035,153066,153067,153068,153069,153075,153093,153178,153181,153187);
		$cont = 0;
    	$node = \Drupal::entityTypeManager()->getStorage('node')->load(153515);
		$node->setPublished(true);
		//save to update node
		
    	foreach ($array as $key => $value) {
			$cont++;
			//$node = Node::load($value);
			$node = \Drupal::entityTypeManager()->getStorage('node')->load($value);
			$node->setPublished(true);
        	$node->set('field_test', 53);
        	$node->changed->value = time();
			//save to update node
			$node->save();
			
		}
        \Drupal::messenger()->addMessage('Nodes updated');
		return array(
		      '#type' => 'markup',
		      '#markup' => t('Hello world:').$cont,
		    );
	}
	public function actualizarnodosplan(){
    	global $base_url;
  		$base_url_parts = parse_url($base_url); 
  		$host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
		$array = array(28031,27802,27798,27796,23169,22654,22426,22651,22439,22424,22436,22420,21918,17338,17337,16598,17331,15308,14693,14687,14683,47097,47095,47089,41276,41269,40954,37882,35579,33077,33076,30336,30132,22440,48410,48406,48319,47116,47109,41293,40956,37885,37628,37883,30338,33078,31855,30133,29136,29135,28034,27794,27790,27788,27808,27806,27786,27807,22419,22434,24152,24169,22445,22432,22429,22444,22431,22428,22442,17469,17463,16588,16583,16582,16574,16368,15305,14524,15301,14719,14713,14523,14518,14515,14513,14506,27803,17509,16600,33075,27227,27246,27222,24293,24291,24288,24285,17293,16349,16355);
        // https://intranet.biblored.net/sinbad/ws/modificar
        $end_point_planactual = $host."/ws/modificar";
      	$datos = file_get_contents($end_point_planactual);
      	$cat_facts = json_decode($datos, TRUE);
		$cont = 0;
        
    	foreach ($cat_facts as $key => $value) {
			
			$node = Node::load($value['nid']);
        	$node->body->format = 'full_html';
			$node->field_concesion->target_id = 1924;
			
			//save to update node
			$node->save();
			$cont++;
		}
    
		return array(
		      '#type' => 'markup',
		      '#markup' => t('Hello world:').$cont,
		    );
	}

	
}