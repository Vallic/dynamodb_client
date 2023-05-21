<?php

namespace Drupal\dynamodb_client;

use Aws\DynamoDb\DynamoDbClient;

/**
 * Interface for Drupal wrapper around DynamoDB service.
 *
 * @package Drupal\dynamodb_client
 */
interface DynamoDbInterface {

  /**
   * Query DynamoDB table on provided parameters.
   *
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return array
   *   Return result set or empty array.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_Query.html
   */
  public function query(array $params): array;

  /**
   * Scan DynamoDB table based on provided parameters.
   *
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return array
   *   Return result set or empty array.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_Scan.html
   */
  public function scan(array $params): array;

  /**
   * Fetch single item from DynamoDB by its keys.
   *
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return array
   *   Return result set or empty array.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_GetItem.html
   */
  public function getItem(array $params): array;

  /**
   * Insert new or replaced old item in DynamoDB table.
   *
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return bool
   *   Return TRUE if update or replace of item where successful.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_PutItem.html
   */
  public function putItem(array $params): bool;

  /**
   * Update item attributes or create new item on DynamoDB table.
   *
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return array
   *   Return result set or empty array.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_UpdateItem.html
   */
  public function updateItem(array $params): array;

  /**
   * Delete item from DynamoDB table.
   *
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return array
   *   Return result set or empty array.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_DeleteItem.html
   */
  public function deleteItem(array $params): array;

  /**
   * Batch put or delete multiple items on DynamoDB table.
   *
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return bool
   *   Return TRUE upon successful execution.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_BatchWriteItem.html
   */
  public function batchWriteItem(array $params): bool;

  /**
   * Batch get items from DynamoDB table by item keys.
   *
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return array
   *   Return result set or empty array.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_BatchGetItem.html
   */
  public function batchGetItem(array $params): array;

  /**
   * Create DynamoDB table.
   *
   * @param array $params
   *   DynamoDB params.
   *
   * @return bool
   *   Return TRUE upon successful creating the table.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_CreateTable.html
   */
  public function createTable(array $params): bool;

  /**
   * Update existing DynamoDB table.
   *
   * @param array $params
   *   DynamoDB params.
   *
   * @return bool
   *   Return TRUE upon successful updating the table.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_UpdateTable.html
   */
  public function updateTable(array $params): bool;

  /**
   * Deletes existing DynamoDB table.
   *
   * @param array $params
   *   DynamoDB params.
   *
   * @return bool
   *   Return TRUE upon successfully deletion of the table.
   */
  public function deleteTable(array $params): bool;

  /**
   * Fetch all DynamoDB tables from current server.
   *
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return array
   *   Return result set or empty array.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_ListTables.html
   */
  public function listTables(array $params): array;

  /**
   * Get direct DynamoDB connection client.
   *
   * @return \Aws\DynamoDb\DynamoDbClient
   *   Return DynamoDB client.
   */
  public function getClient(): DynamoDbClient;

}
