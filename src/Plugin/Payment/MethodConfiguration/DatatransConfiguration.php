<?php

/**
 * @file
 * Contains \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration.
 */

namespace Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
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
      'up_start_url' => 'https://payment.datatrans.biz/upp/jsp/upStart.jsp',
      'req_type' => 'CAA',
      'security' => array(
        'security_level' => 2,
        'merchant_control_constant' => '',
        'hmac_key' => '',
        'hmac_key_2' => '',
        'use_hmac_2' => FALSE,
      ),
      'debug' => FALSE,
    );
  }

  /**
   * Sets the Datatrans Merchant ID.
   *
   * @param string $merchant_id
   *   Datatrans Merchant ID
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setMerchantId($merchant_id) {
    $this->configuration['merchant_id'] = $merchant_id;

    return $this;
  }

  /**
   * Gets the Datatrans Merchant ID.
   *
   * @return string
   *   Unique Merchant Identifier (assigned by Datatrans)
   */
  public function getMerchantId() {
    return $this->configuration['merchant_id'];
  }

  /**
   * Sets the Datatrans Up Start Url.
   *
   * @param string $up_start_url
   *   DataTrans Up Start Url
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setUpStartUrl($up_start_url) {
    $this->configuration['up_start_url'] = $up_start_url;

    return $this;
  }

  /**
   * Gets the Datatrans Up Start URL.
   *
   * @return string
   *   Datatrans service URL
   *   UTF-8 encoding: https://payment.datatrans.biz/upp/jsp/upStart.jsp
   *   ISO encoding: https://payment.datatrans.biz/upp/jsp/upStartIso.jsp
   */
  public function getUpStartUrl() {
    return $this->configuration['up_start_url'];
  }

  /**
   * Sets the Datatrans Request Type.
   *
   * @param string $req_type
   *   Datatrans Request Type
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setReqType($req_type) {
    $this->configuration['req_type'] = $req_type;

    return $this;
  }


  /**
   * Gets the Datatrans Request Type.
   *
   * @return string
   *   The request type specifies whether the transaction has to be immediately
   *   settled or authorized only. There are two request types available:
   *   “NOA” authorization only
   *   “CAA” authorization with immediate settlement in case of successful
   *   authorization; if “reqtype” is not submitted the transaction is
   *   processed according to the setting in the Web Admin Tool (sec-tion “UPP
   *   Administration”).
   */
  public function getReqType() {
    return $this->configuration['req_type'];
  }

  /**
   * Sets the Datatrans Security Level.
   *
   * @param string $security_level
   *   Datatrans Security Level
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setSecurityLevel($security_level) {
    $this->configuration['security']['security_level'] = $security_level;

    return $this;
  }

  /**
   * Gets the Datatrans Security Level.
   *
   * @return string
   *   The entire data transfer between the merchant's shop application and the
   *   Datatrans payment application is se-cured by the secure SSL protocol.
   *
   *   Security Level 0:
   *   The data transmission is not secured.
   *
   *   Security Level 1:
   *   The data transmission is secured by sending of the parameter sign which
   *   must contain a merchant-specific control value (constant). See Merchant
   *   Control Constant.
   *
   *   Security Level 2:
   *   The data transmission is secured by sending the parameter sign, which
   *   must contain a digital signature generated by a standard HMAC-MD5 hash
   *   procedure and using a merchant-specific encryption key. The HMAC key is
   *   generated by the system and can be changed at any time in the merchant
   *   administration tool https://payment.datatrans.biz.
   */
  public function getSecurityLevel() {
    return $this->configuration['security']['security_level'];
  }

  /**
   * Sets the Datatrans Merchant Control Constant.
   *
   * @param string $merchant_control_constant
   *   Merchant Control Constant
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setMerchantControlConstant($merchant_control_constant) {
    $this->configuration['security']['merchant_control_constant'] = $merchant_control_constant;

    return $this;
  }

  /**
   * Gets the Datatrans Merchant Control Constant.
   *
   * @return string
   *   This value is generated in the merchant administration tool
   *   https://payment.datatrans.biz. Note that with every change of this value
   *   (which is possible at any time), the interface accepts the current value
   *   only.
   */
  public function getMerchantControlConstant() {
    return $this->configuration['security']['merchant_control_constant'];
  }

  /**
   * Sets the Datatrans HMAC Key.
   *
   * @param string $hmac_key
   *   The HMAC Key.
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setHmacKey($hmac_key) {
    $this->configuration['security']['hmac_key'] = $hmac_key;

    return $this;
  }

  /**
   * Gets the Datatrans HMAC Key.
   *
   * @return string
   *   The HMAC key is crerated by the system and can be changed at any time in
   *   the merchant administration tool https://payment.datatrans.biz.
   *   - With every change of the key, the interface accepts signature based on
   *     the current key only!
   *   - The key is delivered in hexadecimal format, and it should also be
   *     stored in this format. But before its usage the key must be translated
   *     into byte format!
   *   - “sign2” is only returned in success case.
   */
  public function getHmacKey() {
    return $this->configuration['security']['hmac_key'];
  }

  /**
   * Sets the Datatrans Use for the HMAC key 2.
   *
   * @param string $use_hmac_2
   *   Checkbox to enabled/disable use of the HMAC key 2.
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setUseHmacTwo($use_hmac_2) {
    $this->configuration['security']['use_hmac_2'] = $use_hmac_2;

    return $this;
  }

  /**
   * Gets the Datatrans Use for the HMAC key 2.
   *
   * @return string
   *   Checkbox to enabled/disable use of the HMAC key 2.
   */
  public function getUseHmacTwo() {
    return $this->configuration['security']['use_hmac_2'];
  }

  /**
   * Sets the Datatrans HMAC Key 2.
   *
   * @param string $hmac_key_2
   *   The HMAC key 2.
   *
   * @return \Drupal\payment_datatrans\Plugin\Payment\MethodConfiguration\DatatransConfiguration
   *   The configuration object for the Datatrans payment method plugin.
   */
  public function setHmacKeyTwo($hmac_key_2) {
    $this->configuration['security']['hmac_key_2'] = $hmac_key_2;

    return $this;
  }

  /**
   * Gets the Datatrans HMAC Key 2.
   *
   * @return string
   *   The HMAC key is created by the system and can be changed at any time in
   *   the merchant administration tool https://payment.datatrans.biz.
   *   - With every change of the key, the interface accepts signature based on
   *     the current key only!
   *   - The key is delivered in hexadecimal format, and it should also be
   *     stored in this format. But before its usage the key must be translated
   *     into byte format!
   *   - “sign2” is only returned in success case.
   */
  public function getHmacKeyTwo() {
    return $this->configuration['security']['hmac_key_2'];
  }

  /**
   * Enables logging the response from Datatrans.
   *
   * @param bool $state
   *   Whether debugging should be dis/enabled.
   */
  public function setDebug($state = TRUE) {
    $this->configuration['debug'] = $state;
  }

  /**
   * Disables logging the response from Datatrans.
   */
  public function getDebug() {
    return $this->configuration['debug'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['#element_validate'][] = array($this, 'formElementsValidate');

    $form['merchant_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Merchant-ID'),
      '#default_value' => $this->getMerchantId(),
      '#required' => TRUE,
    );

    $form['up_start_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Start URL'),
      '#default_value' => $this->getUpStartUrl(),
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
      '#default_value' => $this->getReqType(),
    );

    $url = Url::fromUri('https://pilot.datatrans.biz/showcase/doc/Technical_Implementation_Guide.pdf', ['external' => TRUE]);
    $form['security'] = array(
      '#type' => 'fieldset',
      '#title' => t('Security Settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,

      '#description' => t('You should not work with anything else than security level 2 on a productive system. Without the HMAC key there is no way to check whether the data really comes from Datatrans.') . PHP_EOL . t('You can find more details about the security levels in your Datatrans account at UPP ADMINISTRATION -> Security. Or check the technical information in the !link', array('!link' => \Drupal::l('Technical_Implementation_Guide', $url))),
    );

    $form['security']['security_level'] = array(
      '#type' => 'select',
      '#title' => t('Security Level'),
      '#options' => array(
        '0' => t('Level 0. No additional security element will be send with payment messages. (not recommended)'),
        '1' => t('Level 1. An additional Merchant-Identification will be send with payment messages'),
        '2' => t('Level 2. Important parameters will be digitally signed (HMAC-MD5) and sent with payment messages'),
      ),
      '#default_value' => $this->getSecurityLevel(),
    );

    $form['security']['merchant_control_constant'] = array(
      '#type' => 'textfield',
      '#title' => t('Merchant control constant'),
      '#default_value' => $this->getMerchantControlConstant(),
      '#description' => t('Used for security level 1'),
      '#states' => array(
        'visible' => array(
          ':input[name="plugin_form[security][security_level]"]' => array('value' => '1'),
        ),
      ),
    );

    $form['security']['hmac_key'] = array(
      '#type' => 'textfield',
      '#title' => t('HMAC Key'),
      '#default_value' => $this->getHmacKey(),
      '#description' => t('Used for security level 2'),
      '#states' => array(
        'visible' => array(
          ':input[name="plugin_form[security][security_level]"]' => array('value' => '2'),
        ),
      ),
    );

    $form['security']['use_hmac_2'] = array(
      '#type' => 'checkbox',
      '#title' => 'Use HMAC 2',
      '#default_value' => $this->getUseHmacTwo(),
      '#states' => array(
        'visible' => array(
          ':input[name="plugin_form[security][security_level]"]' => array('value' => '2'),
        ),
      ),
    );

    $form['security']['hmac_key_2'] = array(
      '#type' => 'textfield',
      '#title' => t('HMAC Key 2'),
      '#default_value' => $this->getHmacKeyTwo(),
      '#description' => t('Used for security level 2'),
      '#states' => array(
        'visible' => array(
          ':input[name="plugin_form[security][security_level]"]' => array('value' => '2'),
        ),
      ),
    );

    $form['debug'] = array(
      '#type' => 'checkbox',
      '#title' => 'Log response from Datatrans server',
      '#default_value' => $this->getDebug(),
    );
    \Drupal::config('payment.payment_method_configuration.payment_datatrans')->get('pluginConfiguration')['debug'];
    return $form;
  }

  /**
   * Implements form validate callback for self::formElements().
   */
  public function formElementsValidate(array $element, FormStateInterface $form_state, array $form) {
    $values = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    $this->setMerchantId($values['merchant_id'])
      ->setUpStartUrl($values['up_start_url'])
      ->setReqType($values['req_type'])
      ->setSecurityLevel($values['security'])
      ->setSecurityLevel($values['security']['security_level'])
      ->setMerchantControlConstant($values['security']['merchant_control_constant'])
      ->setHmacKey($values['security']['hmac_key'])
      ->setUseHmacTwo($values['security']['use_hmac_2'])
      ->setHmacKeyTwo($values['security']['hmac_key_2'])
      ->setDebug($values['debug']);
  }

}
