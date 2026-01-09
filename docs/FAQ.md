# FAQ

## Is this a full-featured ORM?

No. It is a minimal ORM with explicit mappings and helper methods. It does not
include migrations, schema tools, or advanced query builders.

## How are columns mapped to properties?

The ORM tries the original name, camelCase, and underscore variants. If a
public getter or setter exists (e.g. `getName`, `setName`), it is preferred.

## How do I set the table name?

Set `protected $__map_name = 'table_name';` on your map class. If you do not
set it, the class name is converted to underscore case.

## How do I work with schemas?

Set `protected $__map_schema = 'schema_name';` to prefix the table name with a
schema.

## What does `AUTOMATIC` do?

If a property is set to `orm::AUTOMATIC`, it is skipped during insert so the
database can generate it.

## Why does `metaQuery()` print SQL?

`processMetaQuery()` echoes the query and timing for debugging. If you do not
want this output, avoid `metaQuery()` or wrap it to silence output.

## Which PHP versions are supported?

The package requires PHP 8.0 or higher.
