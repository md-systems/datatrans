<?php
/**
 * @file
 * Contains \Drupal\datatrans_test\DatatransForm.
 */

namespace Drupal\payment_datatrans_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an example form.
 */
class DatatransForm extends FormBase {
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'datatrans_form';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('Hello How are you!');
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Validate Form");
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Submit Form");
  }
}
