<?php

/**
 * @file
 * Contains \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration.
 */

namespace Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the configuration for the Datatrans payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("Datatrans payment method."),
 *   id = "payment_datatrans",
 *   label = @Translation("Datatrans")
 * )
 */
class DatatransConfiguration extends PaymentMethodConfigurationBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface $payment_status_manager
   *   The payment status manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   A string containing the English string to translate.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Interface for classes that manage a set of enabled modules.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PaymentStatusManagerInterface $payment_status_manager, TranslationInterface $string_translation, ModuleHandlerInterface $module_handler) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $string_translation, $module_handler);
    $this->paymentStatusManager = $payment_status_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.payment.status'),
      $container->get('string_translation'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + array(
      'merchant_id' => '1000011011',
      'up_start_url' => 'https://pilot.datatrans.biz/upp/jsp/upStart.jsp',
      'req_type' => 'CAA',
      'security' => array(
        'security_level' => 2,
        'merchant_control_constant' => '',
        'hmac_key' => '',
        'hmac_key_2' => '',
        'use_hmac_2' => FALSE,
      ),
    );
  }

  /**
   * Sets the final payment status.
   *
   * @param string $status
   *   The plugin ID of the payment status to set.
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setStatus($status) {
    $this->configuration['status'] = $status;

    return $this;
  }

  /**
   * Gets the final payment status.
   *
   * @return string
   *   The plugin ID of the payment status to set.
   */
  public function getStatus() {
    return $this->configuration['status'];
  }

  /**
   * Sets the Saferpay account id.
   *
   * @param string $account_id
   *   Account id.
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setMerchantId($account_id) {
    $this->configuration['account_id'] = $account_id;

    return $this;
  }

  /**
   * Gets the Datatrans account id.
   *
   * @return string
   *   The account id.
   */
  public function getMerchantId() {
    return $this->configuration['account_id'];
  }

  /**
   * Sets the Saferpay password.
   *
   * @param string $password
   *   The password.
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setPassword($password) {
    $this->configuration['account_id'] = $password;

    return $this;
  }

  /**
   * Gets the Saferpay password.
   *
   * @return string
   *   The password.
   */
  public function getPassword() {
    return $this->configuration['password'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['#element_validate'][] = array($this, 'formElementsValidate');

    $form['merchant_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Merchant-ID'),
      '#default_value' => $settings['merchant_id'],
      '#required' => TRUE,
    );

    $form['up_start_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Start URL'),
      '#default_value' => $settings['up_start_url'],
      '#required' => TRUE,
    );

    $form['req_type'] = array(
      '#type' => 'select',
      '#title' => t('Request Type'),
      '#options' => array(
        'NOA' => t('Authorization only'),
        'CAA' => t('Authorization with immediate settlement'),
        'ignore' => t('According to the setting in the Web Admin Tool'),
      ),
      '#default_value' => $settings['req_type'],
    );

    $form['security'] = array(
      '#type' => 'fieldset',
      '#title' => t('Security Settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#description' => t('You should not work with anything else than security level 2 on a productive system. Without the HMAC key there is no way to check whether the data really commes from Datatrans.') . PHP_EOL .
        t('You can find more details about the security levels in your Datatrans account at UPP ADMINISTRATION -> Security. Or check the tecnical information in the ').
        l('Technical_Implementation_Guide', 'https://pilot.datatrans.biz/showcase/doc/Technical_Implementation_Guide.pdf', array('external' => TRUE)),
    );

    $form['security']['security_level'] = array(
      '#type' => 'select',
      '#title' => t('Security Level'),
      '#options' => array(
        '0' => t('Level 0. No additional security element will be send with payment messages. (not recommended)'),
        '1' => t('Level 1. An additional Merchant-IDentification will be send with payment messages'),
        '2' => t('Level 2. Important parameters will be digitally signed (HMAC-MD5) and sent with payment messages'),
      ),
      '#default_value' => $settings['security']['security_level'],
    );

    $form['security']['merchant_control_constant'] = array(
      '#type' => 'textfield',
      '#title' => t('Merchant control constant'),
      '#default_value' => $settings['security']['merchant_control_constant'],
      '#description' => t('Used for security level 1'),
    );

    $form['security']['hmac_key'] = array(
      '#type' => 'textfield',
      '#title' => t('HMAC Key'),
      '#default_value' => $settings['security']['hmac_key'],
      '#description' => t('Used for security level 2'),
    );

    $form['security']['use_hmac_2'] = array(
      '#type' => 'checkbox',
      '#title' => 'Use HMAC 2',
      '#default_value' => $settings['security']['use_hmac_2'],
    );

    $form['security']['hmac_key_2'] = array(
      '#type' => 'textfield',
      '#title' => t('HMAC Key 2'),
      '#default_value' => $settings['security']['hmac_key_2'],
      '#description' => t('Used for security level 2'),
    );

    return $form;
  }

  /**
   * Implements form validate callback for self::formElements().
   */
  public function formElementsValidate(array $element, array &$form_state, array $form) {
    $values = NestedArray::getValue($form_state['values'], $element['#parents']);
    $this->setStatus($values['status'])
      ->setAccountId($values['account_id'])
      ->setPassword($values['password']);
  }

}
