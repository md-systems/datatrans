<?php
/**
 * @file
 * Contains \Drupal\payment_datatrans\Controller\DatatransResponseController.
 */

namespace Drupal\payment_datatrans\Controller;
use Drupal\payment\Entity\PaymentInterface;
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
    // @todo this needs to be checked to match the payment method settings AND being valid with its keys and data.
    $post_data = $request->request->all();

    if ($post_data['status'] == 'error') {
      $this->throwException($payment, $this->_commerce_datatrans_map_error_code($post_data['errorCode']));
    }

    // @todo this is the internal guaranteed configurtion that is to be considered authoritative.
    $plugin_definition = $payment->getPaymentMethod()->getPluginDefinition();

    // @todo the request can craft to remove security check. thi NEEDS to be a payment method setting (with matching validation of the request in addition).
    if (empty($post_data) || $post_data['security_level'] != $plugin_definition['security']['security_level']) {
      $this->throwException($payment);
    }

    if ($plugin_definition['security']['security_level'] == 2) {
      // Generates the sign
      $sign = $this->generateSign($post_data, $plugin_definition, $payment);

      if ($sign != $post_data['sign'] || empty($sign) || empty($post_data['sign'])) {
        $this->throwException($payment);
      }
    }

    if($post_data['status'] == 'success') {
      // Store data in the payment configuration
      $this->storeConfiguration($post_data, $payment);

      // Save the succesfull payment
      $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_success'));
      $payment->save();
      $payment->getPaymentType()->resumeContext();
      return;
    }

    $this->throwException($payment);
  }

  /**
   * Page callback for processing error Datatrans response.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The Payment entity type.
   */
  public function processErrorResponse(PaymentInterface $payment) {
    $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_failed'));
    $payment->save();
    $payment->getPaymentType()->resumeContext();
  }

  /**
   * Page callback for processing cancellation Datatrans response.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The Payment entity type.
   */
  public function processCancelResponse(PaymentInterface $payment) {
    $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_cancelled'));
    $payment->save();
    $payment->getPaymentType()->resumeContext();
  }

  /**
   * Generates the server side sign to compare with the datatrans post data.
   *
   * @param $post_data
   *  Datatrans post data
   * @param $payment_method
   *  Payment Method
   * @param PaymentInterface $payment
   *  Payment Interface
   * @return string
   *  Generated Sign
   */
  public function generateSign($post_data, $payment_method, PaymentInterface $payment) {
    if($payment_method['security']['hmac_key'] || $payment_method['security']['hmac_key_2']) {
      if ($payment_method['security']['use_hmac_2']) {
        $key = pack("H*", $payment_method['security']['hmac_key_2']);
      }
      else {
        $key = pack("H*", $payment_method['security']['hmac_key']);
      }

      $hmac_data = $payment_method['merchant_id'] . $post_data['amount'] . $post_data['currency'] . $payment->id();
      return hash_hmac('md5', $hmac_data , $key);
    }

    $this->throwException($payment);
  }

  /**
   * Throws exception and sets drupal error message.
   *
   * @param PaymentInterface $payment
   *  Payment Interface
   * @param string $message
   *  Exception Message to be thrown
   * @param string $status
   *  Payment Status
   * @throws \Exception
   *  Throws Exception
   */
  public function throwException($payment, $message = 'Datatrans communication failure. Invalid data received from datatrans. Please contact the system administrator.', $status = 'payment_failed') {
    drupal_set_message(t($message), 'error');

    $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance($status));
    $payment->save();
    $payment->getPaymentType()->resumeContext();
    throw new \Exception($message);
  }

  /**
   * Datatrans error code mapping.
   *
   * @param $code
   *   Provide error code from datatrans callback
   * @return bool|FALSE|mixed|string
   *   Returns error message
   */
  function _commerce_datatrans_map_error_code($code) {
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
   * Stores configuration data
   *
   * @param $post_data
   *  Datatrans Post Data
   * @param PaymentInterface $payment
   *  Payment Interface
   */
  public function storeConfiguration($post_data, PaymentInterface $payment) {
    /** @var \Drupal\payment_datatrans\Plugin\Payment\Method\DatatransMethod $payment_method */
    $payment_method = $payment->getPaymentMethod();

    if (!empty($post_data['refno'])) {
      $payment_method->setConfigField('refno', $post_data['refno']);
    }

    if(!empty($post_data['uppCustomerDetails']) && $post_data['uppCustomerDetails'] == 'yes') {
      $customer_details = array('uppCustomerTitle', 'uppCustomerName', 'uppCustomerFirstName',
        'uppCustomerLastName', 'uppCustomerStreet', 'uppCustomerStreet2', 'uppCustomerCity',
        'uppCustomerCountry', 'uppCustomerZipCode', 'uppCustomerPhone', 'uppCustomerFax',
        'uppCustomerEmail', 'uppCustomerGender', 'uppCustomerBirthDate', 'uppCustomerLanguage');

      foreach($customer_details as $key) {
        if(!empty($post_data[$key])) {
          $payment_method->setConfigField($key, $post_data[$key]);
        }
      }
    }
  }
}
