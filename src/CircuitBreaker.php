<?php

namespace Solution10\CircuitBreaker;

use Doctrine\Common\Cache\Cache;

/**
 * CircuitBreaker
 *
 * Basic circuit breaker implementation.
 *
 * @package     Solution10\CircuitBreaker
 * @author      Alex Gisby<alex@solution10.com>
 * @license     MIT
 */
class CircuitBreaker implements CircuitBreakerInterface
{
    const OPEN = 'open';
    const CLOSED = 'closed';

    /**
     * @var     string
     */
    protected $name;

    /**
     * @var     Cache
     */
    protected $storage;

    /**
     * @var     int
     */
    protected $threshold = 5;

    /**
     * @var     int
     */
    protected $cooldown = 300;

    /**
     * @var     array
     */
    protected $failures;

    /**
     * @var     array
     */
    protected $changeEventHandlers = [];

    /**
     * @param   string      $name       Name of this breaker
     * @param   Cache       $storage    Persistence for this breaker
     */
    public function __construct($name, Cache $storage)
    {
        $this->name = $name;
        $this->storage = $storage;

        $failures = [];
        $cacheContents = $storage->fetch($this->getCacheKey());
        if (is_array($cacheContents) && array_key_exists('failures', $cacheContents)) {
            $failures = $cacheContents['failures'];
        }
        $this->failures = $failures;
    }

    /**
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return  Cache
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @return  int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * @param   int     $threshold
     * @return  $this
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCooldown()
    {
        return $this->cooldown;
    }

    /**
     * @param   int     $cooldown
     * @return  $this
     */
    public function setCooldown($cooldown)
    {
        $this->cooldown = $cooldown;
        return $this;
    }

    /**
     * If the operation succeeds, call this function to tell the breaker everything went well.
     *
     * @return  $this
     */
    public function success()
    {
        if (!$this->isClosed()) {
            $this->fireEvent(self::OPEN, self::CLOSED);
        }

        $this->failures = [];
        return $this->write();
    }

    /**
     * If the operation fails, call this function to tell the breaker something went wrong.
     *
     * @return  $this
     */
    public function failure()
    {
        $this->failures[] = time();

        // Clean old failures:
        $now = time();
        $cooldownBound = $now - $this->cooldown;
        foreach ($this->failures as $i => $time) {
            if ($time < $cooldownBound) {
                unset($this->failures[$i]);
            }
        }

        if ($this->isOpen()) {
            $this->fireEvent(self::CLOSED, self::OPEN);
        }

        return $this->write();
    }

    /**
     * If the breaker is OPEN, you should NOT perform the operation as it may be dangerous.
     *
     * @return  bool
     */
    public function isOpen()
    {
        return !$this->isClosed();
    }

    /**
     * If the breaker is CLOSED, you CAN perform the operation as everything is ok.
     *
     * @return  bool
     */
    public function isClosed()
    {
        $validFails = 0;
        $now = time();
        $cooldownBound = $now - $this->cooldown;
        foreach ($this->failures as $i => $time) {
            if ($time >= $cooldownBound) {
                $validFails ++;
            }
        }
        return $validFails < $this->threshold;
    }

    /**
     * Forces the breaker to be OPEN (in failure state)
     *
     * @return  $this
     */
    public function forceOpen()
    {
        if (!$this->isOpen()) {
            $this->fireEvent(self::CLOSED, self::OPEN);
        }

        $failTime = time();
        for ($i = 0; $i < $this->threshold; $i ++) {
            $this->failures[] = $failTime;
        }
        return $this->write();
    }

    /**
     * Forces the breaker to be CLOSED (in good state)
     *
     * @return  $this
     */
    public function forceClosed()
    {
        return $this->success();
    }

    /**
     * Binds a function to call when the breaker changes from one state to another.
     *
     * The callback passed should take the form:
     *
     *  function ($previousState, $newState)
     *
     * @param   callable   $callback
     * @return  $this
     */
    public function onChange(callable $callback)
    {
        $this->changeEventHandlers[] = $callback;
        return $this;
    }

    /* ----------------- Protected Helpers ------------------------ */

    /**
     * @return  string
     */
    protected function getCacheKey()
    {
        return 'circuitbreaker_'.$this->getName();
    }

    /**
     * @return  $this
     */
    protected function write()
    {
        $this->storage->save($this->getCacheKey(), ['failures' => $this->failures], $this->cooldown);
        return $this;
    }

    /**
     * @param   string  $prev
     * @param   string  $new
     * @return  $this
     */
    protected function fireEvent($prev, $new)
    {
        foreach ($this->changeEventHandlers as $h) {
            call_user_func_array($h, [$prev, $new]);
        }
        return $this;
    }
}
