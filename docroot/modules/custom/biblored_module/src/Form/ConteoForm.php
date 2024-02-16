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
class ConteoForm extends FormBase
{
	/**
	* {@inheritdoc}
	*/
	public function getFormId()
	{
		return 'biblored_module_conteoform'; //nombremodule_nombreformulario	
	}

	public function buildForm(array $form, FormStateInterface $form_state) 
	{
		/*$vid = 'nodos_bibliotecas';
 
		$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
		$bibliotecas['TODOS'] = "Todos los espacios";
		foreach($terms as $term) 
        {
		    $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];
		    $sigla_biblioteca = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_abreviatura_de_la_bibliote')->getValue()[0]['value'];
		    if ($term->depth == 1) 
            {
	    	     // Array con todas las bibliotecas
	             $term_data[] = array(
                 "id" => $term->tid,
                 "name" => $term->name,
                 'tid_biblioteca_agenda' => \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'],
                 'sigla' => $sigla_biblioteca,
	             );
		         // Creando un array con la bibliotecas que tengan equivalencia (en este caso para el filtro sql necesitamos el la sigla biblioteca)
		         if (!empty($tid_biblioteca_agenda))
                 {
		         	$bibliotecas[$sigla_biblioteca] = $term->name;
		         }
		     }
		}
		$form['biblioteca'] = array (
	    '#type' => 'select',
	    '#title' => ('Espacio &nbsp; &nbsp;'),
	    '#options' => $bibliotecas,
        '#required' => TRUE,
	 	); */
        //Adiiconado para separar espacios
    	$vid = 'nodos_bibliotecas';
 
		$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
		$bibliotecas['TODOS'] = "Todos los espacios";
		foreach($terms as $term) 
        {
		    $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue();
        	$tid_biblioteca_agenda = isset($tid_biblioteca_agenda[0]) ? $tid_biblioteca_agenda[0]['value'] : "";
		    $sigla_biblioteca = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_abreviatura_de_la_bibliote')->getValue();
        	$sigla_biblioteca = isset($sigla_biblioteca[0]) ? $sigla_biblioteca[0]['value'] : "";
		    
        	if($term->depth == 1) 
            {
	    	     // Array con todas las bibliotecas
	    	    $tid_bib = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue();
            	$tid_bib = isset($tid_bib[0]) ? $tid_bib[0]['value'] : "";
	            $term_data[] = array(
                 "id" => $term->tid,
                 "name" => $term->name,
                 'tid_biblioteca_agenda' => $tid_bib,
                 'sigla' => $sigla_biblioteca,
	             );
		         // Creando un array con la bibliotecas que tengan equivalencia (en este caso para el filtro sql necesitamos el la sigla biblioteca)
		        if(!empty($tid_biblioteca_agenda))
                {
		            $bibliotecas[$sigla_biblioteca] = $term->name;
		        }
		     }
		}
		// BIBLIOTECAS CON SIGLAS
      	$vid = 'nodos_bibliotecas';
      	$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
      	$bibliotecas_1['TODOS'] = "Todos los espacios"; 
      	foreach($terms as $term) 
        {  
            if($term->depth == 0) 
            { // 0 PARA EL PADRE
               // Array con todas las bibliotecas
               $term_data[] = array(
                 "id" => $term->tid,
                 "name" => $term->name,
               );
               $bibliotecas_1[$term->tid] = $term->name;
            }
       }

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
          //'#required' => FALSE,
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
	    '#value' => $this->t('Enviar'),
	  	],
		];
	
		// Evaluar al presionar clic en Enviar se ha seleccionado Biblioteca
		if($form_state->getValue('biblioteca_1') && $form_state->getValue('fechaini') && $form_state->getValue('fechafin') ) 
        {
			$espacio = $form_state->getValue('biblioteca_1');
			$fi = $form_state->getValue('fechaini');
			$ff = $form_state->getValue('fechafin');
			$element = null; 
	    	$result = null;
	    	$user = "postgres";
	    	$password = "developer2014";
	    	$dbname = "nautillus";
	    	$port = "5434";
	    	$host = "192.168.200.55";
            $outputs = array();
        
	    	$cadenaConexion = "host=$host port=$port dbname=$dbname user=$user password=$password";
	    	$conexion = pg_connect($cadenaConexion); // or die("Error en la Conexión: ".pg_last_error());
	    	if(!$conexion)
        	{
	        	$conn = array('valor' => false );
	    	}
    		else
        	{
				$conn = array('valor' => true );
				$db = \Drupal\Core\Database\Database::getConnection('default', 'nautillus');
    
				if($espacio == 'TODOS')
            	{
            		//Todos los espacios creados
                	$sql = "SELECT datos.nombiblioteca as biblioteca, datos.conteo as sicuenta, 
    				SUM(CASE WHEN g_movconteoid in (1,3) THEN g_cantidad ELSE 0 END) AS TE, datos.clasificacion 
    				FROM g_historialconteo
    				RIGHT OUTER JOIN (SELECT puntos.puntoid, puntos.tipoconteo as conteo, empresa.sucursal as nombiblioteca, empresa.codi_tien as clasificacion             
            			FROM empresa 
            			JOIN puntos ON puntos.biblioteca = empresa.empresaid    
            			GROUP BY puntos.puntoid, puntos.tipoconteo, empresa.sucursal, empresa.codi_tien) 
    				datos ON (g_historialconteo.g_antenaid = datos.puntoid
     				AND g_historialconteo.g_fechalect BETWEEN '$fi 00:00:00' AND '$ff 23:59:59')
     				GROUP BY datos.nombiblioteca, datos.conteo, datos.clasificacion 
                    ORDER BY datos.clasificacion, datos.nombiblioteca";
            	
            		$result = $db->query($sql);
					$resultados = $result->fetchAll();
            		$header = [
            		'biblioteca' => t('Biblioteca / Espacio'),
            		'valor' => t('Ingresos registrados'),
       				];
       				$totales = 0;
        			$subtales = 0;
        			foreach ($resultados as $record) 
        			{
                    if($record->sicuenta == true)
                    {
                        $output[] = [
                        'biblioteca' => $record->biblioteca,
                        'valor' => $record->te,    
                        ];
                        $totales += $record->te;
                    }
                    else
                    {
                        $outputs[] = [
                        'biblioteca' => $record->biblioteca,   
                        'valor' => $record->te,    
                        ];
                        $subtales += $record->te;
                    }
                	}
            	}
            	else if($form_state->getValue('biblioteca_2'))
                {
                	$biblioteca = $form_state->getValue('biblioteca_2');
                	$biblioteca = str_replace("'","",$biblioteca);
                	$num_esp = explode(":", $biblioteca);
                	if(sizeof($num_esp) > 4)
                    {
                	//Un grupo de espacios (biblioteca, bibloestacion, ppp)
                	$tip_esp = "";
                	if($espacio == 352)
            			$tip_esp = "BIB";
        			if($espacio == 353)
            			$tip_esp = "BIE";
        			if($espacio == 354)
            			$tip_esp = "PPP";
                	$sql = "SELECT datos.nombiblioteca as biblioteca, datos.conteo as sicuenta, 
    				SUM(CASE WHEN g_movconteoid in (1,3) THEN g_cantidad ELSE 0 END) AS TE
    				FROM g_historialconteo
    				RIGHT OUTER JOIN (SELECT puntos.puntoid, puntos.tipoconteo as conteo, empresa.sucursal as nombiblioteca             
            			FROM empresa 
            			JOIN puntos ON puntos.biblioteca = empresa.empresaid   
                        WHERE empresa.codi_tien = '$tip_esp' 
            			GROUP BY puntos.puntoid, puntos.tipoconteo, empresa.sucursal) 
    				datos ON (g_historialconteo.g_antenaid = datos.puntoid
     				AND g_historialconteo.g_fechalect BETWEEN '$fi 00:00:00' AND '$ff 23:59:59')
     				GROUP BY datos.nombiblioteca, datos.conteo
                    ORDER BY datos.nombiblioteca";
            	
            		$result = $db->query($sql);
					$resultados = $result->fetchAll();
            		$header = [
            		'biblioteca' => t('Biblioteca / Espacio'),
            		'valor' => t('Ingresos registrados'),
       				];
       				$totales = 0;
        			$subtales = 0;
        			foreach ($resultados as $record) 
        			{
                    if($record->sicuenta == true)
                    {
                        $output[] = [
                        'biblioteca' => $record->biblioteca,
                        'valor' => $record->te,    
                        ];
                        $totales += $record->te;
                    }
                    else
                    {
                        $outputs[] = [
                        'biblioteca' => $record->biblioteca,   
                        'valor' => $record->te,    
                        ];
                        $subtales += $record->te;
                    }
                	}
                	}
                	else 
                	{
        	 		//Un espacio en particular
                	
                	$sql = "SELECT datos.nombiblioteca as biblioteca, datos.puerta as nombre, datos.conteo as sicuenta,
    				SUM(CASE WHEN g_movconteoid in (1,3) THEN g_cantidad ELSE 0 END) AS TE
    				FROM g_historialconteo
    				RIGHT OUTER JOIN (SELECT puntos.puntoid, puntos.nombre as puerta, puntos.tipoconteo as conteo, empresa.sucursal as nombiblioteca               
            			FROM empresa 
            			JOIN puntos ON puntos.biblioteca = empresa.empresaid     
                        WHERE empresa.web = '$num_esp[0]'
            			GROUP BY puntos.puntoid, puntos.nombre, puntos.tipoconteo, empresa.sucursal) 
    				datos ON (g_historialconteo.g_antenaid = datos.puntoid
     				AND g_historialconteo.g_fechalect BETWEEN '$fi 00:00:00' AND '$ff 23:59:59')
     				GROUP BY datos.nombiblioteca, datos.puerta, datos.conteo"; 
            
            		$result = $db->query($sql);
					$resultados = $result->fetchAll();
            		$header = [
            		'biblioteca' => t('Biblioteca / Espacio'),
            		'espacio' => t('Espacio / Sala'),
            		'valor' => t('Ingresos registrados'),
       				];
       				$totales = 0;
        			$subtales = 0;
        			foreach ($resultados as $record) 
        			{
            		if($record->sicuenta == true)
            		{
                		$output[] = [
                    	'biblioteca' => $record->biblioteca,
                    	'espacio' => $record->nombre,        
                    	'valor' => $record->te,    
                		];
                		$totales += $record->te;
            		}
            		else
            		{
                		$outputs[] = [
                    	'biblioteca' => $record->biblioteca,
                    	'espacio' => $record->nombre,    
                    	'valor' => $record->te,    
                		];
               			$subtales += $record->te;
            		}
        			}
					}
                }
				//echo $sql;
			}
		//}

		if(sizeof($output) > 0) 
		{
			$form['table'] = [
		    '#type' => 'tableselect',
		    '#prefix' => '<br /><h3>Espacios principales</h3>',
		    '#header' => $header,
		    '#options' => $output,
		    '#suffix' => '<h3><span class="alert alert-success alert-dismissible">Total visitas espacios principales: '.$totales.'</span></h3>',
		    '#empty' => t('No actividades encontradas'),
		    ]; 
		}
    	if(sizeof($outputs) == 0) 
	 	{
        	$outputs[] = [
            'biblioteca' => '',
            'espacio' => '',    
            'valor' => 0,    
                		];
        }
	 	if(sizeof($outputs) > 0) 
	 	{
	  		$forms['table2'] = [
	    	'#type' => 'tableselect',
        	'#prefix' => '<hr align="center" width="90%"><br /><h4>Espacios secundarios</h4>',
	    	'#header' => $header,
	    	'#options' => $outputs,
	    	'#suffix' => '<h3><span class="alert alert-success alert-dismissible">Total visitas espacios secundarios: '.$subtales.'</span></h3>'.
        	'<br /><div>** Datos ya incluidos en el conteo de espacio principal, se muestra la información para análisis interno en la biblioteca o espacio.</div><br />'.
        	'<h4><a href="https://intranet.biblored.net/conteo/export.php?startdate='.$fi.'&enddate='.$ff.'" title="Consolidado BibloRed" id="exportarexcel"><br /></a>'.
        	'<a href="https://intranet.biblored.net/conteo/export.php?startdate='.$fi.'&enddate='.$ff.'" title="Consolidado BibloRed">Exportar consolidado completo a Excel</a></h4>'.
        	'<div>** Archivo completo con entradas y salidas por cada espacio creado en el sistema.</div><br />',
		    ];
	  		array_push($form, $forms);
		}  
        }
	 	//unset($forms, $header, $consulta, $total, $i, $key);
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

	public function getOptionsBibliotecasAleph(FormStateInterface $form_state) {

	  $bib = new EvEndpoint;

	  $options = $bib->bibliotecas_aleph($form_state->getValue('biblioteca_1'));  
	  
	  return $options;

  	}

}