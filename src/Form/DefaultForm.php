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
    $connection = \Drupal::database();

    // clean normalized table if there exists
    if (db_table_exists($content_type)) {
        $connection->truncate($content_type)->execute();
    }

    $field_names = array_keys($fields);

    $schema[$content_type]['description'] = $content_type;
    foreach ($field_names as $field_name) {
      $schema[$content_type]['fields'][$field_name] = [
        'description' => $field_name,
        'type' => 'text', // @todo another types
      ];
    }

    if (!db_table_exists($content_type)) {
        db_create_table($content_type, $schema[$content_type]);
    }
    else {
      drupal_set_message('The table ' . $content_type . ' already exists.');
    }

    // set batch processing to load data into the denormalized table
    $batch = $this->batch($content_type, $field_names);
    batch_set($batch);

  }

  public function batch($content_type, $field_names) {

    $query = \Drupal::entityQuery('node')->condition('type', $content_type);
    $num_operations = $query->count()->execute();

    $nids = \Drupal::entityQuery('node')->condition('type', $content_type)->execute();

    $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);

    $i = 0;

    foreach ($nodes as $nid => $node) {
        $i++;
        foreach ($field_names as $field_name) {
          //dpm($node->get($field_name)->getValue());
          $fields[$field_name] = $node->get($field_name)->getString();
        }
        $operations[] = [
          'denormalization_op',
          [
            $i + 1,
            $content_type,
            $fields,
            t('(Operation @operation)', ['@operation' => $fields['nid']]),
          ],
        ];
    }

    drupal_set_message(t('Creating an array of @num operations', ['@num' => $num_operations]));

    $batch = [
      'title' => t('Creating an array of @num operations', ['@num' => $num_operations]),
      'operations' => $operations,
      'finished' => 'denormalization_finished',
    ];
    return $batch;
  }

}
