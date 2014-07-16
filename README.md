Rych Lock
=========

Simple process lock management library.

Installation
------------

Installation is best managed via [Composer](https://getcomposer.org/).

```json
{
    "require": {
        "rych/lock": "1.0.*"
    }
}
```

Or:

```
composer require rych/lock=1.0.*
```

Usage
-----

```php
<?php

$lock = new \Rych\Lock\Lock("lock-name", "/path/to/locks");
if ($lock->lock()) {
    // Do work here
} else {
    die ("Unable to acquire lock! Make sure no other process is running!");
}

$lock->unlock();
```

Methods
-------

- bool \Rych\Lock\Lock::__construct( string $name [, string $bucket = null ] )
- bool \Rych\Lock\Lock::lock( [ bool $block = false ] )
- bool \Rych\Lock\Lock::unlock()
- bool \Rych\Lock\Lock::check( [ string &$pidof = null ] )
