<?php declare(strict_types=1);

namespace Kuria\Event;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ObservableTest extends TestCase
{
    function testShouldPerformObservableOperations()
    {
        /** @var EventEmitter|MockObject $eventEmitterSpy */
        $eventEmitterSpy = $this->createTestProxy(EventEmitter::class);
        /** @var Observable|MockObject $observable */
        $observable = $this->createPartialMock(Observable::class, ['createEventEmitter']);
        $observable->method('createEventEmitter')->willReturn($eventEmitterSpy);

        $listener = new EventListener('test', 'some_callback');

        $eventEmitterSpy->expects($this->at(0))
            ->method('on')
            ->with('foo', 'some_callback', 0);

        $eventEmitterSpy->expects($this->at(1))
            ->method('on')
            ->with('bar', 'another_callback', 10);

        $eventEmitterSpy->expects($this->once())
            ->method('off')
            ->with('baz', 'yet_another_callback');

        $eventEmitterSpy->expects($this->once())
            ->method('addListener')
            ->with($this->identicalTo($listener));

        $eventEmitterSpy->expects($this->once())
            ->method('removeListener')
            ->with($this->identicalTo($listener));

        $eventEmitterSpy->expects($this->once())
            ->method('emit')
            ->with('some_event', 1, 2, 3);

        $observable->on('foo', 'some_callback');
        $observable->on('bar', 'another_callback', 10);
        $observable->off('baz', 'yet_another_callback');
        $observable->addListener($listener);
        $observable->removeListener($listener);
        $observable->emit('some_event', 1, 2, 3);
    }

    function testEmitShouldNotInitializeEventEmitter()
    {
        /** @var Observable|MockObject $observable */
        $observable = $this->createPartialMock(Observable::class, ['createEventEmitter']);
        $observable->expects($this->never())->method('createEventEmitter');

        // the inner event emitter should not be initialized by emit() alone
        $observable->emit('foo');
    }

    function testShouldGetEventEmitter()
    {
        $observable = new class extends Observable {};

        $eventEmitter = $observable->getEventEmitter();

        $this->assertInstanceOf(EventEmitter::class, $eventEmitter);
        $this->assertSame($eventEmitter, $observable->getEventEmitter(), 'eventEmitter() should result the same instance');
    }
}
