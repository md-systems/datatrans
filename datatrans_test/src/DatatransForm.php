<?php
/**
 * @file
 * Contains \Drupal\datatrans_test\DatatransForm.
 */

namespace Drupal\payment_datatrans_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment_datatrans\DatatransHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Implements an example form.
 */
class DatatransForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'datatrans_form';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $generator = \Drupal::urlGenerator();

    foreach ($request->query->all() as $key => $value) {
      drupal_set_message($key . $value);
    }

    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = entity_load('payment', 1);

    $form_elements = array (
      'security_level' => '2',
      'amount' => '24600',
      'uppTransactionId' => rand(10000, 100000),
      'uppCustomerFirstName' => 'firstname',
      'uppCustomerCity' => 'city',
      'uppCustomerZipCode' => 'CHE',
      'uppCustomerDetails' => 'yes',
      'uppCustomerStreet' => 'street',
      'currency' => 'CHF',
      'status' => 'success',
      'datatrans_key' => DatatransHelper::generateDatatransKey($payment),
    );

    // @todo: allow to control the hmac to test hmac2.
    $generated_sign = DatatransHelper::generateSign('6543123456789', $request->query->get('merchantId'), $form_elements['uppTransactionId'], $request->query->get('amount'), $request->query->get('currency'));
    drupal_set_message($generated_sign);
    $form_elements['sign2'] = \Drupal::state()->get('datatrans.sign') ?: $generated_sign;

    foreach ($form_elements as $key => $value) {
      $form[$key] = array(
        '#type' => 'hidden',
        '#value' => $value,
      );
    }

    // Don't generate the route, use the submitted url.
    $response_url = \Drupal::state()->get('datatrans.return_url') ?: 'payment_datatrans.response_success';

    $form['#action'] = $generator->generateFromRoute($response_url, array('payment' => $request->query->get('refno')));
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
