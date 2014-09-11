<?php
/**
 * @file
 * Contains \Drupal\payment_datatrans\Controller\DatatransResponseController.
 */

namespace Drupal\payment_datatrans\Controller;

use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment_datatrans\DatatransHelper;
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
   */
  public function processSuccessResponse(Request $request, PaymentInterface $payment) {
    try {
      // This needs to be checked to match the payment method settings AND being valid with its keys and data.
      $post_data = $request->request->all();

      // Check if payment is pending.
      if ($post_data['datatrans_key'] != DatatransHelper::generateDatatransKey($payment)) {
        throw new \Exception('Invalid datatrans key.');
      }

      // If Datatrans returns error status.
      if ($post_data['status'] == 'error') {
        throw new \Exception($this->mapErrorCode($post_data['errorCode']));
      }

      // This is the internal guaranteed configuration that is to be considered authoritative.
      $plugin_definition = $payment->getPaymentMethod()->getPluginDefinition();

      // Check for invalid security level.
      if (empty($post_data) || $post_data['security_level'] != $plugin_definition['security']['security_level']) {
        throw new \Exception('Invalid security level.');
      }

      // If security level 2 is configured then generate and use a sign.
      if ($plugin_definition['security']['security_level'] == 2) {
        // Generates the sign.
        $sign = $this->generateSign($plugin_definition, $payment, $post_data);

        // Check for correct sign.
        if ($sign != $post_data['sign'] || empty($sign) || empty($post_data['sign'])) {
          throw new \Exception('Invalid sign.');
        }
      }

      // At that point the transaction is treated to be valid.
      if ($post_data['status'] == 'success') {
        // Store data in the payment configuration.
        $this->setPaymentConfiguration($payment, $post_data);

        // Save the successful payment.
        $this->savePayment($payment, 'payment_success');
        return;
      }

      throw new \Exception('Datatrans communication failure. Invalid data received from Datatrans.');
    } catch (\Exception $e) {
      watchdog('datatrans', 'Processing failed with exception @e.', array('@e' => $e->getMessage())); // deprecated
      drupal_set_message(t('Payment processing failed.'));
      $this->savePayment($payment);
    }
  }

  /**
   * Page callback for processing error Datatrans response.
   *
   * @param PaymentInterface $payment
   *  The Payment entity type.
   * @throws \Exception
   */
  public function processErrorResponse(PaymentInterface $payment) {
    $this->savePayment($payment);

    $message = 'Datatrans communication failure. Invalid data received from Datatrans.';
    watchdog('datatrans', 'Processing failed with exception @e.', array('@e' => $message)); // deprecated
    drupal_set_message(t('Payment processing failed.'));
  }

  /**
   * Page callback for processing cancellation Datatrans response.
   *
   * @param PaymentInterface $payment
   *  The Payment entity type.
   * @throws \Exception
   */
  public function processCancelResponse(PaymentInterface $payment) {
    $this->savePayment($payment, 'payment_cancelled');

    $message = 'Payment has been cancelled.';
    watchdog('datatrans', 'Processing failed with exception @e.', array('@e' => $message)); // deprecated
    drupal_set_message(t('Payment processing failed.'));
  }

  /**
   * Generates the server side sign to compare with the datatrans post data.
   *
   * @param $plugin_definition
   *  Plugin Definition.
   * @param PaymentInterface $payment
   *  Payment Interface
   * @param $post_data
   *  Datatrans Post Data.
   *
   * @return string
   * @throws \Exception
   */
  public function generateSign($plugin_definition, PaymentInterface $payment, $post_data) {
    if ($plugin_definition['security']['hmac_key'] || $plugin_definition['security']['hmac_key_2']) {
      if ($plugin_definition['security']['use_hmac_2']) {
        $key = pack("H*", $plugin_definition['security']['hmac_key_2']);
      }
      else {
        $key = pack("H*", $plugin_definition['security']['hmac_key']);
      }

      $hmac_data = $plugin_definition['merchant_id'] . $post_data['amount'] . $post_data['currency'] . $payment->id();

      return hash_hmac('md5', $hmac_data, $key);
    }

    throw new \Exception('Problem generating sign.');
  }

  /**
   * Saves success/cancelled/failed payment.
   *
   * @param $payment
   *  Payment Interface.
   * @param string $status
   *  Payment Status
   */
  public function savePayment(PaymentInterface $payment, $status = 'payment_failed') {
    $payment->setPaymentStatus(\Drupal::service('plugin.manager.payment.status')
      ->createInstance($status));
    $payment->save();
    $payment->getPaymentType()->resumeContext();
  }

  /**
   * Datatrans error code mapping.
   *
   * @param $code
   *   Provide error code from Datatrans callback.
   * @return bool|FALSE|mixed|string
   *   Returns error message.
   *
   * @TODO: Move this method somewhere else.
   */
  function mapErrorCode($code) {
    switch ($code) {
      case '1001':
        $message = t('Datrans transaction failed: missing required parameter.');
        break;

      case '1002':
        $message = t('Datrans transaction failed: invalid parameter format.');
        break;

      case '1003':
        $message = t('Datatrans transaction failed: value of parameter not found.');
        break;

      case '1004':
      case '1400':
        $message = t('Datatrans transaction failed: invalid card number.');
        break;

      case '1007':
        $message = t('Datatrans transaction failed: access denied by sign control/parameter sign invalid.');
        break;

      case '1008':
        $message = t('Datatrans transaction failed: merchant disabled by Datatrans.');
        break;

      case '1401':
        $message = t('Datatrans transaction failed: invalid expiration date.');
        break;

      case '1402':
      case '1404':
        $message = t('Datatrans transaction failed: card expired or blocked.');
        break;

      case '1403':
        $message = t('Datatrans transaction failed: transaction declined by card issuer.');
        break;

      case '1405':
        $message = t('Datatrans transaction failed: amount exceeded.');
        break;

      case '3000':
      case '3001':
      case '3002':
      case '3003':
      case '3004':
      case '3005':
      case '3006':
      case '3011':
      case '3012':
      case '3013':
      case '3014':
      case '3015':
      case '3016':
        $message = t('Datatrans transaction failed: denied by fraud management.');
        break;

      case '3031':
        $message = t('Datatrans transaction failed: declined due to response code 02.');
        break;

      case '3041':
        $message = t('Datatrans transaction failed: Declined due to post error/post URL check failed.');
        break;

      case '10412':
        $message = t('Datatrans transaction failed: PayPal duplicate error.');
        break;

      case '-885':
      case '-886':
        $message = t('Datatrans transaction failed: CC-alias update/insert error.');
        break;

      case '-887':
        $message = t('Datatrans transaction failed: CC-alias does not match with cardno.');
        break;

      case '-888':
        $message = t('Datatrans transaction failed: CC-alias not found.');
        break;

      case '-900':
        $message = t('Datatrans transaction failed: CC-alias service not enabled.');
        break;

      default:
        $message = t('Datatrans transaction failed: undefined error.');
        break;
    }

    return $message;
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
