<?php

namespace Drupal\dynamodb_client;

use Aws\Credentials\Credentials;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Site\Settings;

/**
 * Class ClientFactory.
 */
class ClientFactory {

  /**
   * The 'dynamodb' client settings.
   *
   * @var string[]
   *   Array of site settings.
   */
  protected $settings;

  /**
   * @var string
   */
  protected $alias;

  /**
   * The DynamoDB client.
   *
   * @var \Aws\DynamoDb\DynamoDbClient
   *   The DynamoDb client instance.
   */
  protected $client;

  /**
   * Constructor.
   *
   * @param string $alias
   */
  public function __construct($alias = DynamoDb::DYNAMO_DB_DEFAULT) {
    $this->settings = Settings::get('dynamodb_client');
    $this->alias = $alias;
  }

  /**
   * Return a Client instance for a given alias.
   *
   * @return \Aws\DynamoDb\DynamoDbClient
   *   A Client instance for the chosen server.
   */
  public function connect() {
    if (!isset($this->settings[$this->alias])) {
      throw new \InvalidArgumentException((string) (new FormattableMarkup('Nonexistent DynamoDB connection alias: @alias', [
        '@alias' => $this->alias,
      ])));
    }
    if (!isset($this->client[$this->alias])) {
      $settings = $this->settings[$this->alias];

      $connection_info = [
        'endpoint'   => $settings['endpoint'],
        'region'   => $settings['region'],
        'version'  => $settings['version'] ?? 'latest',
      ];

      if (isset($settings['aws_access_key'], $settings['aws_secret_key'])) {
        $connection_info['credentials'] = new Credentials($settings['aws_access_key'], $settings['aws_secret_key']);
      }

      try {
        $this->client[$this->alias] = new DynamoDbClient($connection_info);
      } catch (DynamoDbException $e) {
        $this->client[$this->alias] = NULL;
      }

    }
    return $this->client[$this->alias];
  }

  /**
   * Return initialized Drupal instance alias.
   *
   * @return string
   *   Return Drupal instance name string.
   */
  public function getInstanceId() {
    return $this->alias;
  }

}
