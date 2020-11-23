<?php

namespace Drupal\dynamodb_keyvalue\KeyValueStore;

use Aws\DynamoDb\Marshaler;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\dynamodb_client\Connection;

/**
 * Defines the key/value store factory for the dynamodb backend.
 */
class KeyValueDynamoDbExpirableFactory implements KeyValueExpirableFactoryInterface {

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The DynamoDb connection.
   *
   * @var \Drupal\dynamodb_client\Connection
   */
  protected $dynamodb;

  /**
   * The marshals and unmarshals AWS service for PHP array and JSON.
   *
   * @var \Aws\DynamoDb\Marshaler
   */
  protected $marshaler;

  /**
   * The current time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Holds references to each instantiation so they can be terminated.
   *
   * @var \Drupal\dynamodb_keyvalue\KeyValueStore\DynamoDbStorageExpirable[]
   */
  protected $storages = [];

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\dynamodb_client\Connection $dynamodb
   *   The DynamoDB connection object containing the key-value tables.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The current time.
   */
  public function __construct(SerializationInterface $serializer, Connection $dynamodb, TimeInterface $time) {
    $this->serializer = $serializer;
    $this->dynamodb = $dynamodb;
    $this->marshaler = new Marshaler();
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if (!isset($this->storages[$collection])) {
      $this->storages[$collection] = new DynamoDbStorageExpirable($collection, $this->serializer, $this->dynamodb, $this->marshaler, $this->time);
    }
    return $this->storages[$collection];
  }

}
