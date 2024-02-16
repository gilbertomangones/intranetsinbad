<?php
/**
* @file
* Contains Drupal\conteo\form\ConteoFormv2
*/

namespace Drupal\biblored_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\biblored_module\Controller\EvEndpoint;
use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
* 
*/
class ConteoFormv2 extends FormBase
{
	/**
	* {@inheritdoc}
	*/

	public function getFormId()
	{
		return 'biblored_module_conteoformv2'; //nombremodule_nombreformulario	
	}

	public function buildForm(array $form, FormStateInterface $form_state) 
	{
		$vid = 'nodos_bibliotecas';
 
		$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
		$bibliotecas[0] = "Ninguno";
		foreach($terms as $term) {
		    $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];
		    $sigla_biblioteca = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_abreviatura_de_la_bibliote')->getValue()[0]['value'];
		    if ($term->depth == 1) {
	    	     // Array con todas las bibliotecas
	            $term_data[] = array(
                 "id" => $term->tid,
                 "name" => $term->name,
                 'tid_biblioteca_agenda' => \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'],
                 'sigla' => $sigla_biblioteca,
	             );

		         // Creando un array con la bibliotecas que tengan equivalencia (en este caso para el filtro sql necesitamos el la sigla biblioteca)
		        if (!empty($tid_biblioteca_agenda)){
		            $bibliotecas[$sigla_biblioteca] = $term->name;
		        }
		     }
		}

		// BIBLIOTECAS CON SIGLAS
	  $vid = 'nodos_bibliotecas';
	  $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
	  $bibliotecas_1['All'] = "Seleccione un espacio"; 
	  foreach($terms as $term) {  
	        if ($term->depth == 0) { // 0 PARA EL PADRE
	            
	           // Array con todas las bibliotecas
	               $term_data[] = array(
	                   "id" => $term->tid,
	                   "name" => $term->name,
	               );
	               $bibliotecas_1[$term->tid] = $term->name;
	        }
	   }

		$month[0] = "Ninguno";
		for ($i=1; $i <= 12; $i++) { 
		 switch ($i) {
		  case '1':
		    $label_mes = "Enero";
		   break;
		  case '2':
		    $label_mes = "Febrero";
		   break;
		   case '3':
		    $label_mes = "Marzo";
		   break;
		   case '4':
		    $label_mes = "Abril";
		   break;
		   case '5':
		    $label_mes = "Mayo";
		   break;
		   case '6':
		    $label_mes = "Junio";
		   break;
		   case '7':
		    $label_mes = "Julio";
		   break;
		   case '8':
		    $label_mes = "Agosto";
		   break;
		   case '9':
		    $label_mes = "Septiembre";
		   break;
		   case '10':
		    $label_mes = "Octubre";
		   break;
		   case '11':
		    $label_mes = "Noviembre";
		   break;
		   case '12':
		    $label_mes = "Diciembre";
		   break;
		 }
		 $month[$i] = $label_mes;
		}
	$year[0] = "Ninguno";
	  for ($i=2012; $i <= date('Y'); $i++) { 
	    $year[$i] = $i;
	}

	$form['filtro']['biblioteca_1'] = array(
	     '#type' => 'select',
	     '#title' => $this->t('Espacios'),
	     '#description' => 'Espacio',
	     '#options' => $bibliotecas_1,
	     '#ajax' => [
	        'callback' => '::getBibliotecas',
	        'wrapper' => 'bibliotecas-wrapper-espacio',
	        'method' => 'replace',
	        'effect' => 'fade',
	      ],
	      '#prefix' => '<div class="col-md-6 mallaespacio">',
	      '#suffix' => '</div>',
	      'cardinality' => 6,
	    );
	  $form['filtro']['biblioteca_2'] = [
	      '#type' => 'select',
	      '#title' => $this->t('Biblioteca'),
	      //'#required' => FALSE,
	      '#options' => $this->getOptionsBibliotecasAleph($form_state),
	      '#validated' => TRUE,
	      '#default_value' => isset($form_state->getValues()['biblioteca_2'])?$form_state->getValues()['biblioteca_2']:"",
	      //'#empty_option' => $this->t('Bibliotecas'),
	      '#prefix' => '<div class="col-md-6" id="bibliotecas-wrapper-espacio">',
	      '#suffix' => '</div>',
	      'cardinality' => 7,
	  ];

