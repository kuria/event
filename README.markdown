Event
=====

Event library that implements variations of the [mediator](http://en.wikipedia.org/wiki/Mediator_pattern)
and [observer](http://en.wikipedia.org/wiki/Observer_pattern) patterns.


## Features

- events
- observers
    - listeners (simple callbacks)
    - event subscribers (map events to methods)
- observable
    - holds observers
    - notifies observers of events (in order of priority)
- external observable
    - proxies the observable interface to the underlying observable object
    - can be used as a parent class or through the trait
- event emitter (aka event dispatcher)
    - extends observable
    - provides convenient facility to emit events


## Requirements

- PHP 5.3 or newer
- PHP 5.4 or newer to use `ExternalObservableTrait`


## Usage

Basic explanation and feature examples.


### Events

Anything that implements `EventInterface` is an event.

- the base `Event` class is designed to be subclassed
- event must have a name before being sent to observers


#### Stopping event propagation

Any observer can stop further propagation of the current event by calling:

    $event->stop();

This prevents any pending observers from being notified.


### Event emitter

The event emitter is designed to be used as a standalone mediator facility (aka event dispatcher).

    use Kuria\Event\EventEmitter;

    $emitter = new EventEmitter();


#### Sending events

To send an event, use the `emit()` method:

    $emitter->emit('foo.bar'); // implicit Event object with that name is created

... or with a custom event object:

    use Kuria\Event\Event;

    class MyEvent extends Event
    {
        // your code here
        // constructor, getters, setters, etc.
        // (the observers will have access to these)
    }

    $emitter->emit('foo.bar', new MyEvent());


#### Adding observers

The emitter extends the observable object. See below.


### Observable

Observable is an object that holds a list of observers and notifies them of events. It is designed
to be used as a base class.

    use Kuria\Event\Event;
    use Kuria\Event\Observable;

    class FooEvent extends Event
    {
        protected $name = 'foo.example';
    }

    class Foo extends Observable
    {
        public function bar()
        {
            $this->notifyObservers(new FooEvent());
        }
    }


#### Example usage

    $foo = new Foo();

    $foo->addListener('foo.example', function (FooEvent $event, Foo $foo) {
        echo "Got notified of {$event->getName()} from observable " . get_class($foo) . "\n";
    });

    $foo->bar();

Output:

    Got notified of foo.example from observable Foo


#### Attaching observers

To get notified when a particular event occurs, the observer needs to be attached
to an observable object using one of its methods.


##### Listeners

Listeners are the most simple way of attaching observers to an event. When that event occurs, the callback is invoked.

    use Kuria\Event\EventInterface;
    use Kuria\Event\ObservableInterface;

    $observable->addListener('foo.bar', function (EventInterface $event, ObservableInterface $observable) {
        // do something
    });

To specify priority, pass the third argument ($priority).


##### Event subscribers

Event subscriber represents an immutable list of observers. Each event is mapped to one method
of the subscriber. When one of the events occurs, the respective method is called.

    use Kuria\Event\EventSubscriberInterface;
    use Kuria\Event\EventInterface;
    use Kuria\Event\ObservableInterface;

    class MySubscriber implements EventSubscriberInterface
    {
        public function getEvents()
        {
            return array(
                'foo.bar' => 'onFooBar',
                'lorem.ipsum' => array('onLoremIpsum', 10), // with priority
            );
        }

        public function onFooBar(EventInterface $event, ObservableInterface $observable)
        {
            // do something
        }

        public function onLoremIpsum(EventInterface $event, ObservableInterface $observable)
        {
            // do something
        }
    }


Attaching the event subscriber:

    $observable->addSubscriber(new MySubscriber());


#### Observer priority

Both listeners and subscribers support specifying priority.

- observers with greater priority are notified sooner
- observers with lesser priority are notified later
- if the priorities are equal, the order of notification is undefined
- default priority is `0`
- priority can be negative
