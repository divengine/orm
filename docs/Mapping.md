# Mapping guide

Div PHP ORM uses class inheritance to define how database objects map to PHP
objects. You describe schemas, tables, and records by extending `divengine\orm`
and setting protected mapping fields.

## Map types

- `SCHEMA`: base class that sets a database schema name.
- `TABLE`: a collection of records.
- `RECORD`: a single row.
- `VIEW`, `PROCEDURE`, `QUERY`, `OBJECT`: reserved for advanced usage.

## Key mapping fields

- `__map_type`: one of the map type constants.
- `__map_name`: table or object name. Defaults to class name in underscore case.
- `__map_schema`: schema name, prefixed to the map name.
- `__map_class`: class to instantiate when loading records.
- `__map_identity`: SQL filter used for `save()` and `delete()` (e.g. `id = :id`).

## Example mapping hierarchy

```php
<?php

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
```

## Property naming rules

When reading or writing data, the ORM tries these names:

- Original property name
- camelCase version
- underscore version

If a public getter or setter exists (`getName`, `setName`), it is preferred.

## Automatic columns

Set a property to `orm::AUTOMATIC` to skip it on insert and let the database
generate the value.
