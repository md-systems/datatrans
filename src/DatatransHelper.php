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
  public static function mapErrorCode($code) {
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
