<?php

namespace Drupal\dadata_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dadata_integration\Service\DadataClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DadataSearchForm extends FormBase {

  protected $dadata;

  public function __construct(DadataClient $dadata) {
    $this->dadata = $dadata;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('dadata_integration.client'));
  }

  public function getFormId() {
    return 'dadata_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    /**
     * Основное поле ввода ИНН
     */

    $form['inn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Введите ИНН'),
      '#required' => TRUE,
      '#size' => 20,
      '#attributes' => ['placeholder' => 'Например, 7707083893'],
      '#prefix' => '<div class="container">',
    ];

    /**
     * Кнопка отправки с ajax
     */
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Найти'),
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'dadata-result-wrapper',
        'effect' => 'fade',
      ],
      '#attributes' => [
        'class' => ['btn', 'btn-primary'], 
      ],
      '#suffix' => '</div>',  
    ];

    /**
     * Обертка для ajax-обновления
     */
    $form['result'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'dadata-result-wrapper', 'class' => 'container mt-5'],
    ];

    /**
     * Если есть результат — выводим таблицу
     */
    if ($data = $form_state->get('result')) {
      $form['result']['table'] = [
        '#type' => 'table',
        '#header' => ['Поле', 'Значение'],
        '#rows' => [
          ['Наименование организации', $data['name']['full_with_opf'] ?? '-'],
          ['ИНН', $data['inn'] ?? '-'],
          ['ОГРН', $data['ogrn'] ?? '-'],
          ['ОКПО', $data['okpo'] ?? '-'],
          ['Руководитель', $data['management']['name'] ?? '-'],
          ['Должность', $data['management']['post'] ?? '-'],
          ['Адрес', $data['address']['value'] ?? '-'],
          ['Статус', $data['state']['status'] ?? '-'],
        ],
        '#attributes' => ['class' => ['table', 'table-bordered', 'responsive-enabled'],
    ],
      ];
    }

    return $form;
  }

  /**
   * Ajax — возвращает только обновляемую часть формы
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['result'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $inn = trim($form_state->getValue('inn'));

    if (strlen($inn) < 10) {
      $this->messenger()->addError($this->t('Введите корректный ИНН.'));
      return;
    }

    try {
      $data = $this->dadata->findByInn($inn);

      if (empty($data)) {
        $this->messenger()->addError($this->t('Организация с таким ИНН не найдена.'));
        $form_state->set('result', NULL);
      }
      else {
        $form_state->set('result', $data);
      }

      /**
       * Обновляем форму
       */
      $form_state->setRebuild(TRUE);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Ошибка: @msg', ['@msg' => $e->getMessage()]));
    }
  }

}
