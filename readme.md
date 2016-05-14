# Solution10\CircuitBreaker

Simple circuit breaker class.

[![Build Status](https://travis-ci.org/Solution10/circuitbreaker.svg?branch=master)](https://travis-ci.org/Solution10/circuitbreaker)
[![Latest Stable Version](https://poser.pugx.org/solution10/circuitbreaker/v/stable.svg)](https://packagist.org/packages/solution10/circuitbreaker)
[![Total Downloads](https://poser.pugx.org/solution10/circuitbreaker/downloads.svg)](https://packagist.org/packages/solution10/circuitbreaker)
[![License](https://poser.pugx.org/solution10/circuitbreaker/license.svg)](https://packagist.org/packages/solution10/circuitbreaker)

## Features

- Adjustable thresholds
- Adjustable cooldowns
- Events on change
- Any Doctine\Common\Cache\Cache implementation for persistence

## Installation

Installation is via composer, in the usual manner:

```sh
$ composer require solution10/circuitbreaker
```

## Example usage

```php
<?php

$persistence = new \Doctrine\Common\Cache\ArrayCache();
$breaker = new \Solution10\CircuitBreaker\CircuitBreaker('my_backend_service', $persistence);

if ($breaker->isClosed()) {
    $response = doSomething();
    if ($response) {
        $breaker->success();
    } else {
        $breaker->failure();
    }
} else {
    gracefullyDegrade();
}
```

### Userguide

[Check out the Wiki](https://github.com/Solution10/circuitbreaker/wiki)

(or the /docs folder in the repo)


## PHP Requirements

- PHP >= 5.5

## Author

Alex Gisby: [GitHub](http://github.com/alexgisby), [Twitter](http://twitter.com/alexgisby)

## License

[MIT](http://github.com/solution10/circuitbreaker/tree/master/LICENSE.md)

## Contributing

[Contributors Notes](http://github.com/solution10/circuitbreaker/tree/master/CONTRIBUTING.md)
