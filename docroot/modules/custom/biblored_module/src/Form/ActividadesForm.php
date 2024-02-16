<?php

namespace Drupal\biblored_module\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ActividadesForm extends FormBase {
  
  public function getFormId() {
    return 'biblored_module_actividadesform';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['nombre'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Nombre'),
        '#description' => $this->t('Introduce un nombre'),
    ];
    
    $form['apellido'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Apellidos'),
        '#description' => $this->t('Introduce los apellidos'),
    ];
    
    $form['actions'] = [
        '#type' => 'actions',
        'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Enviar'),
        ],
    ];
    
    return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
   
  \Drupal::messenger()->addMessage(
           $this->t('Valores: @nombre / @apellido', 
        [ '@nombre' => $form_state->getValue('nombre'),
          '@apellido' => $form_state->getValue('apellido'),
        ])
         );
  }
}
