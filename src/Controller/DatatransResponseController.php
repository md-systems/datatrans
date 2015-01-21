<?php
/**
 * @file
 * Contains \Drupal\payment_datatrans\Controller\DatatransResponseController.
 */

namespace Drupal\payment_datatrans\Controller;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment_datatrans\DatatransHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DatatransResponseController
 *   Datatrans Response Controller
 *
 * @package Drupal\payment_datatrans\Controller
 */
class DatatransResponseController {

  /**
   * Page callback for processing successful Datatrans response.
   *
   * @param Request $request
   *   Request
   * @param PaymentInterface $payment
   *   The Payment entity type.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function processSuccessResponse(Request $request, PaymentInterface $payment) {
    try {
      // This needs to be checked to match the payment method settings
      // ND being valid with its keys and data.
      $post_data = $request->request->all();

      // Check if payment is pending.
      if ($post_data['datatrans_key'] != DatatransHelper::generateDatatransKey($payment)) {
        throw new \Exception('Invalid datatrans key.');
      }

      // If Datatrans returns error status.
      if ($post_data['status'] == 'error') {
        throw new \Exception(DatatransHelper::mapErrorCode($post_data['errorCode']));
      }

      // This is the internal guaranteed configuration that is to be considered
      // authoritative.
      $plugin_definition = $payment->getPaymentMethod()->getPluginDefinition();

      // Check for invalid security level.
      if (empty($post_data) || $post_data['security_level'] != $plugin_definition['security']['security_level']) {
        throw new \Exception('Invalid security level.');
      }

      // If security level 2 is configured then generate and use a sign.
      if ($plugin_definition['security']['security_level'] == 2) {
        // Generates the sign.

        $sign2 = $this->generateSign2($plugin_definition, $post_data);

        // Check for correct sign.
        if (empty($post_data['sign2']) || $sign2 != $post_data['sign2']) {
          throw new \Exception('Invalid sign.');
        }
      }

      // At that point the transaction is treated to be valid.
      if ($post_data['status'] == 'success') {
        // Store data in the payment configuration.
        $this->setPaymentConfiguration($payment, $post_data);

        // Save the successful payment.
        return $this->savePayment($payment, 'payment_success');
      }
      else {
        throw new \Exception('Datatrans communication failure. Invalid data received from Datatrans.');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('datatrans')->error('Processing failed with exception @e.', array('@e' => $e->getMessage()));
      drupal_set_message(t('Payment processing failed.'), 'error');
      return $this->savePayment($payment);
    }
  }

  /**
   * Page callback for processing error Datatrans response.
   *
   * @param PaymentInterface $payment
   *  The Payment entity type.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *
   * @throws \Exception
   */
  public function processErrorResponse(PaymentInterface $payment) {

    $message = 'Datatrans communication failure. Invalid data received from Datatrans.';
    \Drupal::logger('datatrans')->error('Processing failed with exception @e.', array('@e' => $message));
    drupal_set_message(t('Payment processing failed.'), 'error');
    return $this->savePayment($payment);
  }

  /**
   * Page callback for processing cancellation Datatrans response.
   *
   * @param PaymentInterface $payment
   *  The Payment entity type.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *
   * @throws \Exception
   */
  public function processCancelResponse(PaymentInterface $payment) {
    drupal_set_message(t('Payment cancelled.'), 'error');
    return $this->savePayment($payment, 'payment_cancelled');
  }

  /**
   * Generates the sign2 to compare with the datatrans post data.
   *
   * @param array $plugin_definition
   *   Plugin Definition.
   * @param array $post_data
   *   Datatrans Post Data.
   *
   * @return string
   *   The generated sign.
   * @throws \Exception
   */
  public function generateSign2($plugin_definition, $post_data) {
    if ($plugin_definition['security']['hmac_key'] || $plugin_definition['security']['hmac_key_2']) {
      if ($plugin_definition['security']['use_hmac_2']) {
        $key = $plugin_definition['security']['hmac_key_2'];
      }
      else {
        $key = $plugin_definition['security']['hmac_key'];
      }
      return DatatransHelper::generateSign($key, $plugin_definition['merchant_id'], $post_data['uppTransactionId'], $post_data['amount'], $post_data['currency']);
    }

    throw new \Exception('Problem generating sign.');
  }

  /**
   * Saves success/cancelled/failed payment.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *  Payment entity.
   * @param string $status
   *  Payment status to set
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function savePayment(PaymentInterface $payment, $status = 'payment_failed') {
    $payment->setPaymentStatus(\Drupal::service('plugin.manager.payment.status')
      ->createInstance($status));
    $payment->save();
    return $payment->getPaymentType()->getResumeContextResponse()->getResponse();
  }

  /**
   * Sets payments configuration data if present.
   *
   * No validation.
   *
   * @param PaymentInterface $payment
   *   Payment Interface.
   * @param $post_data
   *   Datatrans Post Data.
   */
  public function setPaymentConfiguration(PaymentInterface $payment, $post_data) {
    /** @var \Drupal\payment_datatrans\Plugin\Payment\Method\DatatransMethod $payment_method */
    $payment_method = $payment->getPaymentMethod();

    if (!empty($post_data['uppCustomerDetails']) && $post_data['uppCustomerDetails'] == 'yes') {
      $customer_details = array(
        'uppCustomerTitle',
        'uppCustomerName',
        'uppCustomerFirstName',
        'uppCustomerLastName',
        'uppCustomerStreet',
        'uppCustomerStreet2',
        'uppCustomerCity',
        'uppCustomerCountry',
        'uppCustomerZipCode',
        'uppCustomerPhone',
        'uppCustomerFax',
        'uppCustomerEmail',
        'uppCustomerGender',
        'uppCustomerBirthDate',
        'uppCustomerLanguage',
        'refno'
      );

      foreach ($customer_details as $key) {
        if (!empty($post_data[$key])) {
          $payment_method->setConfigField($key, $post_data[$key]);
        }
      }
    }
  }
}
