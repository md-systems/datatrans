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

  /**
   * Generates the server side sign to compare with the datatrans post data.
   *
   * @param array $plugin_definition
   *  Plugin Definition.
   * @param PaymentInterface $payment
   *  Payment Interface
   * @param array $data
   *  Datatrans Post Data.
   *
   * @return string
   * @throws \Exception
   */
  public static function generateSign($hmac_key, $merchant_id, $identifier, $amount, $currency) {
    $hmac_data = $merchant_id . $amount . $currency . $identifier;
    return hash_hmac('md5', $hmac_data, pack('H*', $hmac_key));
  }
}
