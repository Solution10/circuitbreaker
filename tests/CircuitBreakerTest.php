<?php

namespace Solution10\CircuitBreaker\Tests;

use Doctrine\Common\Cache\ArrayCache;
use Solution10\CircuitBreaker\CircuitBreaker;

class CircuitBreakerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructAndDefaults()
    {
        $c = new ArrayCache();
        $b = new CircuitBreaker('tests', $c);
        $this->assertEquals('tests', $b->getName());
        $this->assertEquals($c, $b->getStorage());
        $this->assertEquals(5, $b->getThreshold());
        $this->assertEquals(300, $b->getCooldown());
        $this->assertFalse($b->isOpen());
        $this->assertTrue($b->isClosed());
    }

    public function testSetGetThreshold()
    {
        $c = new ArrayCache();
        $b = new CircuitBreaker('tests', $c);

        $this->assertEquals(5, $b->getThreshold());
        $this->assertEquals($b, $b->setThreshold(10));
        $this->assertEquals(10, $b->getThreshold());
    }

    public function testSetGetCooldown()
    {
        $c = new ArrayCache();
        $b = new CircuitBreaker('tests', $c);

        $this->assertEquals(300, $b->getCooldown());
        $this->assertEquals($b, $b->setCooldown(100));
        $this->assertEquals(100, $b->getCooldown());
    }

    public function testBreakerTrips()
    {
        $c = new ArrayCache();
        $b = new CircuitBreaker('tests', $c);

        $b->failure();
        $this->assertFalse($b->isOpen());
        $b->failure();
        $this->assertFalse($b->isOpen());
        $b->failure();
        $this->assertFalse($b->isOpen());
        $b->failure();
        $this->assertFalse($b->isOpen());
        $b->failure();
        $this->assertTrue($b->isOpen());
    }

    public function testBreakerRespectsThreshold()
    {
        $c = new ArrayCache();
        $b = new CircuitBreaker('tests', $c);
        $b->setThreshold(2);

        $b->failure();
        $this->assertFalse($b->isOpen());
        $b->failure();
        $this->assertTrue($b->isOpen());
    }

    public function testBreakerLoadedFromStorage()
    {
        $c = new ArrayCache();
        $c->save('circuitbreaker_tests', [
            'failures' => [
                time() - 30, time() - 20, time() - 10
            ]
        ]);

        $b = new CircuitBreaker('tests', $c);
        $b->setThreshold(3);

        $this->assertTrue($b->isOpen());
        $this->assertFalse($b->isClosed());
    }

    public function testBreakerIgnoresOldFailures()
    {
        $c = new ArrayCache();
        $c->save('circuitbreaker_tests', [
            'failures' => [
                time() - 301, time() - 20, time() - 10
            ]
        ]);

        $b = new CircuitBreaker('tests', $c);
        $b->setThreshold(3);
        $this->assertTrue($b->isClosed());
        $this->assertFalse($b->isOpen());
    }

    public function testSuccessClearsFailures()
    {
        $c = new ArrayCache();
        $c->save('circuitbreaker_tests', [
            'failures' => [
                time() - 30, time() - 20, time() - 10
            ]
        ]);

        $b = new CircuitBreaker('tests', $c);
        $b->setThreshold(3);
        $this->assertTrue($b->isOpen());

        $b->success();
        $this->assertFalse($b->isOpen());
    }

    public function testStatePersists()
    {
        $c = new ArrayCache();
        $b = new CircuitBreaker('tests', $c);

        $b->failure();

        $this->assertTrue($c->contains('circuitbreaker_tests'));
        $this->assertCount(1, $c->fetch('circuitbreaker_tests')['failures']);
    }

    public function testForceOpen()
    {
        $c = new ArrayCache();
        $b = new CircuitBreaker('tests', $c);

        $this->assertEquals($b, $b->forceOpen());
        $this->assertTrue($b->isOpen());
        $this->assertFalse($b->isClosed());
    }

    public function testForceClosed()
    {
        $c = new ArrayCache();
        $c->save('circuitbreaker_tests', [
            'failures' => [
                time() - 30, time() - 20, time() - 10
            ]
        ]);
        $b = new CircuitBreaker('tests', $c);

        $this->assertEquals($b, $b->forceClosed());
        $this->assertTrue($b->isClosed());
        $this->assertFalse($b->isOpen());

        $this->assertEquals([], $c->fetch('circuitbreaker_tests')['failures']);
    }

    public function testMangledCacheContents()
    {
        $c = new ArrayCache();
        $c->save('circuitbreaker_tests', 'bad-data');
        $b = new CircuitBreaker('tests', $c);
        $this->assertFalse($b->isOpen());

        $c = new ArrayCache();
        $c->save('circuitbreaker_tests', []);
        $b = new CircuitBreaker('tests', $c);
        $this->assertFalse($b->isOpen());

        $c = new ArrayCache();
        $c->save('circuitbreaker_tests', (object)['failures']);
        $b = new CircuitBreaker('tests', $c);
        $this->assertFalse($b->isOpen());
    }

    public function testWriteCleansInvalidFails()
    {
        $invalidTime = time() - 301;

        $c = new ArrayCache();
        $c->save('circuitbreaker_tests', [
            'failures' => [
                $invalidTime
            ]
        ]);
        $b = new CircuitBreaker('tests', $c);
        $b->failure();
        $this->assertTrue($b->isClosed());

        $this->assertCount(1, $c->fetch('circuitbreaker_tests')['failures']);
        $this->assertFalse(in_array($invalidTime, $c->fetch('circuitbreaker_tests')['failures']));
    }

    public function testOpenEventsDontFireIncorrectly()
    {
        $message = false;

        $c = new ArrayCache();
        $b = new CircuitBreaker('tests', $c);
        $b->setThreshold(5);
        $b->onChange(
            function ($previous, $new) use (&$message) {
                $message = $previous.' -> '.$new;
            }
        );

        $b->failure();
        $b->failure();

        $this->assertFalse($message);
    }

    public function testEventsFireClosedToOpen()
    {
        $message = false;

        $c = new ArrayCache();
        $b = new CircuitBreaker('tests', $c);
        $b->setThreshold(1);
        $b->onChange(
            function ($previous, $new) use (&$message) {
                $message = $previous.' -> '.$new;
            }
        );

        $b->failure();
        $this->assertEquals('closed -> open', $message);
    }

    public function testEventsFireOpenToClosed()
    {
        $message = false;

        $c = new ArrayCache();
        $c->save('circuitbreaker_tests', [
            'failures' => [time()-10]
        ]);

        $b = new CircuitBreaker('tests', $c);
        $b->setThreshold(1);
        $b->onChange(
            function ($previous, $new) use (&$message) {
                $message = $previous.' -> '.$new;
            }
        );

        $b->success();
        $this->assertEquals('open -> closed', $message);
    }
}
