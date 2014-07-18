<?php
/**
 * @file
 * Contains \Drupal\payment_datatrans\Controller\DatatransResponseController
 */

namespace Drupal\payment_datatrans\Controller;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment_datatrans\Plugin\Payment\Method\DatatransDeriver;

/**
 * Datatrans response controller.
 */
class DatatransResponseController {
  /**
   * Page callback for processing successfull Datatrans response.
   */
  public function proccessSuccessResponse(PaymentInterface $payment, DatatransDeriver $datatransDeriver) {
    $configuration = $datatransDeriver->getDerivativeDefinitions();
//    if (!is_array($_POST)) {
//      drupal_set_message(t('Datatrans communication failure. Invalid data received from datatrans. Please contact the system administrator.'), 'error');
//      return FALSE;
//    }
//    $datatrans = $_POST;
//
//    // check the hmac for security level 2
//    if ($_POST['security_level'] == 2) {
//      if ($payment_method['settings']['security']['use_hmac_2'])
//        $key = pack("H*", $payment_method['settings']['security']['hmac_key_2']);
//      else
//        $key = pack("H*", $payment_method['settings']['security']['hmac_key']);
//      $sign = hash_hmac('md5', $payment_method['settings']['merchant_id'] . $datatrans['amount'] . $datatrans['currency'] . $datatrans['uppTransactionId'], $key);
//      if ($sign != $datatrans['sign2']) {
//        drupal_set_message(t('Datatrans communication failure. Invalid data received from datatrans. Please contact the system administrator.'), 'error');
//        return FALSE;
//      }
//    }
//
//    if ($datatrans['status'] == 'error') {
//      drupal_set_message(_commerce_datatrans_map_error_code($datatrans['errorCode']), 'error');
//      return FALSE;
//    }


    debug($datatransDeriver);

//    $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_success'));
//    $payment->save();
//    $payment->getPaymentType()->resumeContext();
  }

  /**
   * Page callback for processing error Datatrans response.
   */
  public function proccessErrorResponse(PaymentInterface $payment) {
    $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_failed'));
    $payment->save();
    $payment->getPaymentType()->resumeContext();
  }

  /**
   * Page callback for processing cancellation Datatrans response.
   */
  public function proccessCancelResponse(PaymentInterface $payment) {
    $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_cancelled'));
    $payment->save();
    $payment->getPaymentType()->resumeContext();
  }
}