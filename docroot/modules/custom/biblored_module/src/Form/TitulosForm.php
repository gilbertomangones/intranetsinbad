<?php
/**
* @file
* Contains Drupal\biblored_module\form\TitulosForm
*/

namespace Drupal\biblored_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
* 
*/
class TitulosForm extends FormBase
{
	/**
	* {@inheritdoc}
	*/

	public function getFormId()
	{
		return 'biblored_module_titulosform'; //nombremodule_nombreformulario	
	}

	public function buildForm(array $form, FormStateInterface $form_state) 
	{
		$vid = 'nodos_bibliotecas';
 
		$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
		$bibliotecas[0] = "Todas";
		
		foreach($terms as $term) {
		    $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue()[0]['value'];
		    $sigla_biblioteca = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_abreviatura_de_la_bibliote')->getValue()[0]['value'];
		    //echo $sigla_biblioteca."<br />"; //Iniciales de la biblioteca en Aleph
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
	$year[date('Y')] = date('Y');
	  for ($i=date('Y'); $i > 2018; $i--) { 
	    $year[$i] = $i;
	}

	$form['biblioteca'] = array (
	   '#type' => 'select',
	   '#title' => ('Biblioteca'),
	   '#options' => $bibliotecas,
	 );

	$form['month'] = array (
	   '#type' => 'select',
	   '#title' => ('Mes'),
	   '#options' => $month,
	 );

	$form['year'] = array (
	   '#type' => 'select',
	   '#title' => ('Año'),
	   '#options' => $year,
	);

	$form['actions'] = [
	  '#type' => 'actions',
	  'submit' => [
	      '#type' => 'submit',
	      '#value' => $this->t('Enviar'),
	  ],
	];
	
	
	// Evaluar al presionar clic en Enviar se ha seleccionado Biblioteca
  	
	//if ($form_state->getValue('biblioteca') || $form_state->getValue('month') || $form_state->getValue('year') ) 
	{

		$biblioteca = $form_state->getValue('biblioteca');
		
		$month = $form_state->getValue('month');

		$year = $form_state->getValue('year');
       
        $numero_dias = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        if($month < 10)
	          $month = "0".$month;
	    $fi = $year.'-'.$month.'-'.'01';
	    $ff = $year.'-'.$month.'-'.$numero_dias;
		/*$element = null; 
	    $result = null;*/
	    	   
		\Drupal\Core\Database\Database::setActiveConnection('blaa');
		$db = \Drupal\Core\Database\Database::getConnection();
		$sql = "SELECT COUNT(*) AS Total, inter.Titulo, consecutivos.Biblioteca FROM inter, consecutivos WHERE consecutivos.Id_Biblioteca = inter.Biblioteca GROUP BY inter.Titulo";	
		if(strlen($biblioteca) > 0)
		{
			if($month > 0)
			{
				$sql = "select count(inter.Id) as Total, inter.Estado, inter.Biblioteca from inter, consecutivos where consecutivos.Aleph = '".$biblioteca."'  and inter.Biblioteca = consecutivos.Id_Biblioteca and inter.Fecha_S between '".$fi."' and '".$ff."' group by inter.Estado";
			}
			else
			{
				$sql = "select count(inter.Id) as Total, inter.Estado, inter.Biblioteca from inter, consecutivos where consecutivos.Aleph ='".$biblioteca."' group by inter.Estado";
			}
		}
		else
		{
			if($month > 0)
			{
				$sql = "select count(inter.Id) as Total, Estado, Biblioteca from inter where Fecha_S between '".$fi."' and '".$ff."' group by Biblioteca, Estado";
			}
		}
		//echo $sql;
		
		$resul = array(0=>0, 1=>0);
		
		$result = $db->query($sql);
		$resultados = $result->fetchAll();
		
	// Switch back
		\Drupal\Core\Database\Database::setActiveConnection();
	    
		$header = [
	      'bib' => t('Biblioteca'),
	      'tt' => t('Título'),
	      'ts' => t('Solicitudes'),

	    ];

	    foreach ($resultados as $record) {	
	    	$n_bib[$record->Biblioteca][$record->Estado] = $record->Total;    
	    	$n_bib[$record->Biblioteca][0] += $record->Total;
	    }

	    for($i=0; $i<sizeof($n_bib); $i++)
		{
			  if(isset($n_bib[$i]) && $n_bib[$i][0] > 0)
			  {
			  	$outputs[] = [
		    		'bib' => $n_bib[$i][6],
				      'ts' => $n_bib[$i][0],
				      'tp' => $n_bib[$i][3],
					  'tv' => $n_bib[$i][4],
				      't1' => $n_bib[$i][2],
				      'sc' => $n_bib[$i][5],   
	         	];
	         	$resul[0] += $n_bib[$i][0];
				$resul[1] += $n_bib[$i][2];
				$resul[2] += $n_bib[$i][3];
				$resul[3] += $n_bib[$i][4];
				$resul[4] += $n_bib[$i][5];
			  
		      } 

		}

		$outputs[] = [
    		'bib' => 'Total',
		      'ts' => $resul[0],
		      'tp' => $resul[1],
			  'tv' => $resul[2],
		      't1' => $resul[3],
		      'sc' => $resul[4],   
     	]; 
		  
	    
	    //var_dump($n_bib);
	}
	
	//exit;
	$form['table'] = [
	    '#type' => 'tableselect',
	    '#header' => $header,
	    '#options' => $outputs,
	    '#empty' => t('No actividades encontradas'),
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

}