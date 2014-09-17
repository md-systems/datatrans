<?php
/**
 * @file
 * Contains \Drupal\datatrans_test\DatatransForm.
 */

namespace Drupal\payment_datatrans_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\Payment;
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

    $payment = Payment::load($request->query->get('refno'));
    $plugin_definition = $payment->getPaymentMethod()->getPluginDefinition();

    $form_elements = array(
      'security_level' => $request->query->get('security_level'),
      'refno' => $payment->id(),
      'amount' => $request->query->get('amount'),
      'uppTransactionId' => rand(10000, 100000),
      'uppCustomerFirstName' => 'firstname',
      'uppCustomerCity' => 'city',
      'uppCustomerZipCode' => 'CHE',
      'uppCustomerDetails' => 'yes',
      'uppCustomerStreet' => 'street',
      'currency' => $request->query->get('currency'),
      'status' => 'success',
      'datatrans_key' => $request->query->get('datatrans_key'),
    );

    $generated_sign = DatatransHelper::generateSign($plugin_definition['security']['hmac_key'], $request->query->get('merchantId'), $form_elements['uppTransactionId'], $request->query->get('amount'), $request->query->get('currency'));
    drupal_set_message($generated_sign);
    $form_elements['sign2'] = \Drupal::state()->get('datatrans.sign') ?: $generated_sign;

    foreach ($form_elements as $key => $value) {
      $form[$key] = array(
        '#type' => 'hidden',
        '#value' => $value,
      );
    }

    // Don't generate the route, use the submitted url.
    $response_url_key = \Drupal::state()->get('datatrans.return_url_key') ?: 'success';
    $response_url = $request->query->get($response_url_key . 'Url');

    $form['#action'] = $response_url;
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
