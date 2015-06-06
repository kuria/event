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

    public function testInitialState()
    {
        $emitter = $this->createEventEmitter();

        $this->assertSame(0, $emitter->getListenerCount());
        $this->assertSame(0, $emitter->getListenerCount('foo'));
        $this->assertFalse($emitter->hasListener());
        $this->assertFalse($emitter->hasListener('foo'));

        // all of these should do nothing
        $emitter->emit('foo');
        $emitter->removeListener('foo', 'nonexistent');
        $emitter->clearListeners('foo');
        $emitter->clearListeners();
    }

    public function testOn()
    {
        $emitter = $this->createEventEmitter();

        $listenerA = function () {};
        $listenerB = function () {};

        $emitter->on('foo', $listenerA);
        $emitter->on('bar', $listenerA);
        $emitter->on('bar', $listenerB, 5);

        $this->assertSame(3, $emitter->getListenerCount());
        $this->assertSame(1, $emitter->getListenerCount('foo'));
        $this->assertSame(2, $emitter->getListenerCount('bar'));
        $this->assertSame(0, $emitter->getListenerCount('baz'));

        $this->assertTrue($emitter->hasListener());
        $this->assertTrue($emitter->hasListener('foo'));
        $this->assertTrue($emitter->hasListener('bar'));
        $this->assertFalse($emitter->hasListener('baz'));
    }

    public function testOnce()
    {
        $emitter = $this->createEventEmitter();

        $listenerA = function () {};
        $listenerB = function () {};

        $emitter->once('foo', $listenerA);
        $emitter->once('bar', $listenerA);
        $emitter->once('bar', $listenerB, 5);

        $this->assertSame(3, $emitter->getListenerCount());
        $this->assertSame(1, $emitter->getListenerCount('foo'));
        $this->assertSame(2, $emitter->getListenerCount('bar'));
        $this->assertSame(0, $emitter->getListenerCount('baz'));

        $this->assertTrue($emitter->hasListener());
        $this->assertTrue($emitter->hasListener('foo'));
        $this->assertTrue($emitter->hasListener('bar'));
        $this->assertFalse($emitter->hasListener('baz'));
    }

    public function testRemoveListener()
    {
        $emitter = $this->createEventEmitter();

        $callStatus = array(false, false, false);

        $listenerA = function () use (&$callStatus) {
            $callStatus[0] = true;
        };
        $listenerB = function () use (&$callStatus) {
            $callStatus[1] = true;
        };
        $listenerC = function () use (&$callStatus) {
            $callStatus[2] = true;
        };

        $emitter->on('foo', $listenerA);
        $emitter->on('foo', $listenerA); // duplicate on purpose
        $emitter->on('bar', $listenerA);
        $emitter->on('bar', $listenerB);

        $this->assertTrue($emitter->hasListener());
        $this->assertTrue($emitter->hasListener('foo'));
        $this->assertTrue($emitter->hasListener('bar'));
        $this->assertSame(4, $emitter->getListenerCount());
        $this->assertSame(2, $emitter->getListenerCount('foo'));
        $this->assertSame(2, $emitter->getListenerCount('bar'));

        $emitter->removeListener('foo', $listenerC); // should have no effect

        $this->assertTrue($emitter->hasListener());
        $this->assertTrue($emitter->hasListener('foo'));
        $this->assertTrue($emitter->hasListener('bar'));
        $this->assertSame(4, $emitter->getListenerCount());
        $this->assertSame(2, $emitter->getListenerCount('foo'));
        $this->assertSame(2, $emitter->getListenerCount('bar'));

        $emitter->removeListener('foo', $listenerA);

        $this->assertTrue($emitter->hasListener());
        $this->assertTrue($emitter->hasListener('foo'));
        $this->assertTrue($emitter->hasListener('bar'));
        $this->assertSame(3, $emitter->getListenerCount());
        $this->assertSame(1, $emitter->getListenerCount('foo'));
        $this->assertSame(2, $emitter->getListenerCount('bar'));

        $emitter->removeListener('foo', $listenerA);

        $this->assertTrue($emitter->hasListener());
        $this->assertFalse($emitter->hasListener('foo'));
        $this->assertTrue($emitter->hasListener('bar'));
        $this->assertSame(2, $emitter->getListenerCount());
        $this->assertSame(0, $emitter->getListenerCount('foo'));
        $this->assertSame(2, $emitter->getListenerCount('bar'));

        $emitter->removeListener('bar', $listenerB);

        $this->assertTrue($emitter->hasListener());
        $this->assertFalse($emitter->hasListener('foo'));
        $this->assertTrue($emitter->hasListener('bar'));
        $this->assertSame(1, $emitter->getListenerCount());
        $this->assertSame(0, $emitter->getListenerCount('foo'));
        $this->assertSame(1, $emitter->getListenerCount('bar'));

        $emitter->removeListener('bar', $listenerA);

        $this->assertFalse($emitter->hasListener());
        $this->assertFalse($emitter->hasListener('foo'));
        $this->assertFalse($emitter->hasListener('bar'));
        $this->assertSame(0, $emitter->getListenerCount());
        $this->assertSame(0, $emitter->getListenerCount('foo'));
        $this->assertSame(0, $emitter->getListenerCount('bar'));

        $emitter->emit('foo');
        $emitter->emitArray('foo', array());
        $emitter->emit('bar');
        $emitter->emitArray('bar', array());

        $this->assertSame(array(false, false, false), $callStatus);
    }

    public function testClearingListeners()
    {
        $emitter = $this->createEventEmitter();

        $listenerA = function () {};
        $listenerB = function () {};
        $listenerC = function () {};

        $emitter->on('foo', $listenerA);
        $emitter->on('foo', $listenerA); // duplicate on purpose
        $emitter->on('bar', $listenerA);
        $emitter->on('bar', $listenerB);
        $emitter->on('bar', $listenerC);
        $emitter->on('baz', $listenerB);
        $emitter->on('baz', $listenerC);

        $this->assertTrue($emitter->hasListener());
        $this->assertTrue($emitter->hasListener('foo'));
        $this->assertTrue($emitter->hasListener('bar'));
        $this->assertTrue($emitter->hasListener('baz'));
        $this->assertSame(7, $emitter->getListenerCount());
        $this->assertSame(2, $emitter->getListenerCount('foo'));
        $this->assertSame(3, $emitter->getListenerCount('bar'));
        $this->assertSame(2, $emitter->getListenerCount('baz'));

        $emitter->clearListeners('bar');

        $this->assertTrue($emitter->hasListener());
        $this->assertTrue($emitter->hasListener('foo'));
        $this->assertFalse($emitter->hasListener('bar'));
        $this->assertTrue($emitter->hasListener('baz'));
        $this->assertSame(4, $emitter->getListenerCount());
        $this->assertSame(2, $emitter->getListenerCount('foo'));
        $this->assertSame(0, $emitter->getListenerCount('bar'));
        $this->assertSame(2, $emitter->getListenerCount('baz'));

        $emitter->clearListeners();

        $this->assertFalse($emitter->hasListener());
        $this->assertFalse($emitter->hasListener('foo'));
        $this->assertFalse($emitter->hasListener('bar'));
        $this->assertFalse($emitter->hasListener('baz'));
        $this->assertSame(0, $emitter->getListenerCount());
        $this->assertSame(0, $emitter->getListenerCount('foo'));
        $this->assertSame(0, $emitter->getListenerCount('bar'));
        $this->assertSame(0, $emitter->getListenerCount('baz'));
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

        $listenerACallCount = 0;
        $listenerBCallCount = 0;

        $listenerA = function ($arg1, $arg2) use ($that, &$listenerACallCount) {
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerACallCount;
        };

        $listenerB = function ($arg1, $arg2) use ($that, &$listenerBCallCount) {
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerBCallCount;
        };

        $emitter->on('foo', $listenerA);
        $emitter->on('foo', $listenerB);

        // emit the event twice
        for ($i = 0; $i < 2; ++$i) {
            $this->assertTrue($emitMethodCaller($emitter, 'foo', array('hello', 123)));
        }

        // emitting events that have no listeners should do nothing
        $this->assertFalse($emitMethodCaller($emitter, 'bar'));

        // the listeners should be called each time
        $this->assertSame(2, $listenerACallCount);
        $this->assertSame(2, $listenerBCallCount);
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

        $listenerACallCount = 0;
        $listenerBCallCount = 0;

        $listenerA = function ($arg1, $arg2) use ($that, &$listenerACallCount) {
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerACallCount;
        };

        $listenerB = function ($arg1, $arg2) use ($that, &$listenerBCallCount) {
            $that->assertSame('hello', $arg1);
            $that->assertSame(123, $arg2);

            ++$listenerBCallCount;
        };

        $emitter->once('foo', $listenerA);
        $emitter->once('foo', $listenerB);

        // emit the event twice
        $this->assertTrue($emitMethodCaller($emitter, 'foo', array('hello', 123)));
        $this->assertFalse($emitMethodCaller($emitter, 'foo', array('hello', 123)));

        // the listeners should be called just once
        $this->assertSame(1, $listenerACallCount);
        $this->assertSame(1, $listenerBCallCount);
    }

    public function testEmitArrayReference()
    {
        $emitter = $this->createEventEmitter();

        $referencedVariable = 'initial';

        $listener = function (&$arg) {
            $arg = 'changed';
        };

        $emitter->on('foo', $listener);

        $emitter->emitArray('foo', array(&$referencedVariable));

        $this->assertSame('changed', $referencedVariable);
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

        $callStatus = array(false, false, false);

        $listenerA = function () use ($that, &$callStatus) {
            $that->assertSame(array(false, true, true), $callStatus);

            $callStatus[0] = true;
        };

        $listenerB = function () use ($that, &$callStatus) {
            $that->assertSame(array(false, false, false), $callStatus);

            $callStatus[1] = true;
        };

        $listenerC = function () use ($that, &$callStatus) {
            $that->assertSame(array(false, true, false), $callStatus);

            $callStatus[2] = true;
        };

        // priority order: B, C, A
        $emitter->on('foo', $listenerA, -1);
        $emitter->on('foo', $listenerB, 1);
        $emitter->on('foo', $listenerC);

        $emitMethodCaller($emitter, 'foo');

        $this->assertSame(array(true, true, true), $callStatus);
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

        $emitter->on('foo', $listenerA, 1);
        $emitter->on('foo', $listenerB, 0);

        $emitMethodCaller($emitter, 'foo');

        $this->assertTrue($listenerACalled);
        $this->assertFalse($listenerBCalled);
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
}
