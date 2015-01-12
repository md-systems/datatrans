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
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManager;
use Drupal\payment_datatrans\DatatransHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, EventDispatcherInterface $event_dispatcher, Token $token, ModuleHandlerInterface $module_handler, PaymentStatusManager $payment_status_manager) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $event_dispatcher, $token, $module_handler);
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
      $container->get('module_handler'),
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
   * @param array $key
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
      'amount' => intval($payment->getamount() * $currency->getSubunits()),
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

    $redirect_url = Url::fromUri($this->pluginDefinition['up_start_url'], array(
      'absolute' => TRUE,
      'query' => $payment_data,
    ))->toString();

    $response = new RedirectResponse($redirect_url);
    $listener = function (FilterResponseEvent $event) use ($response) {
      $event->setResponse($response);
      $event->stopPropagation();
    };
    $this->eventDispatcher->addListener(KernelEvents::RESPONSE, $listener, 999);

    $payment->save();
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
