DynamoDB
-----
The Drupal DynamoDB module provides integration with AWS DynamoDB services.
Current integration provides basic database wrapper for use with DynamoDB.

# Features
* DynamoDB table CRUD operations: create, delete, update and list tables
* DynamoDB write, read, update and query data.
* Option to use multiple DynamoDB instances.
* Granular settings per each DynamoDB instance / table.
* Database service which follows core connection layer
* Ability to query default instance, or any other.
* Simplicity in use as any other database implementation.

# Available methods
### Data query / write.
* query
* scan
* getItem
* putItem
* updateItem
* deleteItem
* batchWriteItem
* batchGetItem

### Table actions.
* createTable
* updateTable
* deleteTable
* listTables

# Settings
Similar to the core database settings array with DynamoDB integration we
can define DynamoDB connection details

```
$settings['dynamodb_client'] = [
  'default' => [
    'endpoint'   => 'http://dynamodb:8000',
    'region'   => 'us-west-1',
    'version'  => 'latest',
    'aws_access_key' => 'dummy',
    'aws_secret_key' => 'dummy',
    'aws_billing' => [
      "BillingMode" => "PAY_PER_REQUEST",
    ],
    'consistent_read' => FALSE,
    'table_settings' => [
      'key_value' => [
        'consistent_read' => TRUE,
      ]
    ]
  ]
];
```

* Note that `aws_billing` and `consistent_read` can be defined per instances
  but as well per each table separately under the key `table_settings`.
  In above example we set that consistent_read is FALSE,
  but for `key_value` table is set to TRUE.

* `aws_billing` values are required, you can set your billing mode to
`PAY_PER_REQUEST` or `PROVISIONED`.  If you set to `PROVISIONED` then you need
  to define capacity, as example below
  ```
    'aws_billing' => [
      'ProvisionedThroughput' => [
        'ReadCapacityUnits' => 10,
        'WriteCapacityUnits' => 5,
      ],
      "BillingMode" => "PROVISIONED",
    ],
  ```

Note that read consistency (`consistent_read` option inside settings)
can effect your billing and as well results which you could get back
from DynamoDb. See more details below.

**Read more about:**
* [Billing parameters](https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_BillingModeSummary.html)
* [Read consistency](https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/HowItWorks.ReadConsistency.html)

**Important**
Whilst needs of different projects could be different, for that reason billing
and read consistency can be defined trough settings per an
instance or per each table. As well, read consistency can be set inside each
query which you perform if you choose to add `ConsistencyRead => TRUE`
inside your query.

# Examples of usage

### Initialize connection - default instance
```
# Drupal core Mysql.
Drupal::database();

# DynamoDB - access to DynamoDB trough Drupal wrapper.
DynamoDb::database();

# DynamoDB - access directly to DynamoDB, without Drupal wrapper.
DynamoDb::rawDatabase();
```

### Initialize connection - different instance
```
# Drupal core Mysql.
Drupal::database('mysql_replica');

# DynamoDB
DynamoDb::database('my_second_instances');
```

### Fetch data from key_value table
```

# DynamoDB - uses arrays as parameters for query to be executed.

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
```

* you can see more examples in dynamodb_keyvalue submodule

## KeyValue
* drop-in replacement for the core KeyValueStore which uses MySQL
  as default storage
* support for key_value and key_value_expire storages
* enable the submodule

IMPORTANT: key_value is essential for Drupal site to be working properly.
First enable the submodule, and during that process,
migration would be performed of entries from MySQL tables to new
DynamoDB tables. After the submodule is enabled,
add parameters change in your services.yml file.
```
parameters:
factory.keyvalue.expirable:
keyvalue_expirable_default: keyvalue.expirable.dynamodb
factory.keyvalue:
default: keyvalue.dynamodb
```

## Session
TBD - implement DynamoDB sessions.

## DynamoDB documentation
* https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/GettingStartedDynamoDB.html
* https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/Introduction.html
