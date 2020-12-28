<?php

namespace Drupal\dynamodb_keyvalue\KeyValueStore;

use Aws\DynamoDb\Marshaler;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\dynamodb_client\Connection;
use Drupal\dynamodb_client\DynamoDb;

/**
 * Defines a dynamodb key/value store implementation for expiring items.
 */
class DynamoDbStorageExpirable extends DynamoDbStorage implements KeyValueStoreExpirableInterface {

  /**
   * The current time.
   * @var \Drupal\Component\Datetime\TimeInterface|string
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function __construct($collection, SerializationInterface $serializer, Connection $dynamodb, Marshaler $marshaler, TimeInterface $time, $table = 'key_value_expire') {
    parent::__construct($collection, $serializer, $dynamodb, $marshaler, $table);
    $this->time = $time;
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
      'KeyConditionExpression' => '#co = :col AND #nm = :na',
      'ExpressionAttributeValues' => [
        ':col' => ['S' => $this->collection],
        ':na' => ['S' => $key],
        ':exp' => ['N' => $this->time->getRequestTime()],
      ],
      'ExpressionAttributeNames' => [
        "#co" => "collection",
        '#vl' => 'value',
        '#nm' => 'name',
      ],
      'Limit' => 1,
      'ProjectionExpression' => '#vl',
      'FilterExpression' => 'expire > :exp'
    ];

    $results = $this->dynamodb->query($params);

    if ($results) {
      $result = end($results);
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

    // If we have multiple items, perform single get.
    // We can't use FilterExpression with sort key.
    // Even if we could, DynamoDB counts reads same with or without
    // FilterExpression
    // And we can't use batchGetItem, and scan could be maybe overkill.
    // Make basic single get request.

    foreach ($keys as $key) {
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
      'IndexName' => 'expired_index',
      'KeyConditionExpression' => '#co = :col AND expire > :exp',
      'ExpressionAttributeValues' => [
        ':col' => ['S' => $this->collection],
        ':exp' => ['N' => $this->time->getRequestTime()],
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
  public function setWithExpire($key, $value, $expire) {
    $params = [
      'TableName' => $this->table,
      'Item' => [
        'collection' => ['S' => $this->collection],
        'name' => ['S' => $key],
        'value' => ['S' => $this->serializer->encode($value)],
        'expire' => ['N' => $this->time->getRequestTime() + $expire],
      ],
    ];

    // Put item should either create new entry, or update existing.
    // because of same name and collection key.
    $this->dynamodb->putItem($params);
  }

  /**
   * {@inheritdoc}
   */
  public function setWithExpireIfNotExists($key, $value, $expire) {
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
        'expire'=> ['N' => $this->time->getRequestTime() + $expire]
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
  public function setMultipleWithExpire(array $data, $expire) {
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
              'expire' => ['N' => $this->time->getRequestTime() + $expire]
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
  public function deleteMultiple(array $keys) {
    parent::deleteMultiple($keys);
  }

}
