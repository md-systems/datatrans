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
    $generator = \Drupal::urlGenerator();
    foreach($_GET as $key => $value) {
      drupal_set_message($key . $value);
    }

    $form_elements = array (
      'security_level' => '2',
      'amount' => '24600',
      'uppCustomerFirstName' => 'firstname',
      'sign' => 'eyuidfghj',
      'uppCustomerCity' => 'city',
      'uppCustomerZipCode' => 'CHE',
      'uppCustomerDetails' => 'yes',
      'uppCustomerStreet' => 'street',
      'currency' => 'CHF',
      'status' => 'success',
    );

    foreach($form_elements as $key => $value) {
      $form[$key] = array(
        '#type' => 'hidden',
        '#value' => $value,
      );
    }

    $form['#action'] = $generator->generateFromRoute('payment_datatrans.response_success', array('payment' => 1));
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
