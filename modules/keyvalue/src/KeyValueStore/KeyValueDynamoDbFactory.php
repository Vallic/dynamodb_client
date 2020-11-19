<?php

namespace Drupal\dynamodb_keyvalue\KeyValueStore;

use Aws\DynamoDb\Marshaler;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\dynamodb_client\Connection;

/**
 * Defines the key/value store factory for the dynamodb backend.
 */
class KeyValueDynamoDbFactory implements KeyValueFactoryInterface {

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The DynamoDB connection to use.
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
   * Holds references to each instantiation so they can be terminated.
   *
   * @var \Drupal\dynamodb_keyvalue\KeyValueStore\DynamoDbStorage[]
   */
  protected $storages = [];

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\dynamodb_client\Connection $dynamodb
   *   The Connection object containing the key-value tables.
   */
  public function __construct(SerializationInterface $serializer, Connection $dynamodb) {
    $this->serializer = $serializer;
    $this->dynamodb = $dynamodb;
    $this->marshaler = new Marshaler();
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if (!isset($this->storages[$collection])) {
      $this->storages[$collection] = new DynamoDbStorage($collection, $this->serializer, $this->dynamodb, $this->marshaler);
    }
    return $this->storages[$collection];
  }

}
