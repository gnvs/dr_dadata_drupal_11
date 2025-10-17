<?php

namespace Drupal\dadata_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DadataSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['dadata_integration.settings'];
  }

  public function getFormId() {
    return 'dadata_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dadata_integration.settings');

    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#default_value' => $config->get('token'),
      '#required' => TRUE,
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#default_value' => $config->get('secret'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dadata_integration.settings')
      ->set('token', $form_state->getValue('token'))
      ->set('secret', $form_state->getValue('secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
