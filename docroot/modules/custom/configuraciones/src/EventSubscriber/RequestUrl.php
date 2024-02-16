<?php

namespace Drupal\configuraciones\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Component\Utility;
use Zumba\GastonJS\Browser;
use Drupal\Core\Cache\Cache;  
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * RequestAccompaniment event subscriber class.
 */
class RequestUrl implements EventSubscriberInterface {

  /**
   * \Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * \Drupal\Core\Session\AccuntProxyInterface definition.
   *
   * @var AccuntProxyInterface
   */
  protected $currentUser;

  /**
   * \Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var entityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * \Drupal\wme_page\PageType definition.
   *
   * @var PageType
   */
  protected $pageTypeManager;

  /**
   * Constructor class.
   *
   * @param AccountProxyInterface $current_user
   * @param CurrentRouteMatch $current_route_match
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(AccountProxyInterface $current_user, CurrentRouteMatch $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 0];
    return $events;
  }

  /**
   * Redirects to the page 403 if the user doesn't have access.
   *
   * @param GetResponseEvent $event
   */
  public function onRequest(GetResponseEvent $event) {
	$node = \Drupal::routeMatch()->getParameter('node');
    $current_path = \Drupal::service('path.current')->getPath();
    //Validate current user relationship with current content
    $idUser = $this->currentUser->id();
    $roles = $this->currentUser->getRoles();
    $path = $_SERVER['HTTP_HOST'];
    $rolesPermission = [
      'anonymous'
    ];
    $result  = array_intersect($roles, $rolesPermission);
    $id = "";
    if ($node instanceof \Drupal\node\NodeInterface) {
    	
    	$id = $node->id();
    	$host = \Drupal::request()->getSchemeAndHttpHost();
    	$sw = 0;
    	
    	if (empty($result)) {
 			
        	$file = \Drupal::request()->query->get('file');
        	$url = $host .'/sinbad/'. $file;
        	$filename = urlencode($url);
        	$end = array_slice(explode('/', rtrim($file, '/')), -1)[0];
        	//return new RedirectResponse(\Drupal::url('<front>', [], ['absolute' => TRUE]));
			// tell the browser it's a pdf document
			header('Content-Type: application/pdf');
			//readfile("https://intranet.biblored.net/sinbad/sites/default/files/sinbad/2022-03/listadoasistentes/Lectura,%20escritura%20y%20oralidad%20en%20el%20territorio/Pasquilla/Actividad_05_de_Marzo59b68a7e.pdf");
            // echo '<iframe src="https://intranet.biblored.net/sinbad/sites/default/files/sinbad/2022-03/listadoasistentes/Lectura%2C%20escritura%20y%20oralidad%20en%20el%20territorio/Urbanizacion%20La%20Esperanza/04-03-2260360d90.pdf" width="600"></iframe>';
        	//return new RedirectResponse(\Drupal::url('<front>', [], ['absolute' => TRUE]));
		}
		else {
 			return new RedirectResponse(\Drupal::url('<front>', [], ['absolute' => TRUE]));
		}
        
    }
    
  	/*
    if (empty($result)) {
        //
     }else{
      	die('No tiene permisos para ver este archivo.');
    }
  	*/
  }

}
