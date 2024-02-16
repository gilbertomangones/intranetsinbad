<?php
namespace Drupal\actividadessiau\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\biblored_module\Controller\EvEndpoint;
use Drupal\reportes\Controller\FuncionesController;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Serialization\Json;

class ActividadExtrasForm extends FormBase {

  public function getFormId() {
      return 'actividadessiau_extrasform'; //nombremodule_nombreformulario
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
    $host_agenda = \Drupal::config('Configuraciones.settings')->get('uribase');
    // BIBLIOTECAS
    $vid = 'nodos_bibliotecas';
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    $bibliotecas['All'] = ""; 
    foreach($terms as $term) {  
          if ($term->depth == 0) { // 0 PARA EL PADRE
             // Array con todas las bibliotecas
                 $term_data[] = array(
                     "id" => $term->tid,
                     "name" => $term->name,
                 );
                 $bibliotecas[$term->tid] = $term->name;
          }
     }
  // In this case, display only current concesion active
  global $base_url;
  $base_url_parts = parse_url($base_url); 
  $host = $base_url_parts['scheme']."://".$base_url_parts['host'].$base_url_parts['path'];
  
  
  
    $form['filtro']['biblioteca'] = array(
     '#type' => 'select',
     '#title' => $this->t('Espacios'),
     '#options' => $bibliotecas,
     '#ajax' => [
        'callback' => [$this, 'getBibliotecas'], //'::getProgramas',
        'wrapper' => 'bibliotecas-wrapper-espacio',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    '#prefix' => '<div class="col-md-6">',
  '#suffix' => '</div>',
    );
    $tipo_espacio = $form_state->getValue('biblioteca');
  switch ($tipo_espacio) {
      case '2077':
        $name_espacio = 'Nombre estrategia móvil';
        break;
  	  case '352':
        $name_espacio = 'Nombre biblioteca';
        break;
      case '353':
        $name_espacio = 'Nombre bibloestación';
        break;
  	  case '354':
        $name_espacio = 'Nombre PPP';
        break;
  	  case '645':
        $name_espacio = 'Nombre ruralidad';
        break;
      default:
        $name_espacio = 'Nombre';
        break;
    }
  $form['filtro']['biblioteca_2'] = [
      '#type' => 'select',
      '#title' => $name_espacio,
      '#required'=> TRUE,
      '#options' => $this->getOptionsBibliotecas($form_state),
      '#validated' => TRUE,
      '#default_value' => isset($form_state->getValues()['biblioteca_2'])?$form_state->getValues()['biblioteca_2']:"",
      //'#empty_option' => $this->t('Bibliotecas'),
      '#prefix' => '<div class="col-md-6" id="bibliotecas-wrapper-espacio">',
      '#suffix' => '</div>',
  ];
      
  $form['filtro']['actions'] = [
        '#type' => 'actions',
        'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Ingresar actividad'),
        ],
        '#prefix' => '<div class="btn-ingresar col-md-6">',
        '#suffix' => '</div>',
    ]; 
      return $form;
    }

    /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

      // Display the results.
      
      // Call the Static Service Container wrapper
      // We should inject the messenger service, but its beyond the scope of this example.
      $messenger = \Drupal::messenger();
      
      $biblioteca = $form_state->getValue('biblioteca_2');
      $params['query'] = [
        'biblioteca' => $form_state->getValue('biblioteca_2'),
      	'edit[field_anno][widget][0][value]' => date("Y"),
      ];
      // Redirect to home
      $form_state->setRedirectUrl(Url::fromUri('internal:' . '/node/add/historico_prestamos', $params));
        //$form_state->setRedirectUrl(Url::fromUri("http://localhost/estadisticas-biblored/node/add/actividad_ejecutada?edit%5Btitle%5D=Leo%20con%20mi%20beb%C3"));
    } 


  function getBibliotecas($form, FormStateInterface $form_state) {

      return $form['filtro']['biblioteca_2'];
  }
  
    public function getOptionsBibliotecas(FormStateInterface $form_state) {

    $statistics = new EvEndpoint;
	$sw = 0;
    $options = "";
    //$options = $bib->bibliotecas_sistema($form_state->getValue('biblioteca'));  
    // Validar rol y biblioteca asignada, para obtener todas o la biblioteca asignada
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id()); // Usuario actual
    //$user = \Drupal\user\Entity\User::load(1333);
    $biblioteca = $user->get('field_biblioteca_o_nodo')->getValue();
    $cod_biblioteca = isset($biblioteca[0]['target_id'])?$biblioteca[0]['target_id']:"";
    
    $current_user = \Drupal::currentUser();

    $roles_excluidos = array("authenticated");
    $roles = $current_user->getRoles();
    
    //var_dump($roles); Ludotecario
    foreach ($roles as $key => $value) {
      if ($value == 'promotores_biblioteca' || 
          $value == 'coordinador_biblioteca' || 
          $value == 'profesional_biblioteca')
           {
          // Para el caso que hay usuarios al cual no se les ha asignado una biblioteca
          $sw = 1;
      } // Fin If
    }//Fin foreach
  
    if ($sw == 1){
      if (!empty($cod_biblioteca)){
        //echo "Biblioteca asignada:".$cod_biblioteca;      
        //$options = $statistics->bibliotecas_sistema_asignada($form_state->getValue('biblioteca'), $cod_biblioteca);  
        $espacio = $form_state->getValue('biblioteca');
        //echo "Biblioteca asignada:".$cod_biblioteca;      
        //$options = $statistics->bibliotecas_asignadas($form_state->getValue('biblioteca'), $cod_biblioteca);
        $options = $statistics->bibliotecas_sistema_asignada($espacio, $biblioteca);
      }else{
        //echo "biblioteca no asignada";
        //$options = $statistics->bibliotecas($form_state->getValue('biblioteca'));
      }
    }else{
      $options = $statistics->bibliotecas_sistema($form_state->getValue('biblioteca'));
    }
   // $form['biblioteca_2']['#options'] = $options;
    
    //return $form['biblioteca_2'];
    
    return $options;
    
  }

}