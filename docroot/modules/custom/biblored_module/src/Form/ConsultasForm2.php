<?php
/**
* @file
* Contains Drupal\conteo\form\ConsultasForm
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
use \SoapClient;
class ConsultasForm2 extends FormBase
{
	/**
	* {@inheritdoc}
	*/

	public function getFormId()
	{
		return 'biblored_module_prestamosform2'; //nombremodule_nombreformulario	
	}

	public function buildForm(array $form, FormStateInterface $form_state) 
	{
		
     //Inicio del comentario de suspensión
    	$vid = 'nodos_bibliotecas';
 		$header = [];
    	$output = [];
    	
    	
		$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
		$bibliotecas[0] = "Ninguno";
    	
		foreach($terms as $term) {
		    $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue();
            $tid_biblioteca_agenda = isset($tid_biblioteca_agenda[0]) ? $tid_biblioteca_agenda[0]['value']:"";
		    
            $sigla_biblioteca = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_abreviatura_de_la_bibliote')->getValue();
            $sigla_biblioteca = isset($sigla_biblioteca[0]) ? $sigla_biblioteca[0]['value']: "";
		    if ($term->depth == 1) {
	    	     // Array con todas las bibliotecas
	    	    $tid_bib = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue();
            	$tid_bib = isset($tid_bib[0]) ?  $tid_bib[0]['value'] : "";
	            $term_data[] = array(
                 "id" => $term->tid,
                 "name" => $term->name,
                 'tid_biblioteca_agenda' => $tid_bib,
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
	  $bibliotecas_1['All'] = "Todos los espacios"; 
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
    /*
	$form['help'] = [
	'#type' => 'item',
	'#title' => t('Nota importante'),
	'#markup' => t('Informe en mantenimiento.'),
	'#prefix' => '<div class="col-md-12 mantenimiento">',
	'#suffix' => '</div>',
	];
    */
	$form['filtro']['biblioteca_1'] = array(
	     '#type' => 'select',
	     '#title' => $this->t('Espacios'),
	     '#description' => 'Espacio',
	     '#options' => $bibliotecas_1,
    	 '#required' => TRUE,
	     '#ajax' => [
	        'callback' => '::getBibliotecas',
	        'wrapper' => 'bibliotecas-wrapper-espacio',
	        'method' => 'replace',
	        'effect' => 'fade',
	      ],
	      '#prefix' => '<div class="mallaespacio">',
	      '#suffix' => '</div>',
	    );
	  $form['filtro']['biblioteca_2'] = [
	      '#type' => 'select',
	      '#title' => $this->t('Biblioteca'),
	      '#required' => TRUE,
	      '#options' => $this->getOptionsBibliotecasAleph($form_state),
	      '#validated' => TRUE,
	      '#default_value' => isset($form_state->getValues()['biblioteca_2'])?$form_state->getValues()['biblioteca_2']:"",
	      //'#empty_option' => $this->t('Bibliotecas'),
	      '#prefix' => '<div class="" id="bibliotecas-wrapper-espacio">',
	      '#suffix' => '</div>',
	  ];

	$form['fechaini'] = array(
		 '#type' => 'date',
		 '#title' => $this->t('Fecha inicio &nbsp;'),
    	 '#required' => TRUE,
    	 '#prefix' => '<div class="">',
	     '#suffix' => '</div>',
		);
	$form['fechafin'] = array(
	 '#type' => 'date',
	 '#title' => $this->t('Fecha fin &nbsp; &nbsp;&nbsp;&nbsp; &nbsp;'),
     '#required' => TRUE,
     '#prefix' => '<div class="">',
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
		if ( $form_state->getValue('biblioteca_1') && $form_state->getValue('fechaini') && $form_state->getValue('fechafin') ) 
        {
			$total = 0;
        	$bloq = false;
			//$numero_dias = cal_days_in_month(CAL_GREGORIAN, $month, $year);
			$array_datos = array();
    		$output = [];
			if($form_state->getValue('biblioteca_2'))
        	{
            	$bloq = true;
            	$biblioteca = $form_state->getValue('biblioteca_2');
            	$array_b = str_replace("'", "",$biblioteca);
            	$array_b = explode(",", $array_b);
            	
            	foreach($array_b as $bib)
            	{
                	$bib_real = explode(":",$bib);
                	$array_datos[trim($bib_real[0])] = array(0=>$bib_real[1], 'ALBUM'=>0, 'Audiolibro'=>0, 'Blu-Ray'=>0, 'Braile General'=>0, 'Braile Infantil'=>0, 'Casetes'=>0, 'CD'=>0, 'Computador Portátil'=>0, 'Diskete'=>0, 'DVD'=>0, 
                                                            'E-reader'=>0, 'Folletos'=>0, 'Infantil Braile'=>0, 'Juegos de reglas'=>0, 'Juegos de Rol'=>0, 'Lámina'=>0, 'Libro Electrónico'=>0, 'Libro en tela'=>0, 'Libro General'=>0, 'Libro Infantil'=>0, 
                                                            'Literatura Braile'=>0, 'Mapas'=>0, 'Música'=>0, 'Musicales'=>0, 'Instrumentos musicales'=>0, 'Objeto'=>0, 'Partituras'=>0, 'Periódicas'=>0, 'Periódicos'=>0, 'Referencia'=>0, 'Tablet'=>0, 'Videos'=>0, 'VHS'=>0, 'Total'=>0);
                	//$w .= "'".$bib_real[0]."',"; 
            	}
            //var_dump($array_datos);
        	}
			$fi = $form_state->getValue('fechaini');
	    	$ff = $form_state->getValue('fechafin');
         
			$year = explode("-", $fi);
			$fir = $year[2]."/".$year[1]."/".$year[0];
			unset($year);
			$year = explode("-", $ff);
			$ffr = $year[2]."/".$year[1]."/".$year[0];
        
			unset($year);
    		$nusoap_path = drupal_get_path('module', 'biblored_module') . '/src/lib/nusoap.php';
        
    		$url = "https://catalogo.biblored.gov.co/pergamum/web_service/integracao_sever_ws.php?wsdl";
    		$options = [
			'cache_wsdl'     => WSDL_CACHE_NONE,
			'trace'          => 1,
			'stream_context' => stream_context_create(
				[
					'ssl' => [
						'verify_peer'       => false,
						'verify_peer_name'  => false,
						'allow_self_signed' => true
					]
				]
			)
			];
        	$client = new \nusoap_client($url, false);
    
    		$client->soap_defencoding = 'UTF-8';
			//$client = new SoapClient($url, $options); 
			$result = $client->call('ws_biblored_sinbad', array('etapa' => "4",	
																  'data_ini' => "".$fir ."",
																  'data_fim' => "".$ffr ."",
																  'chave' => "a8ad1f47da75e86d6511235a6a6c3b7d"));
			if (!$result)
        	{
	     		$conn_val = array('valor' => false );  
	    	}
		    else
		    {
	   	  		$result = explode("\n", utf8_encode($result)); 
	      		$conn_val = array('valor' => true );
            	$total = array('ALBUM'=>0, 'Audiolibro'=>0, 'Blu-Ray'=>0, 'Braile General'=>0, 'Braile Infantil'=>0, 'Casetes'=>0, 'CD'=>0, 'Computador Portátil'=>0, 'Diskete'=>0, 'DVD'=>0, 
                               'E-reader'=>0, 'Folletos'=>0, 'Infantil Braile'=>0, 'Juegos de reglas'=>0, 'Juegos de Rol'=>0, 'Lámina'=>0, 'Libro Electrónico'=>0, 'Libro en tela'=>0, 'Libro General'=>0, 'Libro Infantil'=>0, 
                               'Literatura Braile'=>0, 'Mapas'=>0, 'Música'=>0, 'Musicales'=>0, 'Instrumentos musicales'=>0, 'Objeto'=>0, 'Partituras'=>0,'Periódicas'=>0, 'Periódicos'=>0, 'Referencia'=>0, 'Tablet'=>0, 'Videos'=>0, 'VHS'=>0, 'Total'=>0);
				$con = 0;
				$par = 0;
	      		foreach($result as $rows)  
				{
					$row = explode(",", $rows);
					//var_dump($row);  
					//echo "<br />";
					if(sizeof($row) == 6 && !isset($array_datos[trim($row[2])]) && $bloq == false)
					{
						$array_datos[trim($row[2])] = array(0=>$row[1], 'ALBUM'=>0, 'Audiolibro'=>0, 'Blu-Ray'=>0, 'Braile General'=>0, 'Braile Infantil'=>0, 'Casetes'=>0, 'CD'=>0, 'Computador Portátil'=>0, 'Diskete'=>0, 'DVD'=>0, 
                                                            'E-reader'=>0, 'Folletos'=>0, 'Infantil Braile'=>0, 'Juegos de reglas'=>0, 'Juegos de Rol'=>0, 'Lámina'=>0, 'Libro Electrónico'=>0, 'Libro en tela'=>0, 'Libro General'=>0, 'Libro Infantil'=>0, 
                                                            'Literatura Braile'=>0, 'Mapas'=>0, 'Música'=>0, 'Musicales'=>0, 'Instrumentos musicales'=>0, 'Objeto'=>0, 'Partituras'=>0,'Periódicas'=>0, 'Periódicos'=>0, 'Referencia'=>0, 'Tablet'=>0, 'Videos'=>0, 'VHS'=>0, 'Total'=>0);
					}
					if(sizeof($row) == 6 && isset($array_datos[trim($row[2])]))
					{
						$array_datos[trim($row[2])][trim($row[3])] += $row[4];
                    	$array_datos[trim($row[2])]['Total'] += $row[4];
                    	$total[trim($row[3])] += $row[4];
                    	$total['Total'] += $row[4];
					}
				}
            
            	/*
            	// datos de Sinbad
            	$bib_espacio = $biblioteca = $form_state->getValue('biblioteca_1');
					$bib_biblioteca = $biblioteca = $form_state->getValue('biblioteca_2');
					//echo "bib1: " . $bib_espacio;
					//echo "bib2: " . $bib_biblioteca;
					$det_all = substr_count($bib_biblioteca, ':');
					//echo $det_all;
					$anno = 2022;
					$month = '1';
					$url_sinbad = "";
					// SI ES BIBLIOTECA : 352
					// Bibloestaciones : 353
					// PPP : 354
					// Todos los espacios : All

					$espacios = ['352','353','354'];
            		if (in_array($bib_espacio, $espacios)) {
						if ($bib_biblioteca == 0){
							// Seleccionó Todas las bibliotecas
							$url_sinbad = 'https://intranet.biblored.net/sinbad/ws/consultassala/'. $bib_espacio .'/' . $anno . '/' . $month . '/consulta_sala';
						}elseif ($bib_biblioteca != 0 and $bib_biblioteca > 1) {
							// Consultar tid taxonomy  de $bib_real[1]
							$url_abreviatura = 'https://intranet.biblored.net/sinbad/ws/biblioabreviatura/' . $bib_real[1];
							try {
								$response_tid = \Drupal::httpClient()->get($url_abreviatura, array('headers' => array('Accept' => 'text/plain')));
								$data_abreviatura = (string) $response_tid->getBody();
								if (empty($data_abreviatura)) {
									  return FALSE;
								}
							  }
							  catch (RequestException $e) {
								return FALSE;
							  }
							$output_tid_abreviatura = Json::decode($data_abreviatura); 
							$url_sinbad = 'https://intranet.biblored.net/sinbad/ws/consultassala/' . $output_tid_abreviatura .'/' . $anno . '/' . $month .'/consulta_sala';// (Ej. Espacio específico)
						}
					}else {
						$bib_espacio = 'All';
					}

					// Biblioteca
					// Si es todos : 0
					// Si valor > 1 and <> 0
            
                try {
        			$response = \Drupal::httpClient()->get($url_sinbad, array('headers' => array('Accept' => 'text/plain')));
        			$data = (string) $response->getBody();
        			if (empty($data)) {
          				return FALSE;
        			}
      			}
      			catch (RequestException $e) {
        			return FALSE;
      			}
            	$output_sinbad = Json::decode($data); 
				
            
            
            $output_ws = [];
            	foreach ($output_sinbad as $key => $value) {
                	
                	$output_ws[$value['abreviatura']][0] = $value['title'];
                	if ($value['album'] > 0) {
                		$output_ws[$value['abreviatura']]['ALBUM'] = $value['album'];
                    }
                	$output_ws[$value['abreviatura']]['Audiolibro'] = $value['audiolibro'];
                	$output_ws[$value['abreviatura']]['Blu-Ray'] = $value['bluray'];
                	$output_ws[$value['abreviatura']]['Braile General'] = $value['brailegeneral'];
                	$output_ws[$value['abreviatura']]['Braile Infantil'] = $value['braileinfantil'];
                	$output_ws[$value['abreviatura']]['Casetes'] = $value['casetes'];
                	$output_ws[$value['abreviatura']]['CD'] = $value['cd'];
                	$output_ws[$value['abreviatura']]['Computador Portátil'] = 0; // falta
                	$output_ws[$value['abreviatura']]['Diskete'] = $value['diskettes'];
                	$output_ws[$value['abreviatura']]['DVD'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['E-reader'] = $value['erader'];
                	$output_ws[$value['abreviatura']]['Folletos'] = $value['folleto'];
                	$output_ws[$value['abreviatura']]['Juegos de reglas'] = $value['juegoregla'];
                	$output_ws[$value['abreviatura']]['Juegos de Rol'] = 0; // falta
                	$output_ws[$value['abreviatura']]['Lámina'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Libro Electrónico'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Libro en tela'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Libro General'] = $value['librogeneral'];
                	$output_ws[$value['abreviatura']]['Libro Infantil'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Literatura Braile'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Mapas'] = $value['dvd'];	
                	$output_ws[$value['abreviatura']]['Música'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Objeto'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Partituras'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Periódicos'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Referencia'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Tablet'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['Videos'] = $value['dvd'];
                	$output_ws[$value['abreviatura']]['VHS'] = $value['vh'];
                	
                
                }
            	//var_dump($output_ws);
            	*/
            
            	$header = [
            	'espacio' => "Espacio",
            	'ALBUM' => "Album",
            	'Audiolibro' => "Audiolibro",
            	'Blu-Ray' => "Blu-Ray",
            	'Braile General' => "Braile General",
            	'Braile Infantil' => "Braile Infantil",
            	'Casetes' => "Casetes",
            	'CD' => "CD",
            	'Computador Portátil' => "PC Portátil",
            	'Diskete' => "Diskete",
            	'DVD' => "DVD",
            	'E-reader' => "E-reader",
            	'Folletos' => "Folletos",
            	'Juegos de reglas' => "Juegos de reglas",
                'Juegos de Rol' => "Juegos de Rol",
            	'Lámina' => "Lámina",
            	'Libro Electrónico' => "Libro Electrónico",
            	'Libro en tela'=> 'Libro en tela',
                'Libro General' => "Libro General",
            	'Libro Infantil' => "Libro Infantil",
            	'Literatura Braile' => "Literatura Braile",
            	'Mapas' => "Mapas",
                'Música'=>"Música",
                'Musicales'=>"Musicales",
                'Instrumentos musicales'=>"Instrumentos musicales",
            	'Objeto' => "Objeto",
            	'Partituras' => "Partituras",
                'Periódicas' => "Periódicas",
            	'Periódicos' => "Periódicos",
            	'Referencia' => "Referencia",
            	'Tablet' => "Tablet",
            	'Videos' => "Videos",
            	'VHS' => "VHS",
            	'Total' => "Total",
                ];
                
            	foreach ($array_datos as $key => $value)
        		{
                	
                	$output[] = [
            	   'espacio' => $value[0],
		           'ALBUM' => $value["ALBUM"],
		           'Audiolibro' => $value["Audiolibro"],
            	   'Blu-Ray' => $value["Blu-Ray"],
            	   'Braile General' => $value["Braile General"],
            	   'Braile Infantil' => ($value["Braile Infantil"] + $value["Infantil Braile"]),
            	   'Casetes' => $value["Casetes"],
            	   'CD' => $value["CD"],
            	   'Computador Portátil' => $value["Computador Portátil"],
            	   'Diskete' => $value["Diskete"],
            	   'DVD' => $value["DVD"],
            	   'E-reader' => $value["E-reader"],
            	   'Folletos' => $value["Folletos"],
            	   'Juegos de reglas' => $value["Juegos de reglas"],
                   'Juegos de Rol' => $value["Juegos de Rol"], 
            	   'Lámina' => $value["Lámina"],
            	   'Libro Electrónico' => $value["Libro Electrónico"],
            	   'Libro en tela'=> $value['Libro en tela'],
                   'Libro General' => $value["Libro General"],
            	   'Libro Infantil' => $value["Libro Infantil"],
            	   'Literatura Braile' => $value["Literatura Braile"],
            	   'Mapas' => $value["Mapas"],
                   'Música' => $value["Música"],
                   'Musicales' => $value["Musicales"], 
                   'Instrumentos musicales' => $value["Instrumentos musicales"], 
            	   'Objeto' => $value["Objeto"],
            	   'Partituras' => $value["Partituras"],
                   'Periódicas' => $value["Periódicas"], 
            	   'Periódicos' => $value["Periódicos"],
            	   'Referencia' => $value["Referencia"],
            	   'Tablet' => $value["Tablet"],
            	   'Videos' => $value["Videos"],
            	   'VHS' => $value["VHS"],
            	   'Total' => $value["Total"],
	         		];
                
                
            	}
                    
            		//var_dump($output);
            		
            		$output['t'] = [
            	   'espacio' => "Totales",
		           'ALBUM' => $total["ALBUM"],
		           'Audiolibro' => $total["Audiolibro"],
            	   'Blu-Ray' => $total["Blu-Ray"],
            	   'Braile General' => $total["Braile General"],
            	   'Braile Infantil' => $total["Braile Infantil"],
            	   'Casetes' => $total["Casetes"],
            	   'CD' => $total["CD"],
            	   'Computador Portátil' => $total["Computador Portátil"],
            	   'Diskete'=> $total["Diskete"],
            	   'DVD' => $total["DVD"],
            	   'E-reader' => $total["E-reader"],
            	   'Folletos' => $total["Folletos"],
            	   'Juegos de reglas' => $total["Juegos de reglas"],
                   'Juegos de Rol' => $total["Juegos de Rol"], 
            	   'Lámina' => $total["Lámina"],
            	   'Libro Electrónico' => $total["Libro Electrónico"],
            	   'Libro en tela'=> $total['Libro en tela'],
                   'Libro General' => $total["Libro General"],
            	   'Libro Infantil' => $total["Libro Infantil"],
            	   'Literatura Braile' => $total["Literatura Braile"],
            	   'Mapas' => $total["Mapas"],
                   'Música' => $total["Música"], 
                   'Musicales' => $total["Musicales"],  
                   'Instrumentos musicales' => $total["Instrumentos musicales"],  
            	   'Objeto' => $total["Objeto"],
            	   'Partituras' => $total["Partituras"],
                   'Periódicas' => $total["Periódicas"], 
            	   'Periódicos' => $total["Periódicos"],
            	   'Referencia' => $total["Referencia"],
            	   'Tablet' => $total["Tablet"],
            	   'Videos' => $total["Videos"],
            	   'VHS' => $total["VHS"],
            	   'Total' => $total["Total"],
	         		];
                //var_dump($output[t]);
			} // End of else
			
		} // End of if
		$form['exportar'] = [
      		'#type' => 'processed_text',
      		'#text' => "<a id='exportarexcel' href='javascript:;'>Exportar Excel</a>",
      		'#format' => 'full_html',
    	];
    
    	$form['historico'] = [
      	'#type' => 'processed_text',
      	'#text' => "<h3><br />Para consultar reportes anteriores a abril de 2020, ingrese por el siguiente enlace:<br /><a id='historico' href='http://intranet.biblored.net/sinbad/informes/prestamos-consultas-en-sala?field_tipo_prest_sala_value=consulta_sala'>histórico</a></h3>",
      	'#format' => 'full_html',
    	];
    
    	$form['bibliografico'] = [
      	'#type' => 'processed_text',
      	'#text' => "<h3>Datos del sistema bibliográfico:</h3>",
      	'#format' => 'full_html',
    	];
    
		$form['table'] = [
	    '#type' => 'tableselect',
	    '#header' => $header,
	    '#options' => $output,
	    '#suffix' => '<h4>Utilice la tecla de flecha a la derecha para desplazarse por todo el contenido -&raquo;</h4>',
	    '#empty' => t('No actividades encontradas'),
	    ]; 
    /*
    	$form['interno'] = [
      	'#type' => 'processed_text',
      	'#text' => "<h3>Datos ingresados manualmente:</h3>",
      	'#format' => 'full_html',
    	];
        */
		unset($bloq, $total, $value, $array_datos, $result, $client, $url, $header, $output);
	  return $form;
		 //Fin del cometario de suspensión
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
	  
	  $tid_biblioteca = $form_state->getValue('biblioteca');
	  $mes =  $form_state->getValue('month');
	  $year =  $form_state->getValue('year');

	  $form_state->setRebuild();

	}

	function getBibliotecas($form, FormStateInterface $form_state) {
	  return $form['filtro']['biblioteca_2']; 
	}
	/**
   * Get options for second field.
   */
	public function getOptionsBibliotecasAleph(FormStateInterface $form_state) {

	  $bib = new EvEndpoint;

	  $options = $bib->bibliotecas_aleph($form_state->getValue('biblioteca_1'));  
	  
	  return $options;

  	}
}
