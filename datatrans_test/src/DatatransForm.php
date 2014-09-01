<?php
/**
 * @file
 * Contains \Drupal\datatrans_test\DatatransForm.
 */

namespace Drupal\payment_datatrans_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment_datatrans\DatatransHelper;

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

    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = entity_load('payment', 1);

    $form_elements = array (
      'security_level' => '2',
      'amount' => '24600',
      'uppCustomerFirstName' => 'firstname',
      'sign' => \Drupal::state()->get('datatrans.sign') ?: '309dd30ad0cb07770d3a1ffda64585a9',
      'uppCustomerCity' => 'city',
      'uppCustomerZipCode' => 'CHE',
      'uppCustomerDetails' => 'yes',
      'uppCustomerStreet' => 'street',
      'currency' => 'CHF',
      'status' => 'success',
      'datatrans_key' => DatatransHelper::generateDatatransKey($payment),
    );

    foreach($form_elements as $key => $value) {
      $form[$key] = array(
        '#type' => 'hidden',
        '#value' => $value,
      );
    }

    $response_url = \Drupal::state()->get('datatrans.return_url') ?: 'payment_datatrans.response_success';

    $form['#action'] = $generator->generateFromRoute($response_url, array('payment' => 1));
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
