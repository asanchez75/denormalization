<?php

namespace Drupal\denormalization\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DefaultForm.
 */
class DefaultForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'default_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $content_types = array_keys(node_type_get_types());

    $form['content_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Content types'),
        '#description' => $this->t('Select content type to annotate'),
        '#options' => array_combine($content_types, $content_types),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
   //foreach ($form_state->getValues() as $key => $value) {
   //   drupal_set_message($key . ': ' . $value);
   //}

    $content_type = $form_state->getValues()['content_type'];
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $content_type);
    dpm(array_keys($fields));

  }

}
