September 18, 2019
- release 0.2.0 version
- New method for build a PDO instances, orm::buildPDO($config, $connectGlobal);
This code build PDO, and make global connection

```php
<?php

use divengine\orm;

orm::buildPDO([
    'type' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'name' => 'mydb',
    'user' => 'me',
    'pass' => 'mysuperpass'
], true);
```

September 9, 2019
---------------
- release 0.1.0 version
