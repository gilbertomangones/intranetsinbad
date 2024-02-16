<?php
/**
* @file
* Contains Drupal\biblored_module\form\InterpibForm
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
class InterpibForm extends FormBase
{
	/**
	* {@inheritdoc}
	*/

	public function getFormId()
	{
		return 'biblored_module_interpibform'; //nombremodule_nombreformulario	
	}

	public function buildForm(array $form, FormStateInterface $form_state) 
	{
		$vid = 'nodos_bibliotecas';
 
		$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
		$bibliotecas[0] = "Todas";
		
		foreach($terms as $term) {
        	$term_data = [];
		    $tid_biblioteca_agenda = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue();
        	$tid_biblioteca_agenda =  isset($tid_biblioteca_agenda[0]) ? $tid_biblioteca_agenda[0]['value'] : "";
		    $sigla_biblioteca = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_abreviatura_de_la_bibliote')->getValue();
        	$sigla_biblioteca = isset($sigla_biblioteca[0]) ? $sigla_biblioteca[0]['value'] : "";
		    //echo $sigla_biblioteca."<br />"; //Iniciales de la biblioteca en Aleph
		    if ($term->depth == 1) {
	    	     // Array con todas las bibliotecas
	    	    $tid_bib = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)->get('field_tid_biblioteca_agenda')->getValue();
            	$tid_bib =  isset($tid_bib[0]) ? $tid_bib[0]['value'] : "";
	            if (!empty($tid_bib)) {
            	$term_data[] = array(
                 "id" => $term->tid,
                 "name" => $term->name,
                 'tid_biblioteca_agenda' => $tid_bib,
                 'sigla' => $sigla_biblioteca,
	             );
                }
		         // Creando un array con la bibliotecas que tengan equivalencia (en este caso para el filtro sql necesitamos el la sigla biblioteca)
		        if (!empty($tid_biblioteca_agenda)){
		            $bibliotecas[$sigla_biblioteca] = $term->name;
		        }
		     }
		}

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
    
	$form['actions'] = [
	  '#type' => 'actions',
	  'submit' => [
	      '#type' => 'submit',
	      '#value' => $this->t('Enviar'),
	  ],
	];
	
    \Drupal\Core\Database\Database::setActiveConnection('blaa');
	$db = \Drupal\Core\Database\Database::getConnection();
	\Drupal\Core\Database\Database::setActiveConnection();
	// Evaluar al presionar clic en Enviar se ha seleccionado Biblioteca
  	
	$sql = "";
    if($form_state->getValue('fechaini') || $form_state->getValue('fechafin') ) 
	{

		
    	$fi = $form_state->getValue('fechaini');
	    $ff = $form_state->getValue('fechafin');
		
		
			//if($month > 0)
			{
				$sql = "SELECT count(*) as Total, Estado, Biblioteca_O FROM pib WHERE Fecha_S between '".$fi."' and '".$ff."' group by Biblioteca_O, Estado";
			}
			
    }
    else
    {
                $sql = "SELECT count(*) as Total, Estado, Biblioteca_O FROM pib group by Biblioteca_O, Estado";
    }
       //echo $sql."<br />";
      
		$n_bib = array(0=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Nivel Central",9=>0,10=>"Teusaquillo"),
                       1=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Gabriel García Márquez",9=>0,10=>"Tunjuelito"), 
                       2=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Bosa",9=>0,10=>"Bosa"), 
                       3=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"El Tintal Manuel Zapata Olivella",9=>0,10=>"Kennedy"), 
                       4=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Virgilio Barco",9=>0,10=>"Teusaquillo"), 
                       5=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"La Victoria",9=>0,10=>"San Cristobal"), 
                       6=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Usaquén - Servitá",9=>0,10=>"Usaquén"), 
                       7=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Suba - Francisco José de Caldas",9=>0,10=>"Suba"), 
                       8=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Julio Mario Santo Domingo",9=>0,10=>"Suba"),
                       9=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Las Ferias",9=>0,10=>"Engativá"), 
                       10=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Carlos E. Restrepo",9=>0,10=>"Antonio Nariño"), 
                       12=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"La Giralda",9=>0,10=>"Fontibón"), 
                       14=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Puente Aranda",9=>0,10=>"Puente Aranda"), 
                       15=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Rafael Uribe Uribe",9=>0,10=>"Rafael Uribe"), 
                       16=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Venecia",9=>0,10=>"Tunjuelito"), 
                       17=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Perdomo",9=>0,10=>"Ciudad Bolivar"), 
                       18=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Lago Timiza",9=>0,10=>"Kennedy"), 
                       19=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Arborizadora Alta",9=>0,10=>"Ciudad Bolivar"), 
                       20=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"La Peña",9=>0,10=>"Santa Fé"), 
                       21=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"El Campin",9=>0,10=>"Teusaquillo"), 
                       22=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Sumapaz",9=>0,10=>"Sumapaz"), 
                       23=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Marichuela",9=>0,10=>"Usme"), 
                       24=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"El Parque",9=>0,10=>"Santa Fé"), 
                       25=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Pasquilla",9=>0,10=>"Ciudad Bolivar"), 
                       26=>array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>"Biblomóvil", 9=>0,10=>''), 99=>"Bibliobus"); 
		$resul = array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0);
		
		$result = $db->query($sql);
		$resultados = $result->fetchAll();
		
	// Switch back
		
	    
		$header = [
	      'bib' => t('Biblioteca'),
          'loc' => t('Localidad'),
	      'ts' => t('Total de solicitudes'),
	      'tp' => t('Total en préstamo'),
		  'tv' => t('Total devueltos'),
	      't1' => t('Pendientes por prestar'),
	      'sc' => t('Solicitudes canceladas'),
          'pl' => t('Pendientes por llegar'),
          'nr' => t('Otros no resueltos'),

	    ];
		
	    foreach ($resultados as $record) {	
        	//if (isset($record->Biblioteca)) 
            {
            	$n_bib[$record->Biblioteca_O][$record->Estado] = $record->Total;    
            	$n_bib[$record->Biblioteca_O][0] += $record->Total;       	
            }
	    }
		//var_dump($n_bib);
	    for($i=0; $i<=sizeof($n_bib); $i++)
		{
			  if(isset($n_bib[$i], $n_bib[$i][0]) && $n_bib[$i][0] > 0)
			  {
			  	$outputs[] = [
		    		'bib' => $n_bib[$i][8],
                	'loc' => $n_bib[$i][10],
				      'ts' => $n_bib[$i][0],
				      'tp' => $n_bib[$i][5],
					  'tv' => $n_bib[$i][6],
				      't1' => $n_bib[$i][4],
				      'sc' => $n_bib[$i][9],
                	  'pl' => $n_bib[$i][2],
                	  'nr' => ($n_bib[$i][1] + $n_bib[$i][3] + $n_bib[$i][7]),	
	         	];
	         	$resul[0] += $n_bib[$i][0];
				$resul[1] += $n_bib[$i][5];
				$resul[2] += $n_bib[$i][6];
				$resul[3] += $n_bib[$i][4];
				$resul[4] += $n_bib[$i][9];
                $resul[6] += $n_bib[$i][2];
                $resul[5] += ($n_bib[$i][6] + $n_bib[$i][3] + $n_bib[$i][7]);
			  
		      } 

		}

		$outputs[] = [
    		'bib' => 'Total',
        	'loc' => ' ',
		      'ts' => $resul[0],
		      'tp' => $resul[5],
			  'tv' => $resul[6],
		      't1' => $resul[4],
		      'sc' => $resul[9],
              'pl' => $resul[2],
        	  'nr' => $resul[5],
     	]; 
		  
	    
	    //var_dump($n_bib);
	//}
	
	//exit;
	$form['table'] = [
	    '#type' => 'tableselect',
	    '#header' => $header,
	    '#options' => $outputs,
    	'#suffix' => '<strong>** Otros no resueltos: </strong> "No disponible para préstamo", "El material que usted solicitó, no está disponible temporalmente"',
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