	$form['fechaini'] = array(
		 '#type' => 'date',
		 '#title' => $this
		   ->t('Fecha inicio &nbsp;'),
    	'#prefix' => '<div class="col-md-12">',
	      '#suffix' => '</div>',
		);
	$form['fechafin'] = array(
	 '#type' => 'date',
	 '#title' => $this
	   ->t('Fecha fin &nbsp; &nbsp;&nbsp;&nbsp; &nbsp;'),
    '#prefix' => '<div class="col-md-12">',
	      '#suffix' => '</div>',
	);

	$form['actions'] = [
	  '#type' => 'actions',
	  'submit' => [
	      '#type' => 'submit',
	      '#value' => $this->t('Enviar'),
	  ],
	];
	
	
	// Evaluar al presionar clic en Enviar se ha seleccionado Biblioteca
	if ( $form_state->getValue('biblioteca_2') && $form_state->getValue('fechaini') && $form_state->getValue('fechafin') ) {

		$biblioteca = $form_state->getValue('biblioteca_2');
		$array_b = str_replace("'", "",$biblioteca);
          $w = "";
          $array_b = explode(",", $array_b);
          $array_datos = array();
          
          foreach($array_b as $bib){
            
            $bib_real = explode(":",$bib);
          	$array_datos[$bib_real[0]] =  array(0=>$bib_real[1], 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0);
            $w .= "'".$bib_real[0]."',"; 
          }
          $w = substr($w, 0, -1);
    
		$fi = $form_state->getValue('fechaini');
		$ff = $form_state->getValue('fechafin');
        
        $numero_dias = cal_days_in_month(CAL_GREGORIAN, $month, $year);

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
			$result = $db->query("SELECT 
    				datos.puerta AS nombre, datos.sicuenta,
    				SUM(CASE WHEN g_movconteoid in (1,3) THEN g_cantidad ELSE 0 END) AS TE
    				FROM g_historialconteo
    				RIGHT OUTER JOIN (SELECT antena.antenaid, zona.conteo as sicuenta, 
                        tipoantena.nomb_tian as puerta
                        FROM empresa, zona, lectora, antena, tipoantena
                        WHERE empresa.empresaid = zona.empresaid
                        AND zona.zonaid = lectora.zonaid
                        AND lectora.lectoraid = antena.lectoraid
                        AND antena.tipoantenaid = tipoantena.id
                        AND lectora.tipolectoraid <> 2
                        AND empresa.web in ( ".$w." )
						GROUP BY antena.antenaid, zona.conteo, tipoantena.nomb_tian) 
						datos ON (g_historialconteo.g_antenaid = datos.antenaid 
     					AND g_historialconteo.g_fechalect BETWEEN '$fi 00:00:00' AND '$ff 23:59:59'
                        AND date_part('hour', g_fechalect) BETWEEN 7 AND 20)
					GROUP BY datos.puerta, datos.sicuenta
     				ORDER BY datos.puerta");
		
			$resultados = $result->fetchAll();
	    
	    }
		//'puerta' => t('Puerta'), cuando se mostraba el código de la puerta
		$header = [
	      'espacio' => t('Espacio / Sala'),
	      
	      'valor' => t('Ingresos registrados'),
	    ];
		$totales = 0;
	    foreach ($resultados as $record) {
		    if($record->sicuenta == true)
		    {	
		    	$output[] = [
		    		'espacio' => $record->nombre,        
		            'valor' => $record->te,    
	         	];
	        }
	        else
	        {
	        	$outputs[] = [
		    		'espacio' => $record->nombre,    
		            'valor' => $record->te,    
	         	];
            $totales += $record->te;
	        }
	    }	

	}

$consulta = array();
$total = 0;
$add = false;
foreach ($output as $key)
{

	if(sizeof($consulta) == 0)
	{
		array_push($consulta, $key); 
	}
	else
	{
		foreach ($consulta as $valor)
		{
			if($key['espacio'] == $valor['espacio'])
		    {
		        $add = true;

		    }
		    
		} 

		if($add == false)        
		{
		        array_push($consulta, $key); 
		}
		else
		{
		      	for($i=0;$i<sizeof($consulta);$i++)
		      	{
					if($consulta[$i]['espacio'] == $key['espacio'])
				    {

				        $consulta[$i]['valor'] += $key['valor'];

				    }
				    
				} 

		}
	}
	$add = false;   
      	$total += $key['valor'];
     
}



/*$key = array('espacio' => 'Total', 'valor' => $total);
array_push($consulta, $key); */

