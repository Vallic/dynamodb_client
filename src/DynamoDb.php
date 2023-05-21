<?php

namespace Drupal\dynamodb_client;

use Aws\DynamoDb\DynamoDbClient;

/**
 * Main definition for establishing connection.
 *
 * @package Drupal\dynamodb_client
 */
final class DynamoDb {

  public const DYNAMO_DB_DEFAULT = 'default';

  /**
   * DynamoDB batch write limit.
   */
  public const DYNAMO_DB_BATCH_WRITE_LIMIT = 25;

  /**
   * Use limit of 50, not 100, while DynamoDB takes in account as well size.
   */
  public const DYNAMO_DB_BATCH_GET_LIMIT = 50;

  /**
   * An nested array of all active connections.
   *
   * It is keyed by database name and target.
   *
   * @var []\Drupal\dynamodb_client\Connection
   */
  protected static $connections = [];

  /**
   * A nested array of all active connections.
   *
   * It is keyed by database name and target.
   *
   * @var []\Aws\DynamoDb\DynamoDbClient
   */
  protected static $rawConnections = [];

  /**
   * Drupal wrapper with only specifics method available for DynamoDB.
   *
   * @param string $alias
   *   The database alias from Drupal settings.
   *
   * @return \Drupal\dynamodb_client\Connection
   *   Returns Drupal DynamoDB connection
   */
  public static function database(string $alias = self::DYNAMO_DB_DEFAULT): Connection {
    if (!isset(self::$connections[$alias])) {
      self::$connections[$alias] = new Connection(new ClientFactory($alias));
    }

    return self::$connections[$alias];
  }

  /**
   * Direct connection to DynamoDB without Drupal wrapper.
   *
   * @param string $alias
   *   The database alias from Drupal settings.
   *
   * @return \Aws\DynamoDb\DynamoDbClient
   *   Returns AWS DynamoDB connection
   */
  public static function rawDatabase(string $alias = self::DYNAMO_DB_DEFAULT): DynamoDbClient {
    if (!isset(self::$rawConnections[$alias])) {
      $client_factory = new ClientFactory($alias);
      self::$rawConnections[$alias] = $client_factory->connect();
    }

    return self::$rawConnections[$alias];
  }

}
