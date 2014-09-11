<?php
/**
 * @file
 * Contains \Drupal\payment_datatrans\DatatransHelper.
 *
 * Datatrans Helper class.
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
   * Used for generation an internal key for validation purposes.
   *
   * @param PaymentInterface $payment
   *   Payment Interface.
   * @return string
   *   The generated key.
   */
  public static function generateDatatransKey(PaymentInterface $payment) {
    return Crypt::hashBase64($payment->id() . $payment->getPaymentStatus()
      ->getPluginId() . Settings::getHashSalt() . \Drupal::service('private_key')
      ->get());
  }
}