 //Forma original con modificaciones
	if(sizeof($consulta) > 0) 
	{
		$form['table'] = [
		    '#type' => 'tableselect',
		    '#prefix' => '<br /><h3>Espacios principales</h3>',
		    '#header' => $header,
		    '#options' => $consulta,
		    '#suffix' => '<h3><span class="alert alert-success alert-dismissible">Total visitas espacios principales: '.$total.'</span></h3>',
		    '#empty' => t('No actividades encontradas'),
		    ]; 
	}
	
	 if(sizeof($outputs) > 0) 
	 {
	  	$forms['table'] = [
	    '#type' => 'tableselect',
        '#prefix' => '<hr align="center" width="90%"><br /><h4>Espacios secundarios</h4>',
	    '#header' => $header,
	    '#options' => $outputs,
	    '#suffix' => '<h4>Total visitas espacios secundarios: '.$totales.
        '</h4><div>** Datos ya incluidos en el conteo de espacio principal, se muestra la información para análisis interno en la biblioteca.</div><br />',
		
	    ];
	  	array_push($form, $forms);
	 }  
	  unset($forms, $header, $consulta, $total, $i, $key);
	  return $form;
/*
    //Forma Gilberto
	  $build2 = [
          'table'          => [
          '#prefix'        => '<h3>Espacios principales</h3>',
          '#theme'         => 'table',
          '#attributes'    => [
              'data-striping' => 0
          ],
          '#header' => $header,
          '#rows'   => $consulta,
      ],
    ];
   
   $build3 = [
          'table'          => [
          '#prefix'        => '<h3>Espacios secundarios</h3>',
          '#theme'         => 'table',
          '#attributes'    => [
              'data-striping' => 0
          ],
          '#header' => $header,
          '#rows'   => $outputs,
      ],
    ];
    array_push($build2, $build3 );
    
    return $build2;
    */

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

	 \Drupal::messenger()->addMessage(
          $this->t('Valores: @year / @month / @biblioteca ',  
      [ '@year' => $form_state->getValue('year'),
      '@month' => $form_state->getValue('month'),
      '@biblioteca' => $form_state->getValue('biblioteca_2'),
      ])
         );

	  $tid_biblioteca = $form_state->getValue('biblioteca_2');
	  $mes =  $form_state->getValue('month');
	  $year =  $form_state->getValue('year');

	  $form_state->setRebuild();

	}

	public function getOptionsBibliotecasAleph(FormStateInterface $form_state) {

	  $bib = new EvEndpoint;

	  $options = $bib->bibliotecas_aleph($form_state->getValue('biblioteca_1'));  
	  
	  return $options;

  	}

	function getBibliotecas($form, FormStateInterface $form_state) {
	  return $form['filtro']['biblioteca_2']; 
	}

}