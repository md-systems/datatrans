<?php

/**
 * @file
 * Contains \Drupal\payment_datatrans\Tests\DatatransPaymentTest.
 */

namespace Drupal\payment_datatrans\Tests;

use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeTypeInterface;
use Drupal\payment\Tests\Generate;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\True;

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

    // Create node
    $this->node = $this->drupalCreateNode(array(
      'type' => 'article',
      $this->field_name => array(
        'plugin_configuration' => array(
          'amount' => '123',
          'currency_code' => 'CHF',
          'name' => 'payment_basic',
          'payment_id' => NULL,
          'quantity' => '1',
          'description' => 'pay me man',
        ),
        'plugin_id' => 'payment_basic',
      ),
      'title' => $title,
    ));

    $this->admin_user = $this->drupalCreateUser(array('administer permissions', 'administer node fields', 'administer node display',
      'payment.payment_method_configuration.view.any', 'payment.payment_method_configuration.update.any',
      'access content', 'access administration pages', 'access user profiles'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests token integration.
   */
  function testDatatransPayment() {
    // Modifies the datatrans up_start_url for testing purposes.
    $edit = array();
    $generator = \Drupal::urlGenerator();
    $edit['plugin_form[up_start_url]'] = $generator->generateFromRoute('datatrans_test.datatrans_form', array(), array('absolute' => TRUE));

    $this->drupalPostForm('admin/config/services/payment/method/configuration/payment_datatrans', $edit, t('Save'));

    $this->drupalPostForm('node/' . $this->node->id(), array(), t('Pay'));
  }

  /**
   * @param NodeTypeInterface $type
   * @param string $label
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

