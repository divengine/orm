# Div PHP Object Relational Mapping 0.1.0

This class allow to you make a mapping between your database objects and
your PHP objects.

You can implement your model inheriting from divengine\orm. Look at the 
following example as it implements a hierarchy of classes (scheme, map, 
collection, entitlement) and all inherit from the same divengine\orm class.

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

class Person extends PersonMap {

    private $id = self::AUTOMATIC;
    private $name;

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getName() {
        return $this->name;
    }
   
    public function setName($name) {
        $this->name = $name;
    }
   
}

class PersonCollection extends PersonMap {
    protected $__map_type = self::TABLE;
}

```

Now look at an example of how to use your model:
```php
<?php

use divengine\orm;

$pdo = new PDO();
orm::connectGlobal($pdo);

$person = new Person(['name' => 'Peter']);
// $person::connect($pdo); 
$person->insert();

$list = new PersonCollection();
$list->addItem($person);

$entity = $list->getFirstItem('id = ?', [100]);

```

Enjoy!

-- 

@rafageist

Eng. Rafa Rodriguez

https://rafageist.github.io