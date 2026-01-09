# Usage and CRUD

This guide assumes you already created map classes as shown in the mapping
guide and connected a PDO instance.

## Connect

```php
<?php

use divengine\orm;

$pdo = orm::buildPDO([
    'type' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'name' => 'mydb',
    'user' => 'me',
    'pass' => 'secret'
], true);
```

You can also connect per instance with `$model->connect($pdo)`.

## Insert

```php
$person = new Person(['name' => 'Peter']);
$person->insert();
```

For collections:

```php
$list = new PersonCollection();
$list->addItem(['name' => 'Alice']);
```

## Load records

```php
$list = new PersonCollection();
$list->load(10, 0, '*', 'name = ?', ['Peter']);

$first = $list->getFirstItem('id = ?', [100]);
```

`getAll()` returns data after `load()`:

```php
$rows = $list->getAll('status = ?', ['active']);
```

## Update

`save()` updates a record using `__map_identity`:

```php
$person = $list->getFirstItem('id = ?', [100]);
$person->name = 'Updated';
$person->save();
```

You can also pass an explicit `$fields` array and `$identity` string.

## Delete

```php
$person->delete();
```

For collections:

```php
$list->delete('status = ?', ['inactive']);
```

## Related loading (joins)

`loadRelated()` joins two maps and loads data into the current instance:

```php
$orders = new OrderCollection();
$customers = new CustomerCollection();

$orders->loadRelated(
    $customers,
    joins: [
        ['customer_id' => 'id']
    ],
    filters: 'orders.status = ?',
    params: ['paid']
);
```

## Raw queries

```php
$rows = $list->rawQuery('SELECT * FROM person WHERE id = ?', [100]);
$list->execute('SELECT * FROM person WHERE status = ?', ['active']);
```

`execute()` also updates the internal data store based on map type.

## Meta queries

You can register a custom processor to transform a meta-query to SQL:

```php
orm::setMetaQueryProcessor(function ($query, $args) {
    return str_replace(':table', $args['table'], $query);
});

$list->metaQuery('SELECT * FROM :table WHERE status = ?', ['active'], [
    'table' => 'person'
]);
```

Note: `processMetaQuery()` prints the generated SQL and timing.
