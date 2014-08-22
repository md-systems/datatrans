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
    $payment_method = $payment->getPaymentMethod()->getPluginDefinition();

    $datatrans = $request->request->all();

    if (empty($datatrans)) {
      drupal_set_message(t('Datatrans communication failure. Invalid data received from datatrans. Please contact the system administrator.'), 'error');

      $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_failed'));
      $payment->save();
      $payment->getPaymentType()->resumeContext();
    }

    if ($datatrans['security_level'] == 2) {
      if ($payment_method['security']['use_hmac_2']) {
        $key = pack("H*", $payment_method['security']['hmac_key_2']);
      }
      else {
        $hmac_data = $payment_method['merchant_id'] . $datatrans['amount'] . $datatrans['currency'] . $payment->id();
        $key = pack("H*", $payment_method['security']['hmac_key']);
        $sign = hash_hmac('md5', $hmac_data , $key);
      }

      if ($sign != $datatrans['sign']) {
        drupal_set_message(t('Datatrans communication failure. Invalid data received from datatrans. Please contact the system administrator.'), 'error');

        $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_failed'));
        $payment->save();
        $payment->getPaymentType()->resumeContext();
      }
    }

    if ($datatrans['status'] == 'error') {
      drupal_set_message($this->_commerce_datatrans_map_error_code($datatrans['errorCode']), 'error');

      $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_failed'));
      $payment->save();
      $payment->getPaymentType()->resumeContext();
    }

    $payment_method = $payment->getPaymentMethod();

    if(isset($_POST['refno'])) {
      $payment_method->setRefno($_POST['refno']);
    }

    if(isset($_POST['uppCustomerDetails']) && $_POST['uppCustomerDetails'] == 'yes') {
      $payment_method->setAddress(array(
        'uppCustomerCity' => $_POST['uppCustomerCity'],
        'uppCustomerStreet' => $_POST['uppCustomerStreet'],
        'uppCustomerZipCode' => $_POST['uppCustomerZipCode'],
      ));
    }

    if(isset($_POST['uppCustomerFirstName']) && isset($_POST['uppCustomerLastName'])) {
      $payment_method->setCustomerName($_POST['uppCustomerFirstName'], $_POST['uppCustomerLastName']);
    }

    $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_success'));
    $payment->save();
    $payment->getPaymentType()->resumeContext();
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
}
