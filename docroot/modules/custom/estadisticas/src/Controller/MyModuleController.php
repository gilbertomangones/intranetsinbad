<?php

/**	
* @file
* Contains \Drupal\estadisticas\Controller\MyModuleController
*/

namespace Drupal\estadisticas\Controller;
use Drupal\Core\Controller\ControllerBase;
class MyModuleController extends ControllerBase
{
	/**	
	* Generates an example page
	*/
	
	public function general(){
		return array(
			'#type' => 'markup',
      		'#markup' => $this->t('Hello, World!'),
			);
	}
}