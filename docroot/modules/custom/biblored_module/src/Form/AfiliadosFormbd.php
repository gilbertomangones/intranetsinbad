<?php
/**
* @file
* Contains Drupal\biblored_module\form\AfiliadosFormbd
*/

namespace Drupal\biblored_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use Drupal\biblored_module\Controller\EvEndpoint;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;

use Drupal\Core\Url;
use Drupal\Core\Link;

class AfiliadosFormbd extends FormBase
{
	/**
	* {@inheritdoc}
	*/

	public function getFormId()
	{
		return 'biblored_module_afiliadosformbd'; //nombremodule_nombreformulario	
	}

	public function buildForm(array $form, FormStateInterface $form_state) 
	{
    $sw = 0;
	$current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    $header = [];
    $output = [];
    $array = [];
    $total = 0;
    $descarga = 0;
    //var_dump($roles); Ludotecario
    foreach ($roles as $key => $value) 
    {
      if ($value == 'administrator' || $value == 'profesional_linea')
      {
          $sw = 1;
      } // Fin If
    }//Fin foreach
	$form['fechaini'] = array(
		 '#type' => 'date',
		 '#title' => $this->t('Fecha inicio &nbsp;'),
    	 '#required' => TRUE,
		);
	$form['fechafin'] = array(
	 '#type' => 'date',
	 '#title' => $this->t('Fecha fin &nbsp; &nbsp;&nbsp;&nbsp; &nbsp;'),
     '#required' => TRUE,
	);
	/*
    if ($sw == 1){
    $form['test_ink'] = [
     '#type' => 'markup',
     '#title' => $this->t('Descargar'),
     '#markup' => '<a href="/">Descargar</a>',
    ];
    }
    */
	$form['actions'] = [
	  '#type' => 'actions',
	  'submit' => [
	      '#type' => 'submit',
	      '#value' => $this->t('Enviar'),
	  ],
	];

	// Evaluar al presionar clic en Enviar se ha seleccionado Biblioteca
  	
	if ($form_state->getValue('fechaini') && $form_state->getValue('fechafin') ) {		

	    {
	      $fi = $form_state->getValue('fechaini');
	      $ff = $form_state->getValue('fechafin');
          $fi = str_replace("-", "/", $fi);
	      $ff = str_replace("-", "/", $ff);
		 
        $descarga = "";
        if ($sw == 1)
        {
        	$descarga = '<h4><a href="https://biblored.gov.co/servicios_form/bd/estadistica_bd.php?F_IN='.$fi.'&F_OUT='.$ff.'&CONT=true" download="Afiliados_BD.xls">Descargar listado de afiliados</a></h4>';
        }
         $url = "https://biblored.gov.co/servicios_form/bd/estadisticas.php";
    	 $url .= "?f_ini=".$fi;
    	 $url .= "&f_fin=".$ff;
    	 $url .= "&key=".sha1(date('njY'));
         //echo $url;
    	 if($ret = file_get_contents($url))
         {
    	 $array = json_decode($ret, true);
		 $total = $array['Total'];
	           $header = [
	           	  'espacio' => t('Biblioteca Digital'),
			      'femeninas' => t('Femenino'),
			      'masculinas' => t('Masculino'),
			      'otros' => t('Otros'),
               	  'categoria_a' => t('Categoría A (Menores a 8 años)'),
               	  'categoria_b' => t('Categoría B (Desde los 8 años)'),
			    ];
           
        	    //foreach ($array as $value=>$total) 
                {
                       
        			$output[] = [
            	    'espacio' => 'Biblioteca Digital',
		            'femeninas' => $array['Femeninas'],
		            'masculinas' => $array['Masculinas'],          
		            'otros' => $array['Otros'],
                    'categoria_a' => $array['Cat_A'],
               	    'categoria_b' => ($array['Total'] - $array['Cat_A']),
	         		];
        	
        }
        $output['t'] = [
            	   'espacio' => 'Totales',
		           'femeninas' => $array['Femeninas'],
		           'masculinas' => $array['Masculinas'],          
		           'otros' => $array['Otros'],
        		   'categoria_a' => $array['Cat_A'],
        		   'categoria_b' => ($array['Total'] - $array['Cat_A']),
	         	];
         }
        else
        {
        	$total = "Sin acceso al servicio.";
        }
	    }
	}
	$form['exportar'] = [
      '#type' => 'processed_text',
      '#text' => "<a id='exportarexcel' href='javascript:;'>Exportar Excel</a>",
      '#format' => 'full_html',
    ];
    
	$form['table'] = [
	    '#type' => 'tableselect',
	    '#header' => $header,
	    '#options' => $output,
	    '#suffix' => '<h3>Total afiliaciones: '. $total .'</h3><br /><div> * El total de categoría A y B, puede variar de acuerdo a la fecha de nacimiento registrada por el usuario.</div>
        <div> ** El total puede ser variante en el mes, ya que algunos usuarios cambian su afiliación a préstamo físico.</div>'.$descarga,
	    '#empty' => t('No hay información relacionada'),
	    ]; 

	  return $form;

	}


	/**	
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) 
	{

	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) 
	{
	/*
		drupal_set_message($this->t('Valores: @year / @month / @biblioteca ',  
      [ '@year' => $form_state->getValue('year'),
      '@month' => $form_state->getValue('month'),
      '@biblioteca' => $form_state->getValue('biblioteca'),
      ]) 
	  );
	 */

	  $tid_biblioteca = $form_state->getValue('biblioteca');
	  $mes =  $form_state->getValue('month');
	  $year =  $form_state->getValue('year');

	  $form_state->setRebuild();

	}

/**
	function getBibliotecas($form, FormStateInterface $form_state) {
	  return $form['filtro']['biblioteca_2']; 
	}

   * Get options for second field.
   
	public function getOptionsBibliotecasAleph(FormStateInterface $form_state) {

	  $bib = new EvEndpoint;

	  $options = $bib->bibliotecas_aleph($form_state->getValue('biblioteca_1'));  
	  
	  return $options;

  	}
	*/
}