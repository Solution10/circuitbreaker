<?php

namespace Solution10\CircuitBreaker;

use Doctrine\Common\Cache\Cache;

/**
 * Class BreakerBoard
 *
 * Works like a factory or registry for Circuit breakers, simplifying the construction
 * process and allowing you to access instances from elsewhere.
 *
 * @package     Solution10\CircuitBreaker
 * @author      Alex Gisby <alex@solution10.com>
 * @license     MIT
 */
class BreakerBoard
{
    /**
     * @var     array
     */
    protected $instances = [];

    /**
     * @var     Cache
     */
    protected $storage;

    /**
     * Pass in the storage engine you want to use for the breakers.
     *
     * @param   Cache   $cache
     */
    public function __construct(Cache $cache)
    {
        $this->storage = $cache;
    }

    /**
     * Returns a circuit breaker given by name. Will create a new instance if it
     * doesn't exist already, or return one that already does.
     *
     * @param   string      $name
     * @return  CircuitBreaker
     */
    public function getBreaker($name)
    {
        if (!array_key_exists($name, $this->instances)) {
            $this->instances[$name] = new CircuitBreaker($name, $this->storage);
        }
        return $this->instances[$name];
    }

    /**
     * Allows you to set a breaker onto the board that you may have created elsewhere.
     *
     * @param   CircuitBreaker  $breaker
     * @return  $this
     */
    public function setBreaker(CircuitBreaker $breaker)
    {
        $this->instances[$breaker->getName()] = $breaker;
        return $this;
    }

    /**
     * Returns all of the circuit breakers associated with this board
     *
     * @return  CircuitBreaker[]
     */
    public function getBreakers()
    {
        return $this->instances;
    }
}
