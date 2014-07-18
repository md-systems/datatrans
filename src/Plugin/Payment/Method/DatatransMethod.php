<?php

/**
 * @file
 * Contains \Drupal\payment_datatrans\Plugin\Payment\Method\DatatransMethod.
 */

namespace Drupal\payment_datatrans\Plugin\Payment\Method;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\currency\Entity\Currency;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Datatrans payment method.
 *
 * @PaymentMethod(
 *   deriver = "Drupal\payment_datatrans\Plugin\Payment\Method\DatatransDeriver",
 *   id = "payment_datatrans",
 *   label = @Translation("Datatrans")
 * )
 */
class DatatransMethod extends PaymentMethodBase implements ContainerFactoryPluginInterface, ConfigurablePluginInterface {

  /**
   * The payment status manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $paymentStatusManager;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Utility\Token $token
   *   The token API.
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManager $payment_status_manager
   *   The payment status manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EventDispatcherInterface $event_dispatcher, Token $token, ModuleHandlerInterface $module_handler, PaymentStatusManager $payment_status_manager) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $token, $module_handler);
    $this->paymentStatusManager = $payment_status_manager;

    $this->pluginDefinition['message_text'] = '';
    $this->pluginDefinition['message_text_format'] = '';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('token'),
      $container->get('module_handler'),
      $container->get('plugin.manager.payment.status')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getOperations($plugin_id) {
    return array();
  }

  /**
   * Performs the actual payment execution.
   */
  protected function doExecutePayment() {
    $payment = $this->getPayment();

    $currency = Currency::load($payment->getCurrencyCode());

//    $securityHash = null;
//
//    switch ($this->pluginDefinition['security']['security_level']) {
//      case 1:
//
//
//        $form['sign'] = array(
//          '#type' => 'hidden',
//          '#value' => $payment_method['settings']['security']['merchant_control_constant'],
//        );
//        break;
//      case 2:
//        $form['sign'] = array(
//          '#type' => 'hidden',
//          '#value' => hash_hmac('md5', $payment_method['settings']['merchant_id'] . $total[0]['amount'] . $total[0]['currency_code'] . $order->order_id, pack("H*", $payment_method['settings']['security']['hmac_key'])),
//        );
//        break;
//    }

    $paymentArray = array(
      'merchantId' => $this->pluginDefinition['merchant_id'],
      'amount' => intval($payment->getamount() * $currency->getSubunits()),
      'currency' => $payment->getCurrencyCode(),
      'refno' => '16543', //TODO: Append to a unique reference number (order number)
      //'sign' => '', // TODO: Create a new form item to fill in the sign
      //'security_level' => $this->pluginDefinition['security']['security_level'],

      'successUrl' => url('datatrans/success/'. $payment->id(), array('absolute' => TRUE)),
      'errorUrl' => url('datatrans/error', array('absolute' => TRUE)),
      'cancelUrl' => url('datatrans/cancel', array('absolute' => TRUE)),



      // TODO: Check if the senderUrl should be used for returning to the last page on order cancellation
      'senderUrl' => "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",

//      'up_start_url' => $this->pluginDefinition['up_start_url'],
//      'req_type' => $this->pluginDefinition['req_type'],
//      'security' => array(
//        'security_level' => $this->pluginDefinition['security']['security_level'],
//        'merchant_control_constant' => $this->pluginDefinition['security']['merchant_control_constant'],
//        'hmac_key' => $this->pluginDefinition['security']['hmac_key'],
//        'hmac_key_2' => $this->pluginDefinition['security']['hmac_key_2'],
//        'use_hmac_2' => $this->pluginDefinition['security']['use_hmac_2'],
//      ),
    );

    $http_build_query_paymentArray = "https://payment.datatrans.biz/upp/jsp/upStart.jsp?" . http_build_query($paymentArray);

    debug($http_build_query_paymentArray);
    // @todo: Implement redirect (commerce_datatrans_redirect_form) to and from Datatrans functionality (success/error/cancel), hashmac check (commerce_datatrans_redirect_form_validate).


  }

  /**
   * {@inheritdoc}
   */
  protected function getSupportedCurrencies() {
    return TRUE;

  }

  /**
   * {@inheritdoc}
   */
  protected function doCapturePaymentAccess(AccountInterface $account) {
    // TODO: Implement doCapturePaymentAccess() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doCapturePayment() {
    // TODO: Implement doCapturePayment() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doRefundPaymentAccess(AccountInterface $account) {
    // TODO: Implement doRefundPaymentAccess() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doRefundPayment() {
    // TODO: Implement doRefundPayment() method.
  }

}
