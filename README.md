# Div PHP ORM

Div PHP ORM is a lightweight, class-based ORM that maps database rows to PHP
objects using inheritance and reflection. It is designed for small projects
where you want explicit mappings without a large framework.

## Requirements

- PHP 8.0 or higher
- ext-pdo, ext-json

## Installation

```shell
composer require divengine/orm
```

## Quick start

```php
<?php

require 'vendor/autoload.php';

use divengine\orm;

class PublicMap extends orm
{
    protected $__map_type = self::SCHEMA;
    protected $__map_schema = 'public';
    protected $__map_identity = 'id = :id';
}

class PersonMap extends PublicMap
{
    protected $__map_type = self::RECORD;
    protected $__map_name = 'person';
    protected $__map_class = Person::class;
}

class Person extends PersonMap
{
    public $id = self::AUTOMATIC;
    public $name;
}

class PersonCollection extends PersonMap
{
    protected $__map_type = self::TABLE;
}

$pdo = orm::buildPDO([
    'type' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'name' => 'mydb',
    'user' => 'me',
    'pass' => 'secret'
], true);

$person = new Person(['name' => 'Peter']);
$person->insert();

$list = new PersonCollection();
$first = $list->getFirstItem('id = ?', [100]);
```

## Docs

See `docs/README.md` for installation, mapping, and usage guides.

## License

This project is licensed under the GNU General Public License. See `LICENSE`.
