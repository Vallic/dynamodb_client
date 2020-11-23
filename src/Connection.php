<?php

namespace Drupal\dynamodb_client;

use Aws\DynamoDb\Exception\DynamoDbException;

/**
 * Logic for executing most common DynamoDB actions within Drupal context.
 *
 * @package Drupal\dynamodb_client
 */
class Connection implements DynamoDbInterface {

  /**
   * The DynamoDB client factory.
   *
   * @var \Drupal\dynamodb_client\ClientFactory
   */
  protected $clientFactory;

  /**
   * The DynamoDB connection.
   *
   * @var \Aws\DynamoDb\DynamoDbClient
   */
  protected $dynamoDb;

  /**
   * The logger channel service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerFactory;

  /**
   * DrupalDynamoDb constructor.
   *
   * @param $clientFactory
   */
  public function __construct($clientFactory) {
    $this->clientFactory = $clientFactory;
    $this->dynamoDb = $clientFactory->connect();
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $params) {
    $output = [];
    $limit = isset($params['limit']);
    do {
      try {

        // Add the ExclusiveStartKey if we got one back in the previous response
        if(isset($results['LastEvaluatedKey'])) {
          $params['ExclusiveStartKey'] = $results['LastEvaluatedKey'];
        }

        $results = $this->dynamoDb->query($params);

        if (isset($results['Items'])) {
          if ($limit) {
            $output = $results['Items'];
          } else {
            $output = array_merge($output, $results['Items']);
          }
        }

      } catch (DynamoDbException $e) {
        $this->logger()->error($e);
      }

      // If there is LastEvaluatedKey in the response and initial parameters
      // does not include limit, then there are more items matching this Query.
      // Fetch them all.
    } while(isset($results['LastEvaluatedKey']) && !$limit);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function scan(array $params) {
    $output = [];
    $limit = isset($params['limit']);
    do {
      try {

        // Add the ExclusiveStartKey if we got one back in the previous response
        if(isset($results['LastEvaluatedKey'])) {
          $params['ExclusiveStartKey'] = $results['LastEvaluatedKey'];
        }

        $results = $this->dynamoDb->scan($params);

        if (isset($results['Items'])) {
          if ($limit) {
            $output = $results['Items'];
          } else {
            $output = array_merge($output, $results['Items']);
          }
        }

      } catch (DynamoDbException $e) {
        $this->logger()->error($e);
      }

      // If there is LastEvaluatedKey in the response and initial parameters
      // does not include limit, then there are more items matching this Query.
      // Fetch them all.
    } while(isset($results['LastEvaluatedKey']) && !$limit);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getItem(array $params) {
    try {
      $result = $this->dynamoDb->getItem($params);

    } catch (DynamoDbException $e) {
      $this->logger()->error($e);
    }
    return $result['Item'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function putItem(array $params) {
    try {
      $result = $this->dynamoDb->putItem($params);
    } catch (DynamoDbException $e) {
      $this->logger()->error($e);
    }
    return isset($result['@metadata']);
  }

  /**
   * {@inheritdoc}
   */
  public function updateItem(array $params) {
    try {
      $result = $this->dynamoDb->updateItem($params);
    } catch (DynamoDbException $e) {
      $this->logger()->error($e);
    }
    return $result['Attributes'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem(array $params) {
    try {
      $result = $this->dynamoDb->deleteItem($params);
    } catch (DynamoDbException $e) {
      $this->logger()->error($e);
    }
    return $result['Attributes'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function batchWriteItem(array $params) {
    try {
      $result = $this->dynamoDb->batchWriteItem($params);
    } catch (DynamoDbException $e) {
      $this->logger()->error($e);
    }
    return isset($result['@metadata']);
  }

  /**
   * {@inheritdoc}
   */
  public function batchGetItem(array $params) {
    try {
      $results = $this->dynamoDb->batchGetItem($params);
    } catch (DynamoDbException $e) {
      $this->logger()->error($e);
    }
    return $results['Responses'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function createTable(array $params) {
    // If there is nothing specified on params, use global.
    if (!isset($params['ProvisionedThroughput'], $params['BillingMode'])) {
      $params += DynamoDb::getBillingMode($params['TableName']);
    }

    try {
      $this->dynamoDb->createTable($params);
      $this->dynamoDb->waitUntil('TableExists', [
        'TableName' => $params['TableName'],
        '@waiter' => [
          'delay'       => 3,
          'maxAttempts' => 5
        ]
      ]);

    } catch (DynamoDbException $e) {
      $this->logger()->error($e);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function updateTable(array $params) {
    try {
      $this->dynamoDb->updateTable($params);
    } catch (DynamoDbException $e) {
      $this->logger()->error($e);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTable(array $params) {
    try {
      $this->dynamoDb->deleteTable($params);
      $this->dynamoDb->waitUntil('TableNotExists', [
        'TableName' => $params['TableName'],
        '@waiter' => [
          'delay'       => 3,
          'maxAttempts' => 5
        ]
      ]);
    } catch (DynamoDbException $e) {
      $this->logger()->error($e);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function listTables(array $params) {
    try {
      $this->dynamoDb->listTables($params);
    } catch (DynamoDbException $e) {
      $this->logger()->error($e);
    }
    return $response['TableNames'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    return $this->clientFactory->connect();
  }

  /**
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected function logger() {
    if (!$this->loggerFactory) {
      $this->loggerFactory = \Drupal::service('logger.factory')->get('dynamodb_client');
    }
    return $this->loggerFactory;
  }

}
