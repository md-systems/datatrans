<?php

/**
 * @file
 * Contains \Drupal\payment_datatrans\Plugin\Payment\Method\DatatransMethod.
 */

namespace Drupal\payment_datatrans\Plugin\Payment\Method;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\currency\Entity\Currency;
use Drupal\payment\PaymentExecutionResult;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManager;
use Drupal\payment\Response\Response;
use Drupal\payment_datatrans\DatatransHelper;
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
   * @var \Drupal\payment\Response\Response
   */
  protected $response;

  /**
   * @param string $key
   * @param $value
   * @return $this|void
   */
  public function setConfigField($key, $value) {
    $this->configuration[$key] = $value;

    return $this;
  }

  /**
   * Performs the actual payment execution.
   */
  protected function doExecutePayment() {
    $payment = $this->getPayment();
    $generator = \Drupal::urlGenerator();

    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = Currency::load($payment->getCurrencyCode());

    $payment_data = array(
      'merchantId' => $this->pluginDefinition['merchant_id'],
      'amount' => intval($payment->getAmount() * $currency->getSubunits()),
      'currency' => $payment->getCurrencyCode(),
      'refno' => $payment->id(),
      'sign' => NULL,
      'successUrl' => $generator->generateFromRoute('payment_datatrans.response_success', array('payment' => $payment->id()), array('absolute' => TRUE)),
      'errorUrl' => $generator->generateFromRoute('payment_datatrans.response_error', array('payment' => $payment->id()), array('absolute' => TRUE)),
      'cancelUrl' => $generator->generateFromRoute('payment_datatrans.response_cancel', array('payment' => $payment->id()), array('absolute' => TRUE)),
      'security_level' => $this->pluginDefinition['security']['security_level'],
      'datatrans_key' => DatatransHelper::generateDatatransKey($payment),
    );
    // If security level 2 is configured then generate and use a sign.
    if ($this->pluginDefinition['security']['security_level'] == 2) {
      // Generates the sign.
      $payment_data['sign'] = DatatransHelper::generateSign($this->pluginDefinition['security']['hmac_key'], $this->pluginDefinition['merchant_id'], $payment->id(), $payment_data['amount'], $payment_data['currency']);
    }

    $payment->save();

    $this->response = new Response(Url::fromUri($this->pluginDefinition['up_start_url'], array(
      'absolute' => TRUE,
      'query' => $payment_data,
    )));
  }

  public function getPaymentExecutionResult() {
    return new PaymentExecutionResult($this->response);
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
