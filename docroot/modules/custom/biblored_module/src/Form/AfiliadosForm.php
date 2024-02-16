<?php
/**
* @file
* Contains Drupal\conteo\form\ConteoForm
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

use Drupal\Core\Block\BlockBase;
//use \SoapClient;
use Drupal\biblored_module\lib\nusoap as nusoap_client;

class AfiliadosForm extends FormBase
{
	/**
	* {@inheritdoc}
	*/

	public function getFormId()
	{
		return 'biblored_module_afiliadosform'; //nombremodule_nombreformulario	
	}

	public function buildForm(array $form, FormStateInterface $form_state) 
	{
		$nusoap_path = drupal_get_path('module', 'biblored_module') . '/src/lib/nusoap.php';
    	   
    
		$vid = 'nodos_bibliotecas';
 		$total = 0;
    	$header = [];
    	$output = [];
		$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
		$bibliotecas[0] = "Todas";
		foreach($terms as $term) {
		    $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue();
        	$tid_biblioteca_agenda = isset($tid_biblioteca_agenda[0]) ? $tid_biblioteca_agenda[0]['value'] : null;
		    $sigla_biblioteca = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_abreviatura_de_la_bibliote')->getValue();
        	$sigla_biblioteca = isset($sigla_biblioteca[0]['value']) ? $sigla_biblioteca[0]['value'] : null;
        	//print_r($sigla_biblioteca);
		    if ($term->depth == 1) {
	    	     // Array con todas las bibliotecas
	    	    $dato_tid_bib = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue();
	            $term_data[] = array(
                 "id" => $term->tid,
                 "name" => $term->name,
                 'tid_biblioteca_agenda' => isset($dato_tid_bib[0]) ? $dato_tid_bib[0]['value'] : null,
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
	  $bibliotecas_1['All'] = "Todos los espacios de la Red"; 
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
//var_dump($bibliotecas_1);
	/*$form['biblioteca'] = array (
	   '#type' => 'select',
	   '#title' => ('Biblioteca&nbsp; &nbsp;'),
	   '#options' => $bibliotecas,
	 );*/
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
	      '#prefix' => '<div class="col-md-6 mallaespacio">',
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
	      '#prefix' => '<div class="col-md-6" id="bibliotecas-wrapper-espacio">',
	      '#suffix' => '</div>',
	      
	  ];
	$form['fechaini'] = array(
		 '#type' => 'date',
		 '#title' => $this->t('Fecha inicio &nbsp;'),
    	 '#required' => TRUE,
    	  '#prefix' => '<div class="col-md-12">',
	      '#suffix' => '</div>',
		);
	$form['fechafin'] = array(
	 '#type' => 'date',
	 '#title' => $this->t('Fecha fin &nbsp; &nbsp;&nbsp;&nbsp; &nbsp;'),
     '#required' => TRUE,
      '#prefix' => '<div class="col-md-12">',
	  '#suffix' => '</div>',
	);
	

	$form['actions'] = [
	  '#type' => 'actions',
	  'submit' => [
	      '#type' => 'submit',
	      '#value' => $this->t('Consultar'),
	  ],
    
	];
	
	// Evaluar al presionar clic en Enviar se ha seleccionado Biblioteca
  	
	if($form_state->getValue('biblioteca_1') && $form_state->getValue('fechaini') && $form_state->getValue('fechafin') ) 
    {
		//echo "bib1:".$form_state->getValue('biblioteca_1');
		$array_datos = array();
    	$total = array(0=>0);
        $w = 0;
    	//'Iniciales'=>array(0=>'Nombre', 1=>'Femeninas', 2=>'Masculinas', 3=>'Otros', 4=>'Instituciones', 5=>'Cat A', 6=>'Cat B', 7=>'Total afiliaciones', 8=>'Renovaciones', 9=>'Vencimientos')
    	$bloq = false;
    	if($form_state->getValue('biblioteca_2'))
        {
        	//echo "bib2:".$form_state->getValue('biblioteca_2');
    		$bloq = true;
        	$biblioteca = $form_state->getValue('biblioteca_2');
        	$array_b = str_replace("'", "",$biblioteca);
        	$array_b = explode(",", $array_b);
        	$w = "";
        	foreach($array_b as $bib)
            {
            
            	$bib_real = explode(":",$bib);
            	$array_datos[trim($bib_real[0])] =  array(0=>$bib_real[1], 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0);
            	$w .= "'".$bib_real[0]."',"; 
          	}
        }
    	$fi = $form_state->getValue('fechaini');
	    $ff = $form_state->getValue('fechafin');
         
		$year = explode("-", $fi);
		$fir = $year[2]."/".$year[1]."/".$year[0];
		unset($year);
		$year = explode("-", $ff);
		$ffr = $year[2]."/".$year[1]."/".$year[0];
		unset($year);
    	
    	//$url = "http://52.188.80.217/pergamum/web_service/integracao_sever_ws.php?wsdl";
    	
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
        //\Drupal::service('page_cache_kill_switch')->trigger(); 
    	//$client = new nusoap_client($url);
    	//$client = new nusoap_client($url, true); 
    	$client = new \nusoap_client($url, false);
    
    	$client->soap_defencoding = 'UTF-8';
    	
		//$client = new SoapClient($url, $options);
		$result = $client->call('ws_biblored_sinbad', array('etapa' => "3",	
																  'data_ini' => "".$fir ."",
																  'data_fim' => "".$ffr ."",
																  'chave' => "a8ad1f47da75e86d6511235a6a6c3b7d"));
    	//var_dump($result);
    //$result = json_decode($result);
      
    	if (!$result)
        {
	     	$conn_val = array('valor' => false );  
	    }
	    else 
	    {
    		$result = explode("\n", utf8_encode($result)); 
	      	$conn_val = array('valor' => true );
	      	foreach($result as $rows)  
			{
				$row = explode(",", $rows);
          		//$result = 0=>Id, 1=>'Biblioteca', 2=>'Iniciales, 3=>'Femeninas', 4=>'Masculinas',5=>'Otro', 6=>'Cat a', 7=>'Institucionales'
				if(sizeof($row) == 8 && !isset($array_datos[trim($row[2])]) && $bloq == false)
				{
					$array_datos[trim($row[2])] = array(0=>$row[1], 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0);
				}
			   	if(sizeof($row) == 8 && isset($array_datos[trim($row[2])]))
                {
                    	$subt = $row[3] + $row[4] + $row[5];
                    	$array_datos[trim($row[2])][1] += $row[3];
                    	$array_datos[trim($row[2])][2] += $row[4];
                    	$array_datos[trim($row[2])][3] += $row[5];
                    	$array_datos[trim($row[2])][4] += $row[7];
                    	$array_datos[trim($row[2])][5] += $row[6];
                    	$array_datos[trim($row[2])][6] += ($subt - $row[6]);
                    	$array_datos[trim($row[2])][7] += ($subt + $row[7]);
                }
          	}
          	//Renovaciones
        	$result = $client->call('ws_biblored_sinbad', array('etapa' => "2",	
																  'data_ini' => "".$fir ."",
																  'data_fim' => "".$ffr ."",
																  'chave' => "a8ad1f47da75e86d6511235a6a6c3b7d"));
        	
        	$result = explode("\n", utf8_encode($result));   	
        	//var_dump($result);
        	foreach($result as $rows)  
			{
				$row = explode(",", $rows);
            	//$result = 0=>Id, 1=>'Biblioteca', 2=>'Iniciales, 3=>'Renovaciones'
            	if(sizeof($row) == 4 && !isset($array_datos[trim($row[2])]) && $bloq == false)
				{
					$array_datos[trim($row[2])] = array(0=>$row[1], 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0);
				}
			   	if(sizeof($row) == 4 && isset($array_datos[trim($row[2])]))
                {
                    	$array_datos[trim($row[2])][8] += $row[3];
                }
          	}
            //Vencimientos
            /*
          	$result = $client->call('ws_biblored_sinbad', array('etapa' => "1",	
																  'data_ini' => "".$fir ."",
																  'data_fim' => "".$ffr ."",
																  'chave' => "a8ad1f47da75e86d6511235a6a6c3b7d"));
        	
        	$result = explode("\n", utf8_encode($result));   	
        	foreach($result as $rows)  
			{
				$row = explode(",", $rows);
            	//$result = 0=>Id, 1=>'Biblioteca', 2=>'Iniciales, 3=>'Vencimientos'
            	if(sizeof($row) == 4 && !isset($array_datos[trim($row[2])]) && $bloq == false)
				{
					$array_datos[trim($row[2])] = array(0=>$row[1], 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0);
				}
			   	if(sizeof($row) == 4 && isset($array_datos[trim($row[2])]))
                {
                    	$array_datos[trim($row[2])][9] += $row[3];
                }
          	}
            */
          	$w = substr($w, 0, -1);
          
          	$header = [
                  'espacio' => t('Espacio'),
                  'femeninas' => t('Femenino'),
                  'masculinas' => t('Masculino'),
                  'otros' => t('Otro'),
                  'institucionales' => t('Institucional'),
            	  'totalafiliados' => t('Total afiliados'),
                  'categoriaa' => t('Categoría A (Menores a 8 años)'),
                  'categoriab' => t('Categoría B (Desde los 8 años)'),
                  'renovaciones' => t('Renovaciones'),
          		  //'vencimientos' => t('Vencimientos'),
          ];
           
        $total_fem = 0;
        $total_masc = 0;
        $total_otro = 0;  
        $total_inst = 0;
        $total_afil = 0;
        $total_cata = 0;
        $total_catb = 0;
        //$total_venc = 0;
 		$total_reno = 0;
	    foreach ($array_datos as $key => $value)
        {
        	$output[] = [
            	   'espacio' => $value[0],
		           'femeninas' => isset($value[1]) ? $value[1] : "0",
		           'masculinas' => isset($value[2]) ? $value[2] : "0", 
            	   'otros' => isset($value[3]) ? $value[3] : "0",	
		           'institucionales' => isset($value[4]) ? $value[4] : "0",
		           'totalafiliados' => isset($value[7]) ? $value[7] : "0",
            	   'categoriaa' => isset($value[5]) ? $value[5] : "0",
		           'categoriab' => isset($value[6]) ? $value[6] : "0",
		           'renovaciones' => isset($value[8]) ? $value[8] : "0", 
            	   //'vencimientos' => isset($value[9]) ? $value[9] : "0",
		           
	         	];
        	$total_fem += $value[1];
        	$total_masc+= $value[2];
        	$total_otro+= $value[3];
        	$total_inst+= $value[4];
        	$total_afil+= $value[7];
        	$total_cata+= $value[5];
        	$total_catb+= $value[6];
        	$total_reno+= $value[8]; 
        	//$total_venc+= $value[9];
 			      
        }
        $output['t'] = [
            	   'espacio' => 'Totales',
		           'femeninas' => $total_fem,
		           'masculinas' => $total_masc,
        		   'otros' => $total_otro,
		           'institucionales' => $total_inst,
		           'totalafiliados' => $total_afil,
        		   'categoriaa' => $total_cata,
		           'categoriab' => $total_catb,
		           'renovaciones' => $total_reno,
        		   //'vencimientos' => $total_venc,
	         	];
        
	    } //Fin del else de conexión

	}
    if (!isset($total_afil)) {
    	$total_afil = "";
    }
	else
    {
    	$form['historico'] = [
      	'#type' => 'processed_text',
      	'#text' => "<h3>Para consultar reportes anteriores a abril de 2020, ingrese por el siguiente enlace:<br /><a id='historico' href='https://intranet.biblored.net/sinbad/informes/historicos-afiliados-renovaciones'>histórico</a></h3>",
      	'#format' => 'full_html',
    	];
   		 $form['table'] = [
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $output,
        '#suffix' => '<h3>Total afiliaciones: ' . $total_afil . ' </h3><br /><div>** Vencimientos y Renovaciones incluyen usuarios e instituciones</div>',
        '#empty' => t('No hay información relacionada'),
        ]; 

   		$form['exportar_a'] = [
     	'#type' => 'processed_text',
      	'#text' => "<a id='exportarexcel' href='javascript:;'>Exportar Excel</a>",
      	'#format' => 'full_html',
    	];
    }
	  unset($total, $total_fem, $total_masc, $total_otro, $total_inst, $total_cata, $total_catb, $total_afil, $total_reno, $total_venc, $value, $array_datos, $result, $client, $url);	
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

	
	 \Drupal::messenger()->addMessage(
           $this->t('Valores: @year / @month / @biblioteca ',  
      [ '@year' => $form_state->getValue('year'),
      '@month' => $form_state->getValue('month'),
      '@biblioteca' => $form_state->getValue('biblioteca'),
      ])
         );

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
	  //$options['All'] = "Todos los espacios";  
	  $bib = new EvEndpoint;
	$all_opt = $form_state->getValue('biblioteca_1');  
    if($all_opt == 'All')
    	$options = $bib->bibliotecas_aleph($form_state->getValue('biblioteca_1'));  
    else
	  $options = $bib->bibliotecas_aleph($form_state->getValue('biblioteca_1'));  
	  
	  return $options;

  	}

}



