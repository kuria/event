<?php

namespace Kuria\Event;

class EventEmitterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return EventEmitterInterface
     */
    protected function createEventEmitter()
    {
        return new EventEmitter();
    }

    public function testHasListeners()
    {
        $emitter = $this->createEventEmitter();

        // check with no listeners
        $this->assertFalse($emitter->hasListeners());
        $this->assertFalse($emitter->hasListeners('foo'));
        $this->assertFalse($emitter->hasListeners('foo', false));
        $this->assertFalse($emitter->hasListeners(null, false));

        // check with some listener
        $emitter->on('foo', 'test_a');

        $this->assertTrue($emitter->hasListeners());
        $this->assertTrue($emitter->hasListeners('foo'));
        $this->assertTrue($emitter->hasListeners('foo', false));
        $this->assertTrue($emitter->hasListeners(null, false));

        // check non-registered event without global listeners
        $this->assertFalse($emitter->hasListeners('bar'));

        // check non-registered event with global listeners
        $emitter->on('*', 'test_b');
        
        $this->assertTrue($emitter->hasListeners('bar'));
        $this->assertFalse($emitter->hasListeners('bar', false));
    }

    public function testGetListeners()
    {
        $emitter = $this->createEventEmitter();

        $this->assertSame(array(), $emitter->getListeners());
        $this->assertSame(array(), $emitter->getListeners('nonexistent'));

        $emitter
            ->on('foo', 'test_a', 0)
            ->on('foo', 'test_b', 5)
            ->on('bar', 'test_c', 10)
            ->on('bar', 'test_d', 15)
        ;

        $this->assertSame(
            array('test_b', 'test_a'),
            $emitter->getListeners('foo')
        );

        $this->assertSame(
            array(
                'foo' => array('test_b', 'test_a'),
                'bar' => array('test_d', 'test_c'),
            ),
            $emitter->getListeners()
        );
    }

    public function testOn()
    {
        $emitter = $this->createEventEmitter();

        $listenerA = function () {};
        $listenerB = function () {};

        $emitter->on('foo', $listenerA);
        $emitter->on('bar', $listenerA);
        $emitter->on('bar', $listenerB, 5);

        $this->assertListeners($emitter, array(
            'foo' => array($listenerA),
            'bar' => array($listenerB, $listenerA),
        ));
    }

    public function testOnce()
    {
        $emitter = $this->createEventEmitter();

        $listenerA = function () {};
        $listenerB = function () {};

        $emitter->once('foo', $listenerA);
        $emitter->once('bar', $listenerA);
        $emitter->once('bar', $listenerB, 5);

        $this->assertListeners($emitter, array(
            'foo' => array($listenerA),
            'bar' => array($listenerB, $listenerA),
        ));
    }

    public function testRemoveListener()
    {
        $emitter = $this->createEventEmitter();

        $callStatus = array(
            'a' => false,
            'b' => false,
            'ag' => false,
            'bg' => false,
        );

        $listenerA = function () use (&$callStatus) {
            $callStatus['a'] = true;
        };
        $listenerB = function () use (&$callStatus) {
            $callStatus['b'] = true;
        };
        $globalListenerA = function () use (&$callStatus) {
            $callStatus['ga'] = true;
        };
        $globalListenerB = function () use (&$callStatus) {
            $callStatus['gb'] = true;
        };

        $emitter
            ->on('foo', $listenerA)
            ->on('foo', $listenerA) // duplicate on purpose
            ->on('bar', $listenerA, 1)
            ->on('bar', $listenerB, 0)
            ->on('*', $globalListenerA, 0)
            ->on('*', $globalListenerB, 1)
        ;

        $this->assertListeners($emitter, array(
            '*' => array($globalListenerB, $globalListenerA),
            'foo' => array($listenerA, $listenerA),
            'bar' => array($listenerA, $listenerB),
        ));

        $emitter->removeListener('foo', $globalListenerA); // should have no effect

        $this->assertListeners($emitter, array(
            '*' => array($globalListenerB, $globalListenerA),
            'foo' => array($listenerA, $listenerA),
            'bar' => array($listenerA, $listenerB),
        ));

        $emitter->removeListener('foo', $listenerA);

        $this->assertListeners($emitter, array(
            'foo' => array($listenerA),
            'bar' => array($listenerA, $listenerB),
        ));

        $emitter->removeListener('foo', $listenerA);

        $this->assertListeners($emitter, array(
            '*' => array($globalListenerB, $globalListenerA),
            'foo' => array(),
            'bar' => array($listenerA, $listenerB),
        ));

        $emitter->removeListener('bar', $listenerB);

        $this->assertListeners($emitter, array(
            '*' => array($globalListenerB, $globalListenerA),
            'bar' => array($listenerA),
        ));

        $emitter->removeListener('bar', $listenerA);

        $this->assertListeners($emitter, array(
            '*' => array($globalListenerB, $globalListenerA),
        ));

        $emitter->removeListener('*', $globalListenerA);

        $this->assertListeners($emitter, array(
            '*' => array($globalListenerB),
        ));

        $emitter->removeListener('*', $globalListenerB);

        $this->assertListeners($emitter, array());

        $emitter->emit('foo');
        $emitter->emitArray('foo', array());
        $emitter->emit('bar');
        $emitter->emitArray('bar', array());

        $this->assertSame(array('a' => false, 'b' => false, 'ag' => false, 'bg' => false), $callStatus);
    }

    public function testClearingListeners()
    {
        $emitter = $this->createEventEmitter();

        $listenerA = function () {};
        $listenerB = function () {};
        $listenerC = function () {};

        $emitter
            ->on('foo', $listenerA)
            ->on('foo', $listenerA) // duplicate on purpose
            ->on('bar', $listenerA, 3)
            ->on('bar', $listenerB, 2)
            ->on('bar', $listenerC, 1)
            ->on('baz', $listenerB, 2)
            ->on('baz', $listenerC, 1)
        ;

        $this->assertListeners($emitter, array(
            'foo' => array($listenerA, $listenerA),
            'bar' => array($listenerA, $listenerB, $listenerC),
            'baz' => array($listenerB, $listenerC),
        ));

        $emitter->clearListeners('bar');

        $this->assertListeners($emitter, array(
            'foo' => array($listenerA, $listenerA),
            'bar' => array(),
            'baz' => array($listenerB, $listenerC),
        ));

        $emitter->clearListeners();

        $this->assertListeners($emitter, array(
            'foo' => array(),
            'bar' => array(),
            'baz' => array(),
        ));
    }

    public function testSubscribe()
    {
        $emitter = $this->createEventEmitter();

        $subscriber = $this->getMock(__NAMESPACE__ . '\EventSubscriberInterface');

        $subscriber
            ->expects($this->once())
            ->method('subscribeTo')
        ;

        $emitter->subscribe($subscriber);
    }

    public function testUnsubscribe()
    {
        $emitter = $this->createEventEmitter();

        $subscriber = $this->getMock(__NAMESPACE__ . '\EventSubscriberInterface');

        $subscriber
            ->expects($this->once())
            ->method('unsubscribeFrom')
        ;

        $emitter->unsubscribe($subscriber);
    }

    public function testEmit()
    {
        $this->doTestEmit($this->createEmitMethodCaller());
    }

    public function testEmitArray()
    {
        $this->doTestEmit($this->createEmitArrayMethodCaller());
    }

    /**
     * @param callable $emitMethodCaller
     */
    public function doTestEmit($emitMethodCaller)
    {
        $that = $this;

        $emitter = $this->createEventEmitter();

        $listenerCallCounters = array(
            'a' => 0,
            'b' => 0,
            'ga' => 0,
            'gb' => 0,
        );

        $listenerA = function ($arg1, $arg2) use ($that, &$listenerCallCounters) {
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerCallCounters['a'];
        };

        $listenerB = function ($arg1, $arg2) use ($that, &$listenerCallCounters) {
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerCallCounters['b'];
        };

        $globalListenerA = function ($event, $arg1, $arg2) use ($that, &$listenerCallCounters) {
            $that->assertTrue(in_array($event, array('foo', 'bar'), true));
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerCallCounters['ga'];
        };

        $globalListenerB = function ($event, $arg1, $arg2) use ($that, &$listenerCallCounters) {
            $that->assertTrue(in_array($event, array('foo', 'bar'), true));
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerCallCounters['gb'];
        };

        $emitter
            ->on('foo', $listenerA)
            ->on('foo', $listenerB)
            ->on('*', $globalListenerA)
            ->on('*', $globalListenerB)
        ;

        // emit the event twice
        for ($i = 0; $i < 2; ++$i) {
            $emitMethodCaller($emitter, 'foo', array('hello', 123));
        }

        // emitting events that have no specific listeners should call the global ones
        $emitMethodCaller($emitter, 'bar', array('hello', 123));

        // the listeners should be called twice
        $this->assertSame(2, $listenerCallCounters['a']);
        $this->assertSame(2, $listenerCallCounters['b']);

        // global listeners should be called twice + once for the "bar" event
        $this->assertSame(3, $listenerCallCounters['ga']);
        $this->assertSame(3, $listenerCallCounters['gb']);
    }
    
    public function testEmitWithOnceListeners()
    {
        $this->doTestEmitWithOnceListeners($this->createEmitMethodCaller());
    }
    
    public function testEmitArrayWithOnceListeners()
    {
        $this->doTestEmitWithOnceListeners($this->createEmitArrayMethodCaller());
    }

    /**
     * @param callable $emitMethodCaller
     */
    public function doTestEmitWithOnceListeners($emitMethodCaller)
    {
        $that = $this;

        $emitter = $this->createEventEmitter();

        $listenerCallCounters = array(
            'a' => 0,
            'b' => 0,
            'ga' => 0,
            'gb' => 0,
        );

        $listenerA = function ($arg1, $arg2) use ($that, &$listenerCallCounters) {
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerCallCounters['a'];
        };

        $listenerB = function ($arg1, $arg2) use ($that, &$listenerCallCounters) {
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerCallCounters['b'];
        };

        $globalListenerA = function ($event, $arg1, $arg2) use ($that, &$listenerCallCounters) {
            $that->assertSame('foo', $event);
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerCallCounters['ga'];
        };

        $globalListenerB = function ($event, $arg1, $arg2) use ($that, &$listenerCallCounters) {
            $that->assertSame('foo', $event);
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);
            
            ++$listenerCallCounters['gb'];
        };

        $emitter
            ->once('foo', $listenerA)
            ->once('foo', $listenerB)
            ->once('*', $globalListenerA)
            ->once('*', $globalListenerB)
        ;

        // emit the event twice
        $emitMethodCaller($emitter, 'foo', array('hello', 123));
        $emitMethodCaller($emitter, 'foo', array('hello', 123));

        // the listeners should be called just once
        $this->assertSame(array('a' => 1, 'b' => 1, 'ga' => 1, 'gb' => 1), $listenerCallCounters);
    }

    public function testEmitArrayReference()
    {
        $emitter = $this->createEventEmitter();

        $referencedVariable = 0;

        $listener = function (&$arg) {
            ++$arg;
        };

        $globalListener = function ($event, &$arg) {
            ++$arg;
        };

        $emitter
            ->on('foo', $listener)
            ->on('*', $globalListener)
        ;

        $emitter->emitArray('foo', array(&$referencedVariable));

        $this->assertSame(2, $referencedVariable);
    }

    public function testEmitPriority()
    {
        $this->doTestEmitPriority($this->createEmitMethodCaller());
    }

    public function testEmitArrayPriority()
    {
        $this->doTestEmitPriority($this->createEmitArrayMethodCaller());
    }
    
    /**
     * @param callable $emitMethodCaller
     */
    private function doTestEmitPriority($emitMethodCaller)
    {
        $that = $this;

        $emitter = $this->createEventEmitter();

        $callStatus = array(
            'a' => false,
            'b' => false,
            'c' => false,
            'ga' => false,
            'gb' => false,
            'gc' => false,
        );

        $listenerA = function () use ($that, &$callStatus) {
            $that->assertSame(
                array('a' => false, 'b' => true, 'c' => true, 'ga' => true, 'gb' => true, 'gc' => true),
                $callStatus
            );

            $callStatus['a'] = true;
        };

        $listenerB = function () use ($that, &$callStatus) {
            $that->assertSame(
                array('a' => false, 'b' => false, 'c' => false, 'ga' => true, 'gb' => true, 'gc' => true),
                $callStatus
            );

            $callStatus['b'] = true;
        };

        $listenerC = function () use ($that, &$callStatus) {
            $that->assertSame(
                array('a' => false, 'b' => true, 'c' => false, 'ga' => true, 'gb' => true, 'gc' => true),
                $callStatus
            );

            $callStatus['c'] = true;
        };

        $globalListenerA = function () use ($that, &$callStatus) {
            $that->assertSame(
                array('a' => false, 'b' => false, 'c' => false, 'ga' => false, 'gb' => true, 'gc' => true),
                $callStatus
            );

            $callStatus['ga'] = true;
        };

        $globalListenerB = function () use ($that, &$callStatus) {
            $that->assertSame(
                array('a' => false, 'b' => false, 'c' => false, 'ga' => false, 'gb' => false, 'gc' => false),
                $callStatus
            );

            $callStatus['gb'] = true;
        };

        $globalListenerC = function () use ($that, &$callStatus) {
            $that->assertSame(
                array('a' => false, 'b' => false, 'c' => false, 'ga' => false, 'gb' => true, 'gc' => false),
                $callStatus
            );

            $callStatus['gc'] = true;
        };

        // priority order: B, C, A
        $emitter
            ->on('foo', $listenerA, -1)
            ->on('foo', $listenerB, 1)
            ->on('foo', $listenerC)
            ->on('*', $globalListenerA, -1)
            ->on('*', $globalListenerB, 1)
            ->on('*', $globalListenerC)
        ;

        $emitMethodCaller($emitter, 'foo');

        $this->assertSame(
            array('a' => true, 'b' => true, 'c' => true, 'ga' => true, 'gb' => true, 'gc' => true),
            $callStatus
        );
    }

    public function testEmitStoppingPropagation()
    {
        $this->doTestStoppingPropagation($this->createEmitMethodCaller());
    }

    public function testEmitArrayStoppingPropagation()
    {
        $this->doTestStoppingPropagation($this->createEmitArrayMethodCaller());
    }

    /**
     * @param callable $emitMethodCaller
     */
    private function doTestStoppingPropagation($emitMethodCaller)
    {
        $emitter = $this->createEventEmitter();

        $listenerACalled = false;
        $listenerBCalled = false;

        $listenerA = function () use (&$listenerACalled) {
            $listenerACalled = true;

            return false;
        };

        $listenerB = function () use (&$listenerBCalled) {
            $listenerBCalled = true;
        };

        $emitter
            ->on('foo', $listenerA, 1)
            ->on('foo', $listenerB, 0)
        ;

        $emitMethodCaller($emitter, 'foo');

        $this->assertTrue($listenerACalled);
        $this->assertFalse($listenerBCalled);
    }
    
    public function testEmitStoppingPropagationGlobal()
    {
        $this->doTestStoppingPropagationGlobal($this->createEmitMethodCaller());
    }

    public function testEmitArrayStoppingPropagationGlobal()
    {
        $this->doTestStoppingPropagationGlobal($this->createEmitArrayMethodCaller());
    }

    /**
     * @param callable $emitMethodCaller
     */
    private function doTestStoppingPropagationGlobal($emitMethodCaller)
    {
        $emitter = $this->createEventEmitter();

        $callStatus = array(
            'a' => false,
            'ga' => false,
            'gb' => false,
        );

        $listenerA = function () use (&$callStatus) {
            $callStatus['a'] = true;
        };

        $globalListenerA = function () use (&$callStatus) {
            $callStatus['ga'] = true;

            return false;
        };

        $globalListenerB = function () use (&$callStatus) {
            $callStatus['gb'] = true;
        };

        $emitter
            ->on('foo', $listenerA)
            ->on('*', $globalListenerA, 1)
            ->on('*', $globalListenerB, 0)
        ;

        $emitMethodCaller($emitter, 'foo');

        $this->assertSame(
            array('a' => false, 'ga' => true, 'gb' => false),
            $callStatus
        );
    }

    /**
     * @return callable
     */
    private function createEmitMethodCaller()
    {
        return function (EventEmitterInterface $emitter, $event, array $args = array()) {
            return call_user_func_array(array($emitter, 'emit'), array_merge(array($event), $args));
        };
    }

    /**
     * @return callable
     */
    private function createEmitArrayMethodCaller()
    {
        return function (EventEmitterInterface $emitter, $event, array $args = array()) {
            return $emitter->emitArray($event, $args);
        };
    }

    /**
     * Assert listener map
     *
     * @param EventEmitterInterface $emitter
     * @param array                 $expected
     */
    private function assertListeners(EventEmitterInterface $emitter, array $expected)
    {
        $shouldHaveSomeListeners = false;
        $actualListeners = $emitter->getListeners();

        foreach ($expected as $event => $expectedListeners) {
            if ($expectedListeners) {
                $shouldHaveSomeListeners = true;

                $this->assertTrue($emitter->hasListeners($event, false));
                $this->assertArrayHasKey($event, $actualListeners);
                $this->assertSame($expectedListeners, $actualListeners[$event]);
                $this->assertSame($expectedListeners, $emitter->getListeners($event));

            } else {
                $this->assertFalse($emitter->hasListeners($event, false));
                $this->assertArrayNotHasKey($event, $actualListeners);
                $this->assertSame(array(), $emitter->getListeners($event));
            }
        }

        $this->assertSame($shouldHaveSomeListeners, $emitter->hasListeners());
    }
}
