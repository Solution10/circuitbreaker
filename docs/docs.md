# Solution10\CircuitBreaker

- [Why use a circuitbreaker](#why-use-a-circuitbreaker)
- [Using a breaker](#using-a-breaker)
- [Change Events](#change-events)
- [Breaker Board](#breaker-board)

## Why use a circuitbreaker

Breakers are useful for defending backend services.

Let's say you have a RESTFUL API that you call to build your webpages, but that service
goes down. How do you stop your app hammering an already poorly service with requests for
data?

Enter circuitbreakers. By positioning a circuitbreaker in front of calls to your backend, you
can check it's status before calling it, and leave it alone for a while if it's down.

You can configure thresholds for the breaker so that you can ignore blips in the service.
It would be rubbish if because of a single dropped connection you turn off a whole section
of your website. By giving a number of failures before "opening", you can ignore blips and only
catch true outages.

The final part of the puzzle is the "cooldown" rate. The cooldown dictates the "window" by
which you care about failures. Read this value in combination with the thresholds like so:

> if you see 3 failures (threshold) within 300 seconds (cooldown), OPEN the breaker

**REMEMBER**: the language around circuit breakers is a little weird. CLOSED is good, OPEN
is bad. Imagine a switch in a circuit, if the circuit is broken (OPEN) then current can't flow.
If the loop is CLOSED, everything is good.

## Using a breaker

Enough theory, how do you use it? Like this:

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

Firstly, we need to have somewhere to store the state of the breaker. You can use anything
that conforms to the Doctrine\Common\Cache\Cache interface (and don't use ArrayCache for real
as it won't persist!)

We then define the breaker with a name and the storage adapter.

We can now see if it's safe to call our service using the `isClosed()` method (remember, closed
is good!)

Once we've made our call, we then need to tell the breaker how it went. We do that with the
`success()` and `failure()` methods.

To alter the threshold and cooldown values, use the `setThreshold()` and `setCooldown()` functions
on the breaker:

```php
$persistence = new \Doctrine\Common\Cache\ArrayCache();
$breaker = new \Solution10\CircuitBreaker\CircuitBreaker('my_backend_service', $persistence);

$breaker
    ->setThreshold(10)      // 10 failures
    ->setCooldown(400);     // over 400 seconds
```

Make sure you do this before you start calling `isOpen()` or `isClosed()` so that the breaker
calculates its state correctly!

And it's as simple as that!

## Change Events

It can be useful to know when a breaker changes state so you can log and monitor it. Easy peasy.

```php
$persistence = new \Doctrine\Common\Cache\ArrayCache();
$breaker = new \Solution10\CircuitBreaker\CircuitBreaker('my_backend_service', $persistence);

$breaker->onChange(function ($previousState, $newState)) {
    error_log('Breaker went from '.$previousState.' to '.$newState.');
}
```

The `$previousState` and `$newState` variables passed to the callback are constant strings
that you can compare against `CircuitBreaker::OPEN` and `CircuitBreaker::CLOSED` to be safe.

## Breaker Board

Creating and keeping track of circuit breakers can be a bit of a pain. The library provides a
BreakerBoard class to allow you to logically group and create circuit breakers.

```php
<?php

$persistence = new \Doctrine\Common\Cache\ArrayCache();
$board = new Solution10\CircuitBreaker\BreakerBoard($persistence);

// You can now create/get circuit breaker instances by simply requesting them by name:
// (Requesting the same name twice will naturally return an instance to the same circuitbreaker)
/* @var     \Solution10\CircuitBreaker\CircuitBreaker   $userBackendBreaker     */
$userBackendBreaker = $board->getBreaker('users');

// If you don't want to use the getBreaker() factory, you can set your own breakers to the board:
$myBreaker = new Solution10\CircuitBreaker\CircuitBreaker('snowflake', $persistence);
$board->setBreaker($myBreaker);

// And you can grab all of the circuit breakers assigned to a board like so:
$allBreakers = $board->getBreakers();

```
