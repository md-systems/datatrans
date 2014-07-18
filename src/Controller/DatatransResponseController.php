<?php
/**
 * @file
 * Contains \Drupal\payment_datatrans\Controller\DatatransResponseController.
 */

namespace Drupal\payment_datatrans\Controller;
use Drupal\payment\Entity\PaymentInterface;

/**
 * Datatrans response controller.
 */
class DatatransResponseController {

  /**
   * Page callback for processing successful Datatrans response.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *   The Payment entity type.
   */
  public function proccessSuccessResponse(PaymentInterface $payment) {
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
  public function proccessErrorResponse(PaymentInterface $payment) {
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
  public function proccessCancelResponse(PaymentInterface $payment) {
    $payment->setStatus(\Drupal::service('plugin.manager.payment.status')->createInstance('payment_cancelled'));
    $payment->save();
    $payment->getPaymentType()->resumeContext();
  }
}