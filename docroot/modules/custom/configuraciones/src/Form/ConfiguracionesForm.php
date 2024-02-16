<?php

/**
 * @file
 * Contains Drupal\ConfiguracionesForm\Form\ConfiguracionesForm
 */

namespace Drupal\Configuraciones\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implement a settings form
 */
class ConfiguracionesForm extends ConfigFormBase {

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'configuraciones_settings'; //nombremodule_nombreformulario
  }
    
  /**
  * {@inheritdoc}
  */
  public function getEditableConfigNames() {
    return ['Configuraciones.settings'];
  }

  /**
  * {@inheritdoc]
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('Configuraciones.settings')
      ->set('uribase', $form_state->getValue('uribase'))
      ->set('plazomalla', $form_state->getValue('plazomalla'))
      ->set('plazoespeciales', $form_state->getValue('plazoespeciales'))
      ->save();
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }
  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('Configuraciones.settings');
    $form['uribase'] = array(
      '#type' => 'textfield',
      '#description' => 'Digitar o pegar la url de donde viene el servicio web. Ej: https://www.biblored.gov.co/ (Tener en cuenta "/")',
      '#title' => $this->t('Uri base para webservice de Agenda'),
      '#default_value' => $config->get('uribase'),
      '#required' => true,
    );
    $form['plazomalla'] = array(
      '#type' => 'textfield',
      '#description' => 'Digitar hasta que día del mes hay plazo para programar la malla de actividades. Ej. 10 (Significa que se prodrá programar hasta el día 10 del mes actual)',
      '#title' => $this->t('Día de cierre para programar la malla mensual'),
      '#default_value' => $config->get('plazomalla'),
      '#required' => true,
      '#type' => 'number',
    );
  	$form['plazoespeciales'] = array(
      '#type' => 'textfield',
      '#description' => 'Digitar hasta que día del mes hay plazo para programar actividades especiales. Ej. 10 (Significa que se prodrá programar hasta el día 10 del mes actual)',
      '#title' => $this->t('Día de cierre para programar actividades especiales'),
      '#default_value' => $config->get('plazoespeciales'),
      '#required' => true,
      '#type' => 'number',
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array (
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return parent::buildForm($form, $form_state);
  }
}
