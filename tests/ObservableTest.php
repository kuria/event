<?php

namespace Kuria\Event;

class ObservableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Get class name of the observable
     *
     * @return string
     */
    protected function getObservableClass()
    {
        return __NAMESPACE__ . '\Observable';
    }

    /**
     * Create the observable
     *
     * @return ObservableInterface
     */
    protected function createObservable()
    {
        $className = $this->getObservableClass();

        return new $className();
    }

    /**
     * Emit event using the observable
     *
     * @param object $observable
     * @param string $eventName
     */
    protected function emitEvent($observable, $eventName)
    {
        $event = new Event();
        $event->setName($eventName);

        $observable->notifyObservers($event);
    }

    /**
     * Create test event
     *
     * @param string $name
     * @return EventInterface
     */
    protected function createEvent($name)
    {
        $event = new Event();
        $event->setName($name);

        return $event;
    }

    public function testEventSubscriber()
    {
        $observable = $this->createObservable();

        $subscriber = $this->getMock(__NAMESPACE__ . '\EventSubscriberInterface', array(
            'getEvents',
            'onFooBar',
            'onLoremIpsum',
        ));
        $this->configureSubscriberMock($subscriber);

        $subscriber->expects($this->once())
            ->method('getEvents')
            ->willReturn(array(
                'foo.bar' => 'onFooBar',
                'lorem.ipsum' => array('onLoremIpsum', 10),
            ))
        ;

        $observable->addSubscriber($subscriber);

        $this->emitEvent($observable, 'foo.bar');
        $this->emitEvent($observable, 'lorem.ipsum');
    }

    public function testListener()
    {
        $observable = $this->createObservable();

        $listenerContainer = $this->getMock('stdClass', array('onFooBar', 'onLoremIpsum'));
        $this->configureSubscriberMock($listenerContainer);

        $observable->addListener('foo.bar', array($listenerContainer, 'onFooBar'));
        $observable->addListener('lorem.ipsum', array($listenerContainer, 'onLoremIpsum'), 10);

        $this->emitEvent($observable, 'foo.bar');
        $this->emitEvent($observable, 'lorem.ipsum');
    }

    private function configureSubscriberMock(\PHPUnit_Framework_MockObject_MockObject $mock)
    {
        $mock->expects($this->once())
            ->method('onFooBar')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf(__NAMESPACE__ . '\EventInterface'),
                    $this->callback(function (EventInterface $event) {
                        return 'foo.bar' === $event->getName();
                    })
                ),
                $this->isInstanceOf($this->getObservableClass())
            )
        ;

        $mock->expects($this->once())
            ->method('onLoremIpsum')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf(__NAMESPACE__ . '\EventInterface'),
                    $this->callback(function (EventInterface $event) {
                        return 'lorem.ipsum' === $event->getName();
                    })
                ),
                $this->isInstanceOf($this->getObservableClass())
            )
        ;
    }

    public function testSubscriberManipulation()
    {
        $observable = $this->createObservable();

        $subscriber = $this->getMock(__NAMESPACE__ . '\EventSubscriberInterface', array(
            'getEvents',
            'onFooBar',
        ));

        $subscriber->expects($this->once())
            ->method('getEvents')
            ->willReturn(array(
                'foo.bar' => 'onFooBar',
            ))
        ;

        $subscriber->expects($this->once())
            ->method('onFooBar')
        ;

        $observable->addSubscriber($subscriber);
        $this->assertTrue($observable->hasObservers('foo.bar'));

        $this->emitEvent($observable, 'foo.bar');

        $observable->removeSubscriber($subscriber);
        $this->assertFalse($observable->hasObservers('foo.bar'));

        $this->emitEvent($observable, 'foo.bar');
    }

    public function testListenerManipulation()
    {
        $observable = $this->createObservable();

        $listenerContainer = $this->getMock('stdClass', array(
            'onFooBar',
        ));

        $listenerContainer->expects($this->once())
            ->method('onFooBar')
        ;

        $callback = array($listenerContainer, 'onFooBar');

        $observable->addListener('foo.bar', $callback);
        $this->assertTrue($observable->hasObservers('foo.bar'));

        $this->emitEvent($observable, 'foo.bar');

        $observable->removeListener('foo.bar', $callback);
        $this->assertFalse($observable->hasObservers('foo.bar'));

        $this->emitEvent($observable, 'foo.bar');
    }

    public function testPriority()
    {
        $observable = $this->createObservable();

        $lessPriorityCallbackCalled = false;
        $morePriorityCallbackCalled = false;

        $that = $this;

        $lessPriorityCallback = function () use (&$lessPriorityCallbackCalled, &$morePriorityCallbackCalled, $that) {
            $that->assertFalse($lessPriorityCallbackCalled);
            $that->assertTrue($morePriorityCallbackCalled);

            $lessPriorityCallbackCalled = true;
        };

        $morePriorityCallback = function () use (&$lessPriorityCallbackCalled, &$morePriorityCallbackCalled, $that) {
            $that->assertFalse($lessPriorityCallbackCalled);
            $that->assertFalse($morePriorityCallbackCalled);

            $morePriorityCallbackCalled = true;
        };

        $observable->addListener('foo', $lessPriorityCallback, 0);
        $observable->addListener('foo', $morePriorityCallback, 10);

        $this->emitEvent($observable, 'foo');

        $this->assertTrue($lessPriorityCallbackCalled);
        $this->assertTrue($morePriorityCallbackCalled);
    }

    public function testStop()
    {
        $observable = $this->createObservable();

        $lessPriorityCallbackCalled = false;
        $morePriorityCallbackCalled = false;

        $lessPriorityCallback = function () use (&$lessPriorityCallbackCalled) {
            $lessPriorityCallbackCalled = true;
        };

        $morePriorityCallback = function (EventInterface $event) use (&$morePriorityCallbackCalled) {
            $morePriorityCallbackCalled = true;

            $event->stop();
        };

        $observable->addListener('foo', $lessPriorityCallback, 0);
        $observable->addListener('foo', $morePriorityCallback, 10);

        $this->emitEvent($observable, 'foo');

        $this->assertFalse($lessPriorityCallbackCalled);
        $this->assertTrue($morePriorityCallbackCalled);
    }

    public function testHandledFlag()
    {
        $observable = $this->createObservable();

        $event = new Event();
        $event->setName('foo');

        // default isHandled must be FALSE
        $this->assertFalse($event->isHandled());

        // emitting an event without any subscriber
        // MUST NOT change the isHandled flag
        $observable->notifyObservers($event);
        $this->assertFalse($event->isHandled());

        // add dummy subscriber
        $observable->addListener('foo', function () {});

        // emitting an event with one or more subscribers
        // MUST set the isHandled flag to TRUE
        $observable->notifyObservers($event);
        $this->assertTrue($event->isHandled());
    }

    public function testClear()
    {
        $observable = $this->createObservable();

        $subscriber = $this->getMock(__NAMESPACE__ . '\EventSubscriberInterface');
        $subscriber->expects($this->any())
            ->method('getEvents')
            ->willReturn(array(
                'bar' => 'onFooBar',
            ))
        ;

        $observable->addSubscriber($subscriber);
        $observable->addListener('foo', function () {});

        $this->assertTrue($observable->hasObservers('foo'));
        $this->assertTrue($observable->hasObservers('bar'));

        $observable->clearObservers('foo');
        $this->assertFalse($observable->hasObservers('foo'));
        $this->assertTrue($observable->hasObservers('bar'));

        $observable->clearObservers('bar');
        $this->assertFalse($observable->hasObservers('foo'));
        $this->assertFalse($observable->hasObservers('bar'));

        $observable->addSubscriber($subscriber);
        $observable->addListener('foo', function () {});

        $observable->clearObservers();
        $this->assertFalse($observable->hasObservers('foo'));
        $this->assertFalse($observable->hasObservers('bar'));
    }

    /**
     * @expectedException LogicException
     */
    public function testExceptionOnSendingUnnamedEvent()
    {
        $observable = $this->createObservable();

        $observable->notifyObservers(new Event());
    }
}
