<?php

namespace Drupal\dynamodb_keyvalue;

use Drupal\Core\Database\Connection;
use \Drupal\dynamodb_client\Connection as DynamoDB;

/**
 * Migrates key/value entries from DB to DynamoDB.
 *
 * @package Drupal\keyvalue_store
 */
class MigrateDatabaseKeyValue {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The dynamodb connection.
   *
   * @var \Drupal\dynamodb_client\Connection
   */
  protected $dynamodb;

  /**
   * MigrateDatabaseKeyValue constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection
   * @param \Drupal\dynamodb_client\Connection $dynamodb
   *   The dynamodb connection.
   */
  public function __construct(Connection $database, DynamoDB $dynamodb) {
    $this->database = $database;
    $this->dynamodb = $dynamodb;
  }

  /**
   * Migrate data from database to external destination.
   *
   * @param $type
   *   Type of key value storage, regular or expire.
   */
  public function run($type) {

    // Basic sanity checks.
    if (!in_array($type, ['key_value', 'key_value_expire'])) {
      return;
    }

    $key_values = $this->database->select($type, 'k')
      ->fields('k', [])
      ->execute()->fetchAll();

    foreach (array_chunk($key_values, 25, TRUE) as $subset) {

      $items = [];
      foreach ($subset as $set) {

        $item = [
          'collection' => ['S' => $set->collection],
          'name' => ['S' => $set->name],
          'value' => ['S' => $set->value]
        ];

        if ($type === 'key_value_expire') {
          $item['expire'] = ['N' => $set->expire];
        }

        $items [] =  [
          'PutRequest' => [
            'Item' => $item]
        ];
      }

      $params = [
        'RequestItems' => [
          $type => $items,
        ]
      ];

      $this->dynamodb->batchWriteItem($params);
    }
  }

}
