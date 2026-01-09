# Installation

## Composer

```shell
composer require divengine/orm
```

## Requirements

- PHP 8.0 or higher
- ext-pdo
- ext-json

## Database connection

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

Passing `true` as the second argument registers the PDO instance globally
for all ORM instances.
