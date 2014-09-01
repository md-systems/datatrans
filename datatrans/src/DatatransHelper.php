<?php
/**
 * @file
 * Contains \Drupal\payment_datatrans\DatatransHelper.
 *
 * Set class info here
 */
namespace Drupal\payment_datatrans;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Drupal\payment\Entity\PaymentInterface;

/**
 * DatatransHelper class
 */
class DatatransHelper {

  /**
   * @param PaymentInterface $payment
   * @return string
   */
  public static function generateDatatransKey(PaymentInterface $payment) {
    return Crypt::hashBase64($payment->id() . $payment->getStatus()->getPluginId() . Settings::getHashSalt() . \Drupal::service('private_key')->get());
  }
}
