<?php
/**
 * @file
 * Contains \Drupal\biblored_module\Controller\FirstController.
 */
 
namespace Drupal\biblored_module\Controller;
 
use Drupal\Core\Controller\ControllerBase;
 
class FirstController extends ControllerBase {
  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => t('Hello world'),
    );
  }
}