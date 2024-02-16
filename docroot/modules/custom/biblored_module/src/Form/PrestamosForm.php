<?php
/**
* @file
* Contains Drupal\conteo\form\PrestamosForm
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
class PrestamosForm extends FormBase
{
	/**
	* {@inheritdoc}
	*/

	public function getFormId()
	{
		return 'biblored_module_prestamosform'; //nombremodule_nombreformulario	
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

			$numero_dias = cal_days_in_month(CAL_GREGORIAN, $month, $year);

			$db ="(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP) (HOST = 216.147.208.153) (PORT = 11521) )(CONNECT_DATA = (SID = ALEPH23)))";
		    $conn = "";
        	/*
        	@ $conn = ocilogon('ALEPH','ALEPH',$db);
		    if (!$conn){
		     $conn_val = array('valor' => false );
		    //$e = oci_error();
		    //trigger_error(htmlentities($e['No se pudo establecer la conexión'], ENT_QUOTES), E_USER_ERROR);
		    }
		    else
		    {
	   	  	  
              $fi = $year.$month.'01';
		      $ff = $year.$month.$numero_dias;
		      $w = $biblioteca;//'VICTO';
		      $fi = $form_state->getValue('fechaini');
	      	  $ff = $form_state->getValue('fechafin');
              $fi = str_replace("-", "", $fi);
	          $ff = str_replace("-", "", $ff);
              $z36h_i = array(
                "AULIB"=>"Audio libro", 
                "BLRAY"=>"Blu ray", 
              	"BOOK"=>"Libro electrónico",
                "BRAIL"=>"Braille", 
                "BRINF"=>"Braille infantil", 
                "BRLIT"=>"Braille literatura", 
                "CASET"=>"Caset", 
                "CD"=>"CD", 
                "CDMUL"=>"CD multimedia", 
                "CDMUS"=>"CD música",
                "CPORT"=>"Portátil",
              	"DISKE"=>"Diskette",
                "DVD"=>"DVD", 
                "EREAD"=>"E-reader", 
                "FLLET"=>"Folleto",
                "ISSUE"=>"Publicación periódica", 
                "LAMIN"=>"Lámina", 
                "LBINF"=>"Colección infantil",
                "LIBRG"=>"Colección general", 
                "LITER"=>"Literatura", 
                "MAPA"=>"Mapa", 
                "OBJET"=>"Objeto",
                "PARTI"=>"Partitura",
                "REFER"=>"Colección de referencia", 
                "RESER"=>"Reservado",  
                "TABLE"=>"Tablet", 
                "VDVD"=>"Película", 
                "VH"=>"VH", 
                "VIDEO"=>"VIDEO", 
                ""=>"Otros");
                
		      //$sql = "select count(*) as total, Z30_collection as coleccion from BIB50.Z36, bib50.Z30 where Z36_sub_library = '".$w."' and Z30_sub_library = '".$w."' and z30_item_status != 3 and Z36_REC_KEY = Z30_REC_KEY and Z36_LOAN_DATE BETWEEN '".$fi."' and '".$ff."' group by Z30_collection union select count(*) as total, Z30_collection as coleccion from BIB50.Z36H, bib50.Z30 where Z36H_sub_library = '".$w."' and Z30_sub_library = '".$w."' and z30_item_status != 3 and Z36H_REC_KEY = Z30_REC_KEY and Z36H_LOAN_DATE BETWEEN '".$fi."' and '".$ff."' group by Z30_collection";
              //$sql = "select sum(Total) as Total, Coleccion from (select count(*) as total, Z36_MATERIAL as coleccion from BIB50.Z36 where Z36_sub_library = '".$w."' and Z36_LOAN_DATE BETWEEN '".$fi."' and '".$ff."' group by Z36_MATERIAL union select count(*) as total, Z36H_MATERIAL as coleccion from BIB50.Z36H where Z36H_sub_library = '".$w."' and Z36H_LOAN_DATE BETWEEN '".$fi."' and '".$ff."' group by Z36H_MATERIAL) group by Coleccion";
              $sql = "select count(*) as total, Z35_MATERIAL as coleccion 
              			from BIB50.Z35 
              			where Z35_sub_library = '".$w."' 
              			and Z35_EVENT_DATE between '".$fi."' and '".$ff."'
              			and z35_event_type in (50, 52, 62, 63) group by Z35_MATERIAL";
              //echo  $sql;   
		      $stid = ociparse($conn, $sql);      
		      ociexecute($stid);      
		      $row = oci_fetch_all($stid, $results, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_NUM);
             
                foreach ($results as $key => $value) 
                {
                    $header[trim($value[1])] = t($z36h_i[trim($value[1])]);
                    $output[0][trim($value[1])] = $value[0];
                    $total += $value[0];
		        }
               
		      
			} // End of else
			*/		    
	    	
		    	
			
		} // End of if
		
		$form['table'] = [
	    '#type' => 'tableselect',
	    '#header' => $header,
	    '#options' => $output,
	    '#suffix' => '<h3>Total préstamos: '.$total.'</h3>',
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