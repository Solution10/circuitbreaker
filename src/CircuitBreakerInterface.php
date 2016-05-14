<?php

namespace Solution10\CircuitBreaker;

/**
 * Interface CircuitBreakerInterface
 *
 * @package     Solution10\CircuitBreaker
 * @author      Alex Gisby<alex@solution10.com>
 * @license     MIT
 */
interface CircuitBreakerInterface
{
    /**
     * If the operation succeeds, call this function to tell the breaker everything went well.
     *
     * @return  $this
     */
    public function success();

    /**
     * If the operation fails, call this function to tell the breaker something went wrong.
     *
     * @return  $this
     */
    public function failure();

    /**
     * If the breaker is OPEN, you should NOT perform the operation as it may be dangerous.
     *
     * @return  bool
     */
    public function isOpen();

    /**
     * If the breaker is CLOSED, you CAN perform the operation as everything is ok.
     *
     * @return  bool
     */
    public function isClosed();

    /**
     * Forces the breaker to be OPEN (in failure state)
     *
     * @return  $this
     */
    public function forceOpen();

    /**
     * Forces the breaker to be CLOSED (in good state)
     *
     * @return  $this
     */
    public function forceClosed();
}
