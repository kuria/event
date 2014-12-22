<?php

namespace Kuria\Event;

class ExternalObservableTest extends ObservableTest
{
    protected function getObservableClass()
    {
        return __NAMESPACE__ . '\ExternalObservable';
    }

    protected function createObservable()
    {
        $observable = parent::createObservable();
        $observable->setNestedObservable(
            new Observable()
        );

        return $observable;
    }

    public function testNestedObservable()
    {
        $observable = $this->createObservable();

        $this->assertInstanceOf(__NAMESPACE__ . '\ObservableInterface', $observable->getNestedObservable());
        $observable->setNestedObservable(null);
        $this->assertNull($observable->getNestedObservable());
    }
    
    public function provideObservableInterfaceMethodCalls()
    {
        return array(
            array('addSubscriber', array($this->getMock(__NAMESPACE__ . '\EventSubscriberInterface'))),
            array('removeSubscriber', array($this->getMock(__NAMESPACE__ . '\EventSubscriberInterface'))),
            array('addListener', array('foo', function () {})),
            array('removeListener', array('foo', function () {})),
            array('hasObservers', array('foo')),
            array('clearObservers', array('foo')),
            array('notifyObservers', array(new Event())),
        );
    }

    /**
     * @dataProvider provideObservableInterfaceMethodCalls
     * @expectedException LogicException
     */
    public function testNullObservableThrowsExceptionOnInteraction($method, array $arguments)
    {
        $observable = $this->createObservable();
        $observable->setNestedObservable(null);

        call_user_func_array(array($observable, $method), $arguments);
    }
}
