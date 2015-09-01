<?php

namespace Kuria\Event;

class EventSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testSubscribe()
    {
        $that = $this;

        $onBarCalled = false;
        $onBarOtherCalled = false;
        $onBazACalled = false;
        $onBazBCalled = false;

        $subscriber = $this->getSubscriberMock();

        $subscriber
            ->expects($this->once())
            ->method('onFoo')
        ;

        $subscriber
            ->expects($this->once())
            ->method('onBar')
            ->willReturnCallback(function () use ($that, &$onBarCalled, &$onBarOtherCalled) {
                $that->assertFalse($onBarCalled);
                $that->assertFalse($onBarOtherCalled);

                $onBarCalled = true;
            })
        ;

        $subscriber
            ->expects($this->once())
            ->method('onBazA')
            ->willReturnCallback(function () use ($that, &$onBazACalled, &$onBazBCalled) {
                $that->assertFalse($onBazACalled);
                $that->assertTrue($onBazBCalled);

                $onBazACalled = true;
            })
        ;

        $subscriber
            ->expects($this->once())
            ->method('onBazB')
            ->willReturnCallback(function () use ($that, &$onBazACalled, &$onBazBCalled) {
                $that->assertFalse($onBazACalled);
                $that->assertFalse($onBazBCalled);

                $onBazBCalled = true;
            })
        ;

        $emitter = new EventEmitter();

        $emitter
            ->on('bar', function () use ($that, &$onBarCalled, &$onBarOtherCalled) {
                $that->assertTrue($onBarCalled);
                $that->assertFalse($onBarOtherCalled);

                $onBarOtherCalled = true;
            })
            ->subscribe($subscriber)
        ;

        $this->assertListenerCount($emitter, 5);

        $emitter->emit('foo');
        $emitter->emit('bar');
        $emitter->emit('baz');
    }

    public function testUnsubscribe()
    {
        $subscriber = $this->getSubscriberMock();

        $subscriber
            ->expects($this->never())
            ->method('onFoo')
        ;

        $subscriber
            ->expects($this->never())
            ->method('onBar')
        ;

        $subscriber
            ->expects($this->never())
            ->method('onBazA')
        ;

        $subscriber
            ->expects($this->never())
            ->method('onBazB')
        ;

        $emitter = new EventEmitter();

        $emitter->subscribe($subscriber);

        $this->assertListenerCount($emitter, 4);

        $emitter->unsubscribe($subscriber);

        $emitter->emit('foo');
        $emitter->emit('bar');
        $emitter->emit('baz');

        $this->assertListenerCount($emitter, 0);
    }

    /**
     * @return EventSubscriberInterface
     */
    private function getSubscriberMock()
    {
        $subscriber = $this->getMock(
            __NAMESPACE__ . '\\EventSubscriber',
            array('getEvents', 'onFoo', 'onBar', 'onBazA', 'onBazB')
        );

        $subscriber
            ->expects($this->any())
            ->method('getEvents')
            ->willReturn(array(
                'foo' => 'onFoo',
                'bar' => array('onBar', 5),
                'baz' => array(
                    array('onBazA'),
                    array('onBazB', 10),
                )
            ))
        ;

        return $subscriber;
    }

    /**
     * @param EventEmitterInterface $emitter
     * @param int                   $expected
     */
    private function assertListenerCount(EventEmitterInterface $emitter, $expected)
    {
        return $this->assertSame(
            $expected,
            array_sum(array_map('sizeof', $emitter->getListeners()))
        );
    }
}
