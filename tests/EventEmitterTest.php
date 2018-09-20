<?php declare(strict_types=1);

namespace Kuria\Event;

use Kuria\DevMeta\Test;

class EventEmitterTest extends Test
{
    /** @var EventEmitterInterface */
    private $emitter;

    protected function setUp()
    {
        TestCallbackWrapper::clear();

        $this->emitter = new EventEmitter();
    }

    function testShouldCheckIfListenersExist()
    {
        // check without any listeners
        $this->assertFalse($this->emitter->hasListeners());
        $this->assertFalse($this->emitter->hasListeners('foo'));

        // check with "foo" listener
        $this->emitter->on('foo', $this->createTestCallback('foo'));

        $this->assertTrue($this->emitter->hasListeners());
        $this->assertTrue($this->emitter->hasListeners('foo'));

        // check event without any listeners
        $this->assertFalse($this->emitter->hasListeners('bar'));

        // check event with a global listener
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $this->createTestCallback('global'));

        $this->assertTrue($this->emitter->hasListeners('bar'));
    }

    function testShouldGetListeners()
    {
        $this->assertSame([], $this->emitter->getListeners());
        $this->assertSame([], $this->emitter->getListeners('nonexistent'));

        $listenerA = $this->createTestListener('foo', 'a', null, 1);
        $listenerB = $this->createTestListener('foo', 'b');
        $listenerC = $this->createTestListener('bar', 'c', null, 1);
        $listenerD = $this->createTestListener('bar', 'd');

        $this->emitter->addListener($listenerD);
        $this->emitter->addListener($listenerC);
        $this->emitter->addListener($listenerB);
        $this->emitter->addListener($listenerA);

        $this->assertSame(
            [$listenerA, $listenerB],
            $this->emitter->getListeners('foo')
        );

        $this->assertSame(
            [$listenerC, $listenerD],
            $this->emitter->getListeners('bar')
        );

        $this->assertSame(
            [
                'bar' => [$listenerC, $listenerD],
                'foo' => [$listenerA,$listenerB],
            ],
            $this->emitter->getListeners()
        );
    }

    function testShouldRegisterCallback()
    {
        $callbackA = $this->createTestCallback('a');
        $callbackB = $this->createTestCallback('b');

        $this->emitter->on('foo', $callbackA);
        $this->emitter->on('bar', $callbackA);
        $this->emitter->on('bar', $callbackB, 5);

        $this->assertCallbacks([
            'foo' => [$callbackA],
            'bar' => [$callbackB, $callbackA],
        ]);
    }

    function testShouldUnregisterCallback()
    {
        $callStatus = [
            'a' => false,
            'b' => false,
            'global_a' => false,
            'global_b' => false,
        ];

        $callbackA = $this->createTestCallback('a', function () use (&$callStatus) {
            $callStatus['a'] = true;
        });
        $callbackB = $this->createTestCallback('b', function () use (&$callStatus) {
            $callStatus['b'] = true;
        });
        $globalCallbackA = $this->createTestCallback('global_a', function () use (&$callStatus) {
            $callStatus['global_a'] = true;
        });
        $globalCallbackB = $this->createTestCallback('global_b', function () use (&$callStatus) {
            $callStatus['global_b'] = true;
        });
        $unusedCallback = $this->createTestCallback('unused');

        $this->emitter->on('foo', $callbackA);
        $this->emitter->on('foo', $callbackA); // duplicate on purpose
        $this->emitter->on('bar', $callbackA, 1);
        $this->emitter->on('bar', $callbackB, 0);
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $globalCallbackA, 0);
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $globalCallbackB, 1);

        $this->assertCallbacks([
            'foo' => [$callbackA, $callbackA],
            'bar' => [$callbackA, $callbackB],
            EventEmitterInterface::ANY_EVENT => [$globalCallbackB, $globalCallbackA],
        ]);

        $this->assertFalse($this->emitter->off('foo', $globalCallbackA)); // should have no effect
        $this->assertFalse($this->emitter->off('unused', $unusedCallback)); // should have no effect

        $this->assertCallbacks([
            'foo' => [$callbackA, $callbackA],
            'bar' => [$callbackA, $callbackB],
            EventEmitterInterface::ANY_EVENT => [$globalCallbackB, $globalCallbackA],
        ]);

        $this->assertTrue($this->emitter->off('foo', $callbackA));

        $this->assertCallbacks([
            'foo' => [$callbackA],
            'bar' => [$callbackA, $callbackB],
            EventEmitterInterface::ANY_EVENT => [$globalCallbackB, $globalCallbackA],
        ]);

        $this->assertTrue($this->emitter->off('foo', $callbackA));

        $this->assertCallbacks([
            'foo' => [],
            'bar' => [$callbackA, $callbackB],
            EventEmitterInterface::ANY_EVENT => [$globalCallbackB, $globalCallbackA],
        ]);

        $this->assertTrue($this->emitter->off('bar', $callbackB));

        $this->assertCallbacks([
            'foo' => [],
            'bar' => [$callbackA],
            EventEmitterInterface::ANY_EVENT => [$globalCallbackB, $globalCallbackA],
        ]);

        $this->assertTrue($this->emitter->off('bar', $callbackA));

        $this->assertCallbacks([
            'foo' => [],
            'bar' => [],
            EventEmitterInterface::ANY_EVENT => [$globalCallbackB, $globalCallbackA],
        ]);

        $this->assertTrue($this->emitter->off(EventEmitterInterface::ANY_EVENT, $globalCallbackA));

        $this->assertCallbacks([
            'foo' => [],
            'bar' => [],
            EventEmitterInterface::ANY_EVENT => [$globalCallbackB],
        ]);

        $this->assertTrue($this->emitter->off(EventEmitterInterface::ANY_EVENT, $globalCallbackB));

        $this->assertCallbacks([
            'foo' => [],
            'bar' => [],
            EventEmitterInterface::ANY_EVENT => [],
        ]);

        $this->emitter->emit('foo');
        $this->emitter->emit('bar');

        $this->assertSame(['a' => false, 'b' => false, 'global_a' => false, 'global_b' => false], $callStatus);
    }

    function testShouldAddListener()
    {
        $fooListener = $this->createTestListener('foo');
        $barListenerA = $this->createTestListener('bar', 'b');
        $barListenerB = $this->createTestListener('bar', 'c');

        $this->emitter->addListener($fooListener);
        $this->emitter->addListener($fooListener); // duplicate on purpose
        $this->emitter->addListener($barListenerA);
        $this->emitter->addListener($barListenerB);

        $this->assertListeners([
            'foo' => [$fooListener, $fooListener],
            'bar' => [$barListenerA, $barListenerB],
        ]);
    }

    function testShouldRemoveListener()
    {
        $expectedCallStatus = [
            'foo_a' => false,
            'bar_a' => false,
            'bar_b' => false,
            'baz' => false,
            'global_a' => false,
            'global-b' => false,
        ];

        $actualCallStatus = $expectedCallStatus;

        $fooListenerA = $this->createTestListener('foo', 'a', function () use (&$actualCallStatus) {
            $actualCallStatus['foo_a'] = true;
        });
        $barListenerA = $this->createTestListener('bar', 'a', function () use (&$actualCallStatus) {
            $actualCallStatus['bar_a'] = true;
        });
        $barListenerB = $this->createTestListener('bar', 'b', function () use (&$actualCallStatus) {
            $actualCallStatus['bar_b'] = true;
        });
        $bazListener = $this->createTestListener('baz', null, function () use (&$actualCallStatus) {
            $actualCallStatus['baz'] = true;
        });
        $globalListenerA = $this->createTestListener(EventEmitterInterface::ANY_EVENT, 'global_a', function () use (&$actualCallStatus) {
            $actualCallStatus['global_a'] = true;
        });
        $globalListenerB = $this->createTestListener(EventEmitterInterface::ANY_EVENT, 'global_b', function () use (&$actualCallStatus) {
            $actualCallStatus['global_b'] = true;
        }, 1);
        $unusedListener = $this->createTestListener('unused');

        $this->emitter->addListener($fooListenerA);
        $this->emitter->addListener($fooListenerA); // duplicate on purpose
        $this->emitter->addListener($barListenerA);
        $this->emitter->addListener($barListenerB);
        $this->emitter->addListener($bazListener);
        $this->emitter->addListener($globalListenerA);
        $this->emitter->addListener($globalListenerB);

        $this->assertListeners([
            'foo' => [$fooListenerA, $fooListenerA],
            'bar' => [$barListenerA, $barListenerB],
            'baz' => [$bazListener],
            EventEmitterInterface::ANY_EVENT => [$globalListenerB, $globalListenerA],
        ]);

        $this->assertFalse($this->emitter->removeListener($unusedListener)); // should have no effect

        $this->assertListeners([
            'foo' => [$fooListenerA, $fooListenerA],
            'bar' => [$barListenerA, $barListenerB],
            'baz' => [$bazListener],
            EventEmitterInterface::ANY_EVENT => [$globalListenerB, $globalListenerA],
        ]);

        $this->assertTrue($this->emitter->removeListener($fooListenerA));

        $this->assertListeners([
            'foo' => [$fooListenerA],
            'bar' => [$barListenerA, $barListenerB],
            'baz' => [$bazListener],
            EventEmitterInterface::ANY_EVENT => [$globalListenerB, $globalListenerA],
        ]);

        $this->assertTrue($this->emitter->removeListener($fooListenerA));

        $this->assertListeners([
            'foo' => [],
            'bar' => [$barListenerA, $barListenerB],
            'baz' => [$bazListener],
            EventEmitterInterface::ANY_EVENT => [$globalListenerB, $globalListenerA],
        ]);

        $this->assertTrue($this->emitter->removeListener($barListenerA));

        $this->assertListeners([
            'foo' => [],
            'bar' => [$barListenerB],
            'baz' => [$bazListener],
            EventEmitterInterface::ANY_EVENT => [$globalListenerB, $globalListenerA],
        ]);

        $this->assertTrue($this->emitter->removeListener($barListenerB));

        $this->assertListeners([
            'foo' => [],
            'bar' => [],
            'baz' => [$bazListener],
            EventEmitterInterface::ANY_EVENT => [$globalListenerB, $globalListenerA],
        ]);

        $this->assertTrue($this->emitter->removeListener($bazListener));

        $this->assertListeners([
            'foo' => [],
            'bar' => [],
            'baz' => [],
            EventEmitterInterface::ANY_EVENT => [$globalListenerB, $globalListenerA],
        ]);

        $this->assertTrue($this->emitter->removeListener($globalListenerA));

        $this->assertListeners([
            'foo' => [],
            'bar' => [],
            'baz' => [],
            EventEmitterInterface::ANY_EVENT => [$globalListenerB],
        ]);

        $this->assertTrue($this->emitter->removeListener($globalListenerB));

        $this->assertListeners([
            'foo' => [],
            'bar' => [],
            'baz' => [],
            EventEmitterInterface::ANY_EVENT => [],
        ]);

        $this->emitter->emit('foo');
        $this->emitter->emit('bar');
        $this->emitter->emit('baz');

        $this->assertSame($expectedCallStatus, $actualCallStatus);
    }

    function testShouldClearListeners()
    {
        $callbackA = $this->createTestCallback('a');
        $callbackB = $this->createTestCallback('b');
        $callbackC = $this->createTestCallback('c');

        $this->emitter->on('foo', $callbackA);
        $this->emitter->on('foo', $callbackA); // duplicate on purpose
        $this->emitter->on('bar', $callbackA, 3);
        $this->emitter->on('bar', $callbackB, 2);
        $this->emitter->on('bar', $callbackC, 1);
        $this->emitter->on('baz', $callbackB, 2);
        $this->emitter->on('baz', $callbackC, 1);

        $this->assertCallbacks([
            'foo' => [$callbackA, $callbackA],
            'bar' => [$callbackA, $callbackB, $callbackC],
            'baz' => [$callbackB, $callbackC],
        ]);

        $this->emitter->clearListeners('bar');

        $this->assertCallbacks([
            'foo' => [$callbackA, $callbackA],
            'bar' => [],
            'baz' => [$callbackB, $callbackC],
        ]);

        $this->emitter->clearListeners();

        $this->assertCallbacks([
            'foo' => [],
            'bar' => [],
            'baz' => [],
        ]);
    }

    function testShouldEmit()
    {
        $callbackCallCounters = [
            'a' => 0,
            'a2' => 0,
            'b' => 0,
            'global_a' => 0,
            'global_a2' => 0,
            'global_b' => 0,
        ];

        $callbackA = $this->createTestCallback('a', function ($arg1, $arg2) use (&$callbackCallCounters) {
            $this->assertSame('hello', $arg1);
            $this->assertSame(123, $arg2);

            ++$callbackCallCounters['a'];
        });

        $callbackARemoved = $this->createTestCallback('a2', function ($arg1, $arg2) use (&$callbackCallCounters) {
            $this->assertSame('hello', $arg1);
            $this->assertSame(123, $arg2);

            ++$callbackCallCounters['a2'];
        });

        $callbackB = $this->createTestCallback('b', function ($arg1, $arg2) use (&$callbackCallCounters) {
            $this->assertSame('hello', $arg1);
            $this->assertSame(123, $arg2);

            ++$callbackCallCounters['b'];
        });

        $globalCallbackA = $this->createTestCallback('global_a', function ($event, $arg1, $arg2) use (&$callbackCallCounters) {
            $this->assertContains($event, ['foo', 'bar']);
            $this->assertSame('hello', $arg1);
            $this->assertSame(123, $arg2);

            ++$callbackCallCounters['global_a'];
        });

        $globalCallbackARemoved = $this->createTestCallback('global_a2', function ($event, $arg1, $arg2) use (&$callbackCallCounters) {
            $this->assertContains($event, ['foo', 'bar']);
            $this->assertSame('hello', $arg1);
            $this->assertSame(123, $arg2);

            ++$callbackCallCounters['global_a2'];
        });

        $globalCallbackB = $this->createTestCallback('global_b', function ($event, $arg1, $arg2) use (&$callbackCallCounters) {
            $this->assertContains($event, ['foo', 'bar']);
            $this->assertSame('hello', $arg1);
            $this->assertSame(123, $arg2);

            ++$callbackCallCounters['global_b'];
        });

        $this->emitter->on('foo', $callbackA);
        $this->emitter->on('foo', $callbackARemoved, 123);
        $this->emitter->off('foo', $callbackARemoved);
        $this->emitter->on('foo', $callbackB);
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $globalCallbackA);
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $globalCallbackARemoved, 456);
        $this->emitter->off(EventEmitterInterface::ANY_EVENT, $globalCallbackARemoved);
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $globalCallbackB);

        // emit the event twice
        $this->emitter->emit('foo', 'hello', 123);
        $this->emitter->emit('foo', 'hello', 123);

        // emitting events that have no specific listeners should call the global ones
        $this->emitter->emit('bar', 'hello', 123);

        // the listeners should be called twice
        $this->assertSame(2, $callbackCallCounters['a']);
        $this->assertSame(2, $callbackCallCounters['b']);

        // global listeners should be called twice + once for the "bar" event
        $this->assertSame(3, $callbackCallCounters['global_a']);
        $this->assertSame(3, $callbackCallCounters['global_b']);
    }

    function testEmitShouldInvokeCallbacksAccordingToPriority()
    {
        /** @var bool[] $callStatus */
        $callStatus = [
            'a' => false,
            'b' => false,
            'c' => false,
            'global_a' => false,
            'global_b' => false,
            'global_c' => false,
        ];

        $callbackA = $this->createTestCallback('a', function () use (&$callStatus) {
            $this->assertSame(
                ['a' => false, 'b' => true, 'c' => true, 'global_a' => true, 'global_b' => true, 'global_c' => true],
                $callStatus
            );

            $callStatus['a'] = true;
        });

        $callbackB = $this->createTestCallback('b', function () use (&$callStatus) {
            $this->assertSame(
                ['a' => false, 'b' => false, 'c' => false, 'global_a' => true, 'global_b' => true, 'global_c' => true],
                $callStatus
            );

            $callStatus['b'] = true;
        });

        $callbackC = $this->createTestCallback('c', function () use (&$callStatus) {
            $this->assertSame(
                ['a' => false, 'b' => true, 'c' => false, 'global_a' => true, 'global_b' => true, 'global_c' => true],
                $callStatus
            );

            $callStatus['c'] = true;
        });

        $globalCallbackA = $this->createTestCallback('global_a', function () use (&$callStatus) {
            $this->assertSame(
                ['a' => false, 'b' => false, 'c' => false, 'global_a' => false, 'global_b' => true, 'global_c' => true],
                $callStatus
            );

            $callStatus['global_a'] = true;
        });

        $globalCallbackB = $this->createTestCallback('global_b', function () use (&$callStatus) {
            $this->assertSame(
                ['a' => false, 'b' => false, 'c' => false, 'global_a' => false, 'global_b' => false, 'global_c' => false],
                $callStatus
            );

            $callStatus['global_b'] = true;
        });

        $globalCallbackC = $this->createTestCallback('global_c', function () use (&$callStatus) {
            $this->assertSame(
                ['a' => false, 'b' => false, 'c' => false, 'global_a' => false, 'global_b' => true, 'global_c' => false],
                $callStatus
            );

            $callStatus['global_c'] = true;
        });

        // priority order: B, C, A
        $this->emitter->on('foo', $callbackA, -1);
        $this->emitter->on('foo', $callbackB, 1);
        $this->emitter->on('foo', $callbackC);
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $globalCallbackA, -1);
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $globalCallbackB, 1);
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $globalCallbackC);

        $this->emitter->emit('foo');

        $this->assertSame(
            ['a' => true, 'b' => true, 'c' => true, 'global_a' => true, 'global_b' => true, 'global_c' => true],
            $callStatus
        );
    }

    function testShouldStopPropagation()
    {
        $callbackACalled = false;
        $callbackBCalled = false;

        $callbackA = $this->createTestCallback('a', function () use (&$callbackACalled) {
            $callbackACalled = true;

            return false;
        });

        $callbackB = $this->createTestCallback('b', function () use (&$callbackBCalled) {
            $callbackBCalled = true;
        });

        $this->emitter->on('foo', $callbackA, 1);
        $this->emitter->on('foo', $callbackB, 0);

        $this->emitter->emit('foo');

        $this->assertTrue($callbackACalled);
        $this->assertFalse($callbackBCalled);
    }

    function testShouldStopPropagationInGlobalListener()
    {
        $callStatus = [
            'a' => false,
            'global_a' => false,
            'global_b' => false,
        ];

        $callbackA = $this->createTestCallback('a', function () use (&$callStatus) {
            $callStatus['a'] = true;
        });

        $globalCallbackA = $this->createTestCallback('global_a', function () use (&$callStatus) {
            $callStatus['global_a'] = true;

            return false;
        });

        $globalCallbackB = $this->createTestCallback('global_b', function () use (&$callStatus) {
            $callStatus['global_b'] = true;
        });

        $this->emitter->on('foo', $callbackA);
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $globalCallbackA, 1);
        $this->emitter->on(EventEmitterInterface::ANY_EVENT, $globalCallbackB, 0);

        $this->emitter->emit('foo');

        $this->assertSame(
            ['a' => false, 'global_a' => true, 'global_b' => false],
            $callStatus
        );
    }

    private function createTestCallback(string $name, ?callable $callback = null): callable
    {
        if ($callback === null) {
            $callback = function () {};
        }

        return TestCallbackWrapper::wrap("callback_{$name}", $callback);
    }

    private function createTestListener(string $event, ?string $name = null, ?callable $callback = null, int $priority = 0): EventListener
    {
        if ($callback === null) {
            $callback = function () {};
        }

        $callbackName = "{$event}_listener";

        if ($name !== null) {
            $callbackName .= "_{$name}";
        }

        return new EventListener($event, TestCallbackWrapper::wrap($callbackName, $callback), $priority);
    }

    private function assertCallbacks(array $expectedCallbacks): void
    {
        $actualListeners = $this->emitter->getListeners();
        $hasGlobalListeners = !empty($actualListeners[EventEmitterInterface::ANY_EVENT]);
        $shouldHaveSomeListeners = $hasGlobalListeners;

        foreach ($expectedCallbacks as $event => $expectedEventCallbacks) {
            if ($expectedEventCallbacks) {
                $shouldHaveSomeListeners = true;

                foreach ($expectedEventCallbacks as $callback) {
                    $this->assertTrue(
                        $this->emitter->hasCallback($event, $callback),
                        sprintf('hasCallback("%s", "%s") should return TRUE', $event, $callback)
                    );
                }

                $this->assertTrue(
                    $this->emitter->hasListeners($event),
                    sprintf('hasListeners("%s") should return TRUE', $event)
                );
                $this->assertArrayHasKey(
                    $event,
                    $actualListeners,
                    sprintf('the "%s" event key should exist in output of listeners()', $event)
                );
                $this->assertSame(
                    $expectedEventCallbacks,
                    array_column($actualListeners[$event], 'callback'),
                    sprintf('callbacks from listeners()["%s"] should match the expected callbacks', $event)
                );
                $this->assertSame(
                    $expectedEventCallbacks,
                    array_column($this->emitter->getListeners($event), 'callback'),
                    sprintf('callbacks from listeners("%s") should match the expected callbacks', $event)
                );
            } else {
                $this->assertSame(
                    $hasGlobalListeners,
                    $this->emitter->hasListeners($event),
                    sprintf('hasListeners("%s") should return %s', $event, $hasGlobalListeners ? 'TRUE' : 'FALSE')
                );
                $this->assertArrayNotHasKey(
                    $event,
                    $actualListeners,
                    sprintf('the "%s" event key should not exist in output of listeners()', $event)
                );
                $this->assertSame(
                    [],
                    $this->emitter->getListeners($event),
                    sprintf('listeners("%s") should yield an empty array', $event)
                );
            }
        }

        $unexpectedCallbacks = array_keys(array_diff_key($actualListeners, $expectedCallbacks));

        $this->assertEmpty(
            $unexpectedCallbacks,
            sprintf('Unexpected event callbacks registered for events: %s', implode(', ', $unexpectedCallbacks))
        );

        $this->assertSame(
            $shouldHaveSomeListeners,
            $this->emitter->hasListeners(),
            sprintf('hasListeners() should return %s', $shouldHaveSomeListeners ? 'TRUE' : 'FALSE')
        );
    }

    private function assertListeners(array $expectedListeners): void
    {
        $actualListeners = $this->emitter->getListeners();
        $hasGlobalListeners = !empty($actualListeners[EventEmitterInterface::ANY_EVENT]);
        $shouldHaveSomeListeners = $hasGlobalListeners;

        foreach ($expectedListeners as $event => $expectedEventListeners) {
            if ($expectedEventListeners) {
                $shouldHaveSomeListeners = true;

                foreach ($expectedEventListeners as $listener) {
                    $this->assertTrue(
                        $this->emitter->hasListener($listener),
                        sprintf('hasListener() with listener for event "%s" should return TRUE', $event)
                    );
                }

                $this->assertTrue(
                    $this->emitter->hasListeners($event),
                    sprintf('hasListeners("%s") should return TRUE', $event)
                );
                $this->assertArrayHasKey(
                    $event,
                    $actualListeners,
                    sprintf('The "%s" event key should exist in output of listeners()', $event)
                );
                $this->assertSame(
                    $expectedEventListeners,
                    $actualListeners[$event],
                    sprintf('Listeners from listeners()["%s"] should match the expected listeners', $event)
                );
                $this->assertSame(
                    $expectedEventListeners,
                    $this->emitter->getListeners($event),
                    sprintf('Listeners from listeners("%s") should match the expected listeners', $event)
                );
            } else {
                $this->assertSame(
                    $hasGlobalListeners,
                    $this->emitter->hasListeners($event),
                    sprintf('hasListeners("%s") should return %s', $event, $hasGlobalListeners ? 'TRUE' : 'FALSE')
                );
                $this->assertArrayNotHasKey(
                    $event,
                    $actualListeners,
                    sprintf('The "%s" event key should not exist in output of listeners()', $event)
                );
                $this->assertSame(
                    [],
                    $this->emitter->getListeners($event),
                    sprintf('listeners("%s") should yield an empty array', $event)
                );
            }
        }

        $unexpectedListeners = array_keys(array_diff_key($actualListeners, $expectedListeners));

        $this->assertEmpty(
            $unexpectedListeners,
            sprintf('Unexpected event listeners registered for events: %s', implode(', ', $unexpectedListeners))
        );

        $this->assertSame(
            $shouldHaveSomeListeners,
            $this->emitter->hasListeners(),
            sprintf('hasListeners() should return %s', $shouldHaveSomeListeners ? 'TRUE' : 'FALSE')
        );
    }
}

/**
 * Wraps test callbacks to make them more readable in assertion errors
 *
 * @internal
 */
class TestCallbackWrapper
{
    private static $methods;

    static function wrap(string $readableMethodName, $callback): callable
    {
        static::$methods[$readableMethodName] = $callback;

        return static::class . '::' . $readableMethodName;
    }

    static function clear(): void
    {
        static::$methods = [];
    }

    static function __callStatic(string $name, array $args)
    {
        if (!isset(static::$methods[$name])) {
            throw new \BadMethodCallException(sprintf('Undefined method "%s::%s"', static::class, $name));
        }

        return (static::$methods[$name])(...$args);
    }
}
