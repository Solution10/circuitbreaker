<?php

namespace Solution10\CircuitBreaker\Tests;

use Doctrine\Common\Cache\ArrayCache;
use Solution10\CircuitBreaker\BreakerBoard;
use Solution10\CircuitBreaker\CircuitBreaker;

class BreakerBoardTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $cache = new ArrayCache();
        $board = new BreakerBoard($cache);
        $this->assertInstanceOf('Solution10\\CircuitBreaker\\BreakerBoard', $board);
    }

    public function testConstructingBreakers()
    {
        $cache      = new ArrayCache();
        $board      = new BreakerBoard($cache);
        $breaker    = $board->getBreaker('test');

        $this->assertInstanceOf('Solution10\\CircuitBreaker\\CircuitBreaker', $breaker);
        $this->assertEquals($cache, $breaker->getStorage());
        $this->assertEquals('test', $breaker->getName());
    }

    public function testRepeatGetBreaker()
    {
        $cache      = new ArrayCache();
        $board      = new BreakerBoard($cache);
        $breaker    = $board->getBreaker('test');
        $breaker->mark = 'green';

        $breaker2   = $board->getBreaker('test');
        $breaker3   = $board->getBreaker('unrelated');

        $this->assertEquals($breaker, $breaker2);
        $this->assertEquals('green', $breaker2->mark);
        $this->assertNotEquals($breaker, $breaker3);
    }

    public function testGetBreakers()
    {
        $cache      = new ArrayCache();
        $board      = new BreakerBoard($cache);

        $this->assertEquals([], $board->getBreakers());

        $breaker    = $board->getBreaker('charmander');
        $breaker2   = $board->getBreaker('squirtle');
        $breaker3   = $board->getBreaker('bulbasaur');

        $breakers   = $board->getBreakers();
        $this->assertEquals([
            'charmander' => $breaker,
            'squirtle' => $breaker2,
            'bulbasaur' => $breaker3
        ], $breakers);
    }

    public function testSetBreaker()
    {
        $cache      = new ArrayCache();
        $board      = new BreakerBoard($cache);
        $breaker    = new CircuitBreaker('rogue', $cache);

        $this->assertEquals($board, $board->setBreaker($breaker));
        $this->assertEquals($breaker, $board->getBreaker('rogue'));
    }
}
