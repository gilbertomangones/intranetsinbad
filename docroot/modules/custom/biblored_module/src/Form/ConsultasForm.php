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

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;

use Drupal\Core\Url;
use Drupal\Core\Link;

class ConsultasForm extends FormBase
{
	/**
	* {@inheritdoc}
	*/

	public function getFormId()
	{
		return 'biblored_module_consultasform'; //nombremodule_nombreformulario	
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

		$form['biblioteca'] = array (
		   '#type' => 'select',
		   '#title' => ('Biblioteca&nbsp; &nbsp;'),
		   '#options' => $bibliotecas,
		 );

		/* //Modelo de mes - año
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
		*/

		$form['fechaini'] = array(
		 '#type' => 'date',
		 '#title' => $this
		   ->t('Fecha inicio &nbsp;'),
		);
		$form['fechafin'] = array(
		 '#type' => 'date',
		 '#title' => $this
		   ->t('Fecha fin &nbsp; &nbsp;&nbsp;&nbsp; &nbsp;'),
		);

		$form['actions'] = [
		  '#type' => 'actions',
		  'submit' => [
		      '#type' => 'submit',
		      '#value' => $this->t('Enviar'),
		  ],
		];


		// Evaluar al presionar clic en Enviar se ha seleccionado Biblioteca
  	
		if ( $form_state->getValue('biblioteca') && $form_state->getValue('fechaini') && $form_state->getValue('fechafin') ) {

			$biblioteca = $form_state->getValue('biblioteca');

			$month = $form_state->getValue('month');

			$year = $form_state->getValue('year');

			$numero_dias = cal_days_in_month(CAL_GREGORIAN, $month, $year);

			$db ="(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP) (HOST = 216.147.208.153) (PORT = 11521) )(CONNECT_DATA = (SID = ALEPH23)))";
		    //@ $conn = ocilogon('ALEPH','ALEPH',$db);
            $conn = "";
		    if (!$conn){
		     // $conn_val = array('valor' => false );
		    //$e = oci_error();
		    //trigger_error(htmlentities($e['No se pudo establecer la conexión'], ENT_QUOTES), E_USER_ERROR);
		    }
		    else
		    {
	   	  	  if($month < 10)
                  $month = "0".$month;
              //$fi = $year.$month.'01';
		      //$ff = $year.$month.$numero_dias;
		      $fi = $form_state->getValue('fechaini');
		      $ff = $form_state->getValue('fechafin');
		      $fi = str_replace("-", "", $fi);
		      $ff = str_replace("-", "", $ff);
		      $w = $biblioteca;//'VICTO';
		      //$fir = $year.$month.'01';
		      //$ffr = $year.$month.$numero_dias;
              $total = 0;

              //Validar el cambio por el nuevo modelo
              

			  $sql = "select count(*) as total, z35_material from bib50.z35 where Z35_sub_library = '".$w."' and z35_event_date between '".$fi."' 
		      and '".$ff."' and z35_event_type = 80 group by z35_material";
		      /*
		      $stid = ociparse($conn, $sql);      
		      ociexecute($stid);      
		      $row = oci_fetch_all($stid, $results, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_NUM);
		      
		      //print($sql);
		      foreach ($results as $key => $value) {
		        $consulta[trim($value[1])] = $value;
                $total += $value[0];
		      } 

		      

		    	$output[] = [
		    		'AULIB'=>isset($consulta['AULIB'][0])? $consulta['AULIB'][0] : "0",
                    'BLRAY' => isset($consulta['BLRAY'][0]) ? $consulta['BLRAY'][0] : "0",
                	'BRAIL'=>isset($consulta['BRAIL'][0])? $consulta['BRAIL'][0] : "0",
		    		'BRINF' => isset($consulta['BRINF'][0]) ? $consulta['BRINF'][0] : "0",
		    		'BRLIT' => isset($consulta['BRLIT'][0]) ? $consulta['BRLIT'][0] : "0",
		    		'CASET' => isset($consulta['CASET'][0]) ? $consulta['CASET'][0] : "0",
					'CD' => isset($consulta['CD'][0]) ? $consulta['CD'][0] : "0",
                	'CDMUL' => isset($consulta['CDMUL'][0]) ? $consulta['CDMUL'][0] : "0",
                	'CDMUS' => isset($consulta['CDMUS'][0]) ? $consulta['CDMUS'][0] : "0",
					'CPORT' => isset($consulta['CPORT'][0]) ? $consulta['CPORT'][0] : "0",
                	'DVD' => isset($consulta['DVD'][0]) ? $consulta['DVD'][0] : "0",
                	'EREAD' => isset($consulta['EREAD'][0]) ? $consulta['EREAD'][0] : "0",
                	'FLLET' => isset($consulta['FLLET'][0]) ? $consulta['FLLET'][0] : "0",
                	'LBINF' => isset($consulta['LBINF'][0]) ? $consulta['LBINF'][0] : "0",
		          	'LIBRG' => isset($consulta['LIBRG'][0]) ? $consulta['LIBRG'][0] : "0",
					'LITER' => isset($consulta['LITER'][0]) ? $consulta['LITER'][0] : "0",
					'REFER' => isset($consulta['REFER'][0]) ? $consulta['REFER'][0] : "0",
					'LAMIN' => isset($consulta['LAMIN'][0]) ? $consulta['LAMIN'][0] : "0",
                    'ISSUE' => isset($consulta['ISSUE'][0])? $consulta['ISSUE'][0] : "0",
					'JEJER' => isset($consulta['JEJER'][0])? $consulta['JEJER'][0] : "0",
					'JENSA' => isset($consulta['JENSA'][0])? $consulta['JENSA'][0] : "0",
                	'JREGL' => isset($consulta['JREGL'][0])? $consulta['JREGL'][0] : "0",
                	'JSIMB' => isset($consulta['JSIMB'][0])? $consulta['JSIMB'][0] : "0",
					'MAPA' => isset($consulta['MAPA'][0]) ? $consulta['MAPA'][0] : "0",
                	'OBJET' => isset($consulta['OBJET'][0]) ? $consulta['OBJET'][0] : "0",
					'TABLE' => isset($consulta['TABLE'][0]) ? $consulta['TABLE'][0] :"0",
					'VIDEO' => isset($consulta['VIDEO'][0]) ? $consulta['VIDEO'][0] : "0",
					'VDVD' => isset($consulta['VDVD'][0]) ? $consulta['VDVD'][0] : "0",
					'RESER' => isset($consulta['RESER'][0]) ? $consulta['RESER'][0] : "0",
					'VH' => isset($consulta['VH'][0]) ? $consulta['VH'][0] : "0",
					'PARTI' => isset($consulta['PARTI'][0]) ? $consulta['PARTI'][0] : "0",
                    //'Total' => $total > 0 ? $total : "0",
	         	];
            */
			}
			
		}
		
		
		$form['resultados'] = array  (
		      '#theme' => 'consultasbiblioteca',
		      '#contenido' => $output,//$form_state['storage']['some_value'],
		      '#suffix' => '<h3>Total consultas: '.$total.'</h3>',
		    );

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
