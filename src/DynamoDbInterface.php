<?php

namespace Drupal\dynamodb_client;

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
  public function query(array $params);

  /**
   * Scan DynamoDB table based on provided paramters.
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return array
   *   Return result set or empty array.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_Scan.html
   */
  public function scan(array $params);

  /**
   * Fetch single item from DynamoDB by it's keys.
   *
   * @param array $params
   *   DynamoDB query parameters.
   *
   * @return array
   *   Return result set or empty array.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_GetItem.html
   */
  public function getItem(array $params);

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
  public function putItem(array $params);

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
  public function updateItem(array $params);

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
  public function deleteItem(array $params);

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
  public function batchWriteItem(array $params);

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
  public function batchGetItem(array $params);

  /**
   * Create DynamoDB table.
   *
   * @param array $params
   *   DynamoDB parameters.
   *
   * @return bool
   *   Return TRUE upon successful creating the table.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_CreateTable.html
   */
  public function createTable(array $params);

  /**
   * Update existing DynamoDB table.
   *
   * @param array $params
   *   DynamoDB parameters.
   *
   * @return bool
   *   Return TRUE upon successful updating the table.
   *
   * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_UpdateTable.html
   */
  public function updateTable(array $params);

  /**
   * Deletes existing DynamoDB table.
   *
   * @param array $params
   *   DynamoDB parameters.
   *
   * @return bool
   *   Return TRUE upon successfully deletion of the table.
   */
  public function deleteTable(array $params);

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
  public function listTables(array $params);

}
