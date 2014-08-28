<?php

/**
 * @file
 * Contains \Drupal\payment_datatrans\Tests\DatatransPaymentTest.
 */

namespace Drupal\payment_datatrans\Tests;

use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeTypeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Token integration.
 *
 * @group Currency
 */
class DatatransPaymentTest extends WebTestBase {

  public static $modules = array('payment_datatrans', 'payment', 'payment_form', 'payment_datatrans_test', 'node', 'field_ui', 'config');

  /**
   * A user with permission to create and edit books and to administer blocks.
   *
   * @var object
   */
  protected $admin_user;


  /**
   * Generic node used for testing.
   */
  protected $node;


  /**
   * @var $field_name
   */
  protected $field_name;

  protected function setUp() {
    parent::setUp();

    // Create a field name
    $this->field_name = strtolower($this->randomMachineName());

    // Create article content type
    $node_type = $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    $this->node_add_payment_form_field($node_type);

    // Create article node
    $title = $this->randomString();

    // Create node with payment plugin configuration
    $this->node = $this->drupalCreateNode(array(
      'type' => 'article',
      $this->field_name => array(
        'plugin_configuration' => array(
          'amount' => '123',
          'currency_code' => 'CHF',
          'name' => 'payment_basic',
          'payment_id' => NULL,
          'quantity' => '2',
          'description' => 'pay me man',
        ),
        'plugin_id' => 'payment_basic',
      ),
      'title' => $title,
    ));

    // Create user with correct permission.
    $this->admin_user = $this->drupalCreateUser(array('payment.payment_method_configuration.view.any',
      'payment.payment_method_configuration.update.any', 'access content', 'access administration pages',
      'access user profiles', 'payment.payment.view.any'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests succesfull Datatrans payment.
   */
  function testDatatransSuccessPayment() {
    // Modifies the datatrans configuration for testing purposes.
    $generator = \Drupal::urlGenerator();
    $datatrans_configuration = array(
      'plugin_form[up_start_url]' => $generator->generateFromRoute('datatrans_test.datatrans_form', array(), array('absolute' => TRUE)),
      'plugin_form[merchant_id]' => '123456789',
      'plugin_form[message][value]' => 'Datatrans',
      'plugin_form[req_type]' => 'CAA',
      'plugin_form[security][security_level]' => '2',
      'plugin_form[security][merchant_control_constant]' => '',
      'plugin_form[security][hmac_key]' => '6543123456789',
      'plugin_form[security][hmac_key_2]' => '',
    );
    $this->drupalPostForm('admin/config/services/payment/method/configuration/payment_datatrans', $datatrans_configuration, t('Save'));

    // Create datatrans payment
    $this->drupalPostForm('node/' . $this->node->id(), array(), t('Pay'));

    // Retrieve plugin configuration of created node
    $plugin_configuration = $this->node->{$this->field_name}->plugin_configuration;

    // Array of Datatrans payment method configuration.
    $datatrans_payment_method_configuration = entity_load('payment_method_configuration', 'payment_datatrans')->getPluginConfiguration();

    // Check for correct Merchant ID
    $this->assertText('merchantId' . $datatrans_payment_method_configuration['merchant_id']);

    // Check for correct amount
    $calculated_amount = $this->calculateAmount($plugin_configuration['amount'], $plugin_configuration['quantity'], $plugin_configuration['currency_code']);
    $this->assertText('amount' . $calculated_amount);

    // Check for correct success, error and cancel url
    $this->assertText('successUrl' . $generator->generateFromRoute('payment_datatrans.response_success', array('payment' => 1), array('absolute' => TRUE)));
    $this->assertText('errorUrl' . $generator->generateFromRoute('payment_datatrans.response_error', array('payment' => 1), array('absolute' => TRUE)));
    $this->assertText('cancelUrl' . $generator->generateFromRoute('payment_datatrans.response_cancel', array('payment' => 1), array('absolute' => TRUE)));

    // Check for correct sign with using hmac_key
    $this->assertText('sign' . '309dd30ad0cb07770d3a1ffda64585a9');

    // Finish and save payment
    $this->drupalPostForm(NULL, array(), t('Submit'));

    // Check out the payment overview page
    $this->drupalGet('admin/content/payment');

    // Check for correct currency code and payment amount
    $this->assertText('CHF 246.00');

    // Check for correct Payment Method
    $this->assertText('Datatrans');

    // Check payment configuration (city, street & zipcode)
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = entity_load('payment', 1);
    $payment_configuration = $payment->getPaymentMethod()->getConfiguration();
    $this->assertTrue($payment_configuration['uppCustomerCity'], 'city');
    $this->assertTrue($payment_configuration['uppCustomerStreet'], 'street');
    $this->assertTrue($payment_configuration['uppCustomerZipCode'], 'CHE');

    // Check for detailed payment information
    $this->drupalGet('payment/1');
    $this->assertText('pay me man');
    $this->assertText('CHF 123.00');
    $this->assertText('CHF 246.00');
    $this->assertText('Completed');
  }

  /**
   * Tests failing Datatrans payment.
   * The test fails by providing an incorrect hmac key.
   */
  function testDatatransFailedPayment() {
    // Modifies the datatrans configuration for testing purposes.
    $generator = \Drupal::urlGenerator();
    $datatrans_configuration = array(
      'plugin_form[up_start_url]' => $generator->generateFromRoute('datatrans_test.datatrans_form', array(), array('absolute' => TRUE)),
      'plugin_form[merchant_id]' => '123456789',
      'plugin_form[message][value]' => 'Datatrans',
      'plugin_form[req_type]' => 'CAA',
      'plugin_form[security][security_level]' => '2',
      'plugin_form[security][merchant_control_constant]' => '',
      'plugin_form[security][hmac_key]' => '1234', // For failed test we give a wrong hmac_key
      'plugin_form[security][hmac_key_2]' => '',
    );
    $this->drupalPostForm('admin/config/services/payment/method/configuration/payment_datatrans', $datatrans_configuration, t('Save'));

    // Create datatrans payment
    $this->drupalPostForm('node/' . $this->node->id(), array(), t('Pay'));

    // Check for incorrect sign.
    $this->assertNoText('sign309dd30ad0cb07770d3a1ffda64585a9');

    // Finish and save payment
    $this->drupalPostForm(NULL, array(), t('Submit'));

    // Check out the payment overview page
    $this->drupalGet('admin/content/payment');
    $this->assertText('Failed');
    $this->assertNoText('Success');

    // Check for detailed payment information
    $this->drupalGet('payment/1');
    $this->assertText('Failed');
    $this->assertNoText('Success');
  }

  /**
   * Tests cancelled Datatrans payment.
   */
  function testDatatransCancelPayment() {
    // Modifies the datatrans configuration for testing purposes.
    $generator = \Drupal::urlGenerator();
    $datatrans_configuration = array(
      'plugin_form[up_start_url]' => $generator->generateFromRoute('datatrans_test.datatrans_form', array(), array('absolute' => TRUE)),
      'plugin_form[merchant_id]' => '123456789',
      'plugin_form[message][value]' => 'Datatrans',
      'plugin_form[req_type]' => 'CAA',
      'plugin_form[security][security_level]' => '2',
      'plugin_form[security][merchant_control_constant]' => '',
      'plugin_form[security][hmac_key]' => '1234', // For failed test we give a wrong hmac_key
      'plugin_form[security][hmac_key_2]' => '',
    );
    $this->drupalPostForm('admin/config/services/payment/method/configuration/payment_datatrans', $datatrans_configuration, t('Save'));

    // Create datatrans payment
    $this->drupalPostForm('node/' . $this->node->id(), array(), t('Pay'));

    // Check for incorrect sign.
    $this->assertNoText('sign309dd30ad0cb07770d3a1ffda64585a9');

    // Check out the payment overview page
    $this->drupalGet('admin/content/payment');
    $this->assertText('Failed');
    $this->assertNoText('Success');

    // Check for detailed payment information
    $this->drupalGet('payment/1');
    $this->assertText('Failed');
    $this->assertNoText('Success');
  }

  /**
   * Calculates the total amount
   *
   * @param $amount
   *  Base amount
   * @param $quantity
   *  Quantity
   * @param $currency_code
   *  Currency code
   * @return int
   *  Returns the total amount
   */
  function calculateAmount($amount, $quantity, $currency_code) {
    $base_amount = $amount * $quantity;
    $currency = \Drupal\currency\Entity\Currency::load($currency_code);
    return intval($base_amount * $currency->getSubunits());
  }

  /**
   * Generates the sign
   *
   * @param $merchant_id
   *  Merchant ID
   * @param $calculated_amount
   *  Calculated Amount see: calculateAmount()
   * @param $currency_code
   *  Currency Code
   * @param $payment_id
   *  Payment ID
   * @param $hmac_key
   *  hmac key
   * @return string
   *  Returns the sign
   */
  function generateSign($merchant_id, $calculated_amount, $currency_code, $payment_id, $hmac_key) {
    return hash_hmac(
      'md5',
      $merchant_id . $calculated_amount . $currency_code . $payment_id,
      pack("H*", $hmac_key)
    );
  }

  /**
   * Adds the payment field to the node
   *
   * @param NodeTypeInterface $type
   *   Node type interface type
   *
   * @param string $label
   *   Field label
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  function node_add_payment_form_field(NodeTypeInterface $type, $label = 'Payment Label') {
    // Add or remove the body field, as needed.
    $field_storage = FieldStorageConfig::loadByName('node', $this->field_name);
    $instance = FieldInstanceConfig::loadByName('node', $type->id(), $this->field_name);
    if (empty($field_storage)) {
      $field_storage = entity_create('field_storage_config', array(
        'name' => $this->field_name,
        'entity_type' => 'node',
        'type' => 'payment_form',
      ));
      $field_storage->save();
    }
    if (empty($instance)) {
      $instance = entity_create('field_instance_config', array(
        'field_storage' => $field_storage,
        'bundle' => $type->id(),
        'label' => $label,
        'settings' => array('currency_code' => 'CHF'),
      ));
      $instance->save();

      // Assign display settings for the 'default' and 'teaser' view modes.
      entity_get_display('node', $type->type, 'default')
        ->setComponent($this->field_name, array(
          'label' => 'hidden',
          'type' => 'text_default',
        ))
        ->save();
    }

    return $instance;
  }
}

