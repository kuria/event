<?php declare(strict_types=1);

namespace Kuria\Event;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EventSubscriberTest extends TestCase
{
    /** @var EventEmitter */
    private $emitter;
    /** @var TestEventSubscriber|MockObject */
    private $subscriber;

    protected function setUp()
    {
        $this->emitter = new EventEmitter();
        $this->subscriber = $this->createPartialMock(TestEventSubscriber::class, [
            'onFoo',
            'onBar',
            'onBazA',
            'onBazB',
        ]);
    }

    function testSubscribe()
    {
        $onBarCalled = false;
        $onBarOtherCalled = false;
        $onBazACalled = false;
        $onBazBCalled = false;

        $this->subscriber
            ->expects($this->once())
            ->method('onFoo');

        $this->subscriber
            ->expects($this->once())
            ->method('onBar')
            ->willReturnCallback(function () use (&$onBarCalled, &$onBarOtherCalled) {
                $this->assertFalse($onBarCalled);
                $this->assertFalse($onBarOtherCalled);

                $onBarCalled = true;
            });

        $this->subscriber
            ->expects($this->once())
            ->method('onBazA')
            ->willReturnCallback(function () use (&$onBazACalled, &$onBazBCalled) {
                $this->assertFalse($onBazACalled);
                $this->assertTrue($onBazBCalled);

                $onBazACalled = true;
            });

        $this->subscriber
            ->expects($this->once())
            ->method('onBazB')
            ->willReturnCallback(function () use (&$onBazACalled, &$onBazBCalled) {
                $this->assertFalse($onBazACalled);
                $this->assertFalse($onBazBCalled);

                $onBazBCalled = true;
            });

        $this->emitter->on('bar', function () use (&$onBarCalled, &$onBarOtherCalled) {
            $this->assertTrue($onBarCalled);
            $this->assertFalse($onBarOtherCalled);

            $onBarOtherCalled = true;
        });
        
        $this->subscriber->subscribeTo($this->emitter);

        $this->assertListenerCount(5);

        $this->emitter->emit('foo');
        $this->emitter->emit('bar');
        $this->emitter->emit('baz');
    }

    function testUnsubscribe()
    {
        $this->subscriber
            ->expects($this->never())
            ->method('onFoo');

        $this->subscriber
            ->expects($this->never())
            ->method('onBar');

        $this->subscriber
            ->expects($this->never())
            ->method('onBazA');

        $this->subscriber
            ->expects($this->never())
            ->method('onBazB');

        $this->subscriber->subscribeTo($this->emitter);

        $this->assertListenerCount(4);

        $this->subscriber->unsubscribeFrom($this->emitter);

        $this->emitter->emit('foo');
        $this->emitter->emit('bar');
        $this->emitter->emit('baz');

        $this->assertListenerCount(0);
    }

    private function assertListenerCount(int $expected): void
    {
        $this->assertSame(
            $expected,
            array_sum(array_map('sizeof', $this->emitter->getListeners()))
        );
    }
}

/**
 * @internal
 */
class TestEventSubscriber extends EventSubscriber
{
    protected function getListeners(): array
    {
        return [
            $this->listen('foo', 'onFoo'),
            $this->listen('bar', 'onBar', 5),
            $this->listen('baz', 'onBazA'),
            $this->listen('baz', 'onBazB', 10),
        ];
    }

    function onFoo() {}
    function onBar() {}
    function onBazA() {}
    function onBazB() {}
}
