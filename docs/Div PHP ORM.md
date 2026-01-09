[![Readme Card](https://github-readme-stats.vercel.app/api/pin/?username=divengine&repo=orm&show_owner=true&rand=23)](https://github.com/anuraghazra/github-readme-stats)

# Div PHP ORM

Div PHP ORM provides a minimal object-relational mapping layer using classes
and reflection. You define map classes that describe how database tables and
records relate to PHP objects, and the base `orm` class provides CRUD helpers.

## What it is good for

- Small and mid-sized projects that want explicit mappings.
- Class-based hierarchies (schema -> table -> record).
- Simple CRUD without a full framework.

## What it is not

- A feature-complete ORM with migrations and schema tooling.
- A query builder that hides SQL.
- A replacement for Doctrine or Eloquent.

## Core ideas

- Maps are classes that extend `divengine\orm`.
- `RECORD` maps one row to one object.
- `TABLE` maps a table to a collection of objects.
- Property names map to column names using camelCase and underscore rules.

See the rest of the docs for mapping and usage details.
