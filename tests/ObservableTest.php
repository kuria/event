<?php declare(strict_types=1);

namespace Kuria\Event;

use PHPUnit\Framework\TestCase;

class ObservableTest extends TestCase
{
    function testObservableInterface()
    {
        /** @var EventEmitter|\PHPUnit_Framework_MockObject_MockObject $eventEmitterSpy */
        $eventEmitterSpy = $this->createTestProxy(EventEmitter::class);
        /** @var Observable|\PHPUnit_Framework_MockObject_MockObject $observable */
        $observable = $this->createPartialMock(Observable::class, ['createEventEmitter']);
        $observable->method('createEventEmitter')->willReturn($eventEmitterSpy);

        $listener = new EventListener('test', 'some_callback');

        $eventEmitterSpy->expects($this->at(0))
            ->method('on')
            ->with($this->identicalTo('foo'), $this->identicalTo('some_callback'), $this->identicalTo(0));

        $eventEmitterSpy->expects($this->at(1))
            ->method('on')
            ->with($this->identicalTo('bar'), $this->identicalTo('another_callback'), $this->identicalTo(10));

        $eventEmitterSpy->expects($this->once())
            ->method('off')
            ->with($this->identicalTo('baz'), $this->identicalTo('yet_another_callback'));

        $eventEmitterSpy->expects($this->once())
            ->method('addListener')
            ->with($this->identicalTo($listener));

        $eventEmitterSpy->expects($this->once())
            ->method('removeListener')
            ->with($this->identicalTo($listener));

        $eventEmitterSpy->expects($this->once())
            ->method('emit')
            ->with($this->identicalTo('some_event'), $this->identicalTo(1), $this->identicalTo(2), $this->identicalTo(3));

        $observable->on('foo', 'some_callback');
        $observable->on('bar', 'another_callback', 10);
        $observable->off('baz', 'yet_another_callback');
        $observable->addListener($listener);
        $observable->removeListener($listener);
        $observable->emit('some_event', 1, 2, 3);
    }

    function testLazyEmit()
    {
        /** @var Observable|\PHPUnit_Framework_MockObject_MockObject $observable */
        $observable = $this->createPartialMock(Observable::class, ['createEventEmitter']);
        $observable->expects($this->never())->method('createEventEmitter');

        // the inner event emitter should not be initialized by emit() alone
        $observable->emit('foo');
    }

    function testEventEmitterPropInterface()
    {
        $observable = new class extends Observable {};

        $eventEmitter = $observable->getEventEmitter();

        $this->assertInstanceOf(EventEmitter::class, $eventEmitter);
        $this->assertSame($eventEmitter, $observable->getEventEmitter(), 'eventEmitter() should result the same instance');
    }
}
