<?php
namespace Drupal\configuraciones\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a 'User Name' Block.
 *
 * @Block(  
 *   id = "my_show_user",
 *   admin_label = @Translation("Show User"),
 *   category = @Translation("Show User"),
 * )
 */
 class UserNameBlock extends BlockBase {

    /**
   * {@inheritdoc}
   */

  public function build() {

    $userCurrent = \Drupal::currentUser();
    $roles = "";
    $cont = 0;
    $session_close = "";
    $name = "";
    $header_user = "";
    if ($userCurrent->isAuthenticated()) {
        $name = $userCurrent->getAccountName();
        $current_user_roles = $userCurrent->getRoles(); 
        
        foreach($current_user_roles as $rol){
          
          if ($cont == 0) {
            $roles .= $rol;
          } else {
            $roles .= ",".$rol;
          } 
          $cont++;
        }
        $session_close = '<a href="user/logout">Cerrar sesión</a>';
        $header_user = "<div class='profile-user'><span class='nameuser'>" .$name. "</span><span class='roles'>".$session_close."</span></div>";
    }
    else{
        //$name = "Anonymous/Unauthenticated User";
    }

    
    return array(
      '#markup' => $header_user,
    );
  
  }
}
?>