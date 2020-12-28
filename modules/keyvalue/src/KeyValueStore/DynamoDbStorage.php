<?php

namespace Drupal\dynamodb_keyvalue\KeyValueStore;

use Aws\DynamoDb\Marshaler;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\KeyValueStore\StorageBase;
use Drupal\dynamodb_client\Connection;
use Drupal\dynamodb_client\DynamoDb;

/**
 * Defines a DynamoDB key/value store implementation.
 */
class DynamoDbStorage extends StorageBase {

  use DependencySerializationTrait;

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The dynamodb connection.
   *
   * @var \Drupal\dynamodb_client\Connection
   */
  protected $dynamodb;

  /**
   * The name of the SQL table to use.
   *
   * @var string
   */
  protected $table;

  /**
   * The marshals and unmarshals AWS service for PHP array and JSON.
   *
   * @var \Aws\DynamoDb\Marshaler
   */
  protected $marshaler;

  /**
   * DynamoDB constructor for key value.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\dynamodb_client\Connection $dynamodb
   *   The DynamoDB connection.
   * @param string $table
   *   The name of the SQL table to use, defaults to key_value.
   * @param \Aws\DynamoDb\Marshaler $marshaler
   *   The marshals and unmarshals AWS service for PHP array and JSON.
   */
  public function __construct($collection, SerializationInterface $serializer, Connection $dynamodb, Marshaler $marshaler, $table = 'key_value') {
    parent::__construct($collection);
    $this->serializer = $serializer;
    $this->dynamodb = $dynamodb;
    $this->table = $table;
    $this->marshaler = $marshaler;
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    return (bool) $this->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    $params = [
      'TableName' => $this->table,
      'Key' => [
        'collection' => [
          'S' => $this->collection,
        ],
        'name' => [
          'S' => $key,
        ],
      ],
      'ProjectionExpression' => '#val',
      'ExpressionAttributeNames' => [
        '#val' => 'value',
      ],
    ];
    $result = $this->dynamodb->getItem($params);

    if ($result) {
      $item = $this->marshaler->unmarshalItem($result, TRUE);
      return $this->serializer->decode($item->value);
    }

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    if (empty($keys)) {
      return [];
    }
    $values = [];

    // If we have multiple items, perform batchGetItem.
    // Otherwise make single getItem request.
    if (count($keys) > 1) {
      // Use batch get Items.
      foreach (array_chunk($keys, DynamoDb::DYNAMO_DB_BATCH_GET_LIMIT, TRUE) as $items) {
        $data = [];
        foreach ($items as $id => $key) {
          $data['Keys'][] = [
              'collection' => ['S' => $this->collection],
              'name' => ['S' => $key],
            ];
        }

        $params = [
          'RequestItems' => [
            $this->table => $data,
          ],
        ];

        $results = $this->dynamodb->batchGetItem($params);

        if (isset($results[$this->table])) {
          foreach ($results[$this->table] as $result) {
            $item = $this->marshaler->unmarshalItem($result, TRUE);
            $values[$item->name] = $this->serializer->decode($item->value);
          }
        }
      }
    }
    else {
      $key = end($keys);
      if ($value = $this->get($key)) {
        $values[$key] = $value;
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    $params = [
      'TableName' => $this->table,
      'KeyConditionExpression' => '#co = :v_c',
      'ExpressionAttributeValues' => [
        ':v_c' => ['S' => $this->collection],
      ],
      'ExpressionAttributeNames' => [
        "#co" => "collection",
        '#vl' => 'value',
        '#nm' => 'name',
      ],
      'ProjectionExpression' => '#vl, #nm',
    ];

    // DynamoDB have limit of 1MB of data per query.
    // But we don't have set limit here, then in query function
    // we can always fetch all results while DynamoDB is returning
    // last fetched ID.
    $results = $this->dynamodb->query($params);

    $values = [];

    foreach ($results as $result) {
      $item = $this->marshaler->unmarshalItem($result, TRUE);
      $values[$item->name] = $this->serializer->decode($item->value);
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $params = [
      'TableName' => $this->table,
      'Item' => [
        'collection' => ['S' => $this->collection],
        'name' => ['S' => $key],
        'value' => ['S' => $this->serializer->encode($value)],
      ],
    ];

    // Put item should either create new entry, or update existing.
    // because of same name and collection key.
    $this->dynamodb->putItem($params);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {
    // Batch write items.
    foreach (array_chunk($data, DynamoDb::DYNAMO_DB_BATCH_WRITE_LIMIT, TRUE) as $items) {
      $values = [];
      foreach ($items as $key => $value) {
        $values[] = [
          'PutRequest' => [
            'Item' => [
              'collection' => ['S' => $this->collection],
              'name' => ['S' => $key],
              'value' => ['S' => $this->serializer->encode($value)],
            ],
          ],
        ];
      }

      $params = [
        'RequestItems' => [
          $this->table => $values,
        ],
      ];

      $this->dynamodb->batchWriteItem($params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setIfNotExists($key, $value) {
    $params = [
      'TableName' => $this->table,
      'Key' => [
        'collection' => [
          'S' => $this->collection,
        ],
        'name' => [
          'S' => $key,
        ],
      ],
      'Item' => [
        'collection' => ['S' => $this->collection],
        'name' => ['S' => $key],
        'value' => ['S' => $this->serializer->encode($value)],
      ],
      'ExpressionAttributeNames' => [
        '#vl' => 'value',
      ],
      "ConditionExpression" => "attribute_not_exists(#vl)",
      "ReturnValues" => "ALL_NEW",
    ];

    return (bool) $this->dynamodb->updateItem($params);
  }

  /**
   * {@inheritdoc}
   */
  public function rename($key, $new_key) {
    $params = [
      'TableName' => $this->table,
      'Key' => [
        'collection' => [
          'S' => $this->collection,
        ],
        'name' => [
          'S' => $key,
        ],
      ],
      'ExpressionAttributeNames' => [
        '#na' => 'name',
      ],
      'ExpressionAttributeValues' => [
        ':new_key' => ['S' => $new_key],
      ],
      'UpdateExpression' => 'set #na = :new_key',
    ];

    $this->dynamodb->updateItem($params);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    foreach (array_chunk($keys, DynamoDb::DYNAMO_DB_BATCH_WRITE_LIMIT, TRUE) as $items) {
      $values = [];
      foreach ($items as $id => $key) {
        $values[] = [
          'DeleteRequest' => [
            'Key' => [
              'collection' => ['S' => $this->collection],
              'name' => ['S' => $key],
            ],
          ],
        ];
      }

      $params = [
        'RequestItems' => [
          $this->table => $values,
        ],
      ];

      $this->dynamodb->batchWriteItem($params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $params = [
      'TableName' => $this->table,
      'Key' => [
        'collection' => [
          'S' => $this->collection,
        ],
        'name' => [
          'S' => $key,
        ],
      ],
    ];

    $this->dynamodb->deleteItem($params);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $params = [
      'TableName' => $this->table,
      'KeyConditionExpression' => '#co = :v_c',
      'ExpressionAttributeValues' => [
        ':v_c' => ['S' => $this->collection],
      ],
      'ExpressionAttributeNames' => [
        "#co" => "collection",
        '#nm' => 'name',
      ],
      'ProjectionExpression' => '#nm',
    ];

    $results = $this->dynamodb->query($params);

    $values = [];

    foreach ($results as $result) {
      $item = $this->marshaler->unmarshalItem($result, TRUE);
      $values[] = $item->name;
    }
    // Delete in batches.
    $this->deleteMultiple($values);
  }

}
