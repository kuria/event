Event
=====

Event library that implements variations of the mediator and observer patterns.


## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Usage](#usage)
    - [Event emitters](#event-emitters)
    - [Listening to events](#listeners)
    - [Emitting events](#emitting)


## <a name="features"></a> Features

- simple to use
- multiple ways to incorporate the event system (inheritance or as a mediator)
- listeners can be simple callbacks or event subscribers (objects)
- listener order can be controlled by specifying priority
- any number of values can be emitted along with the event
    - these values are passed to the listeners directly
    - no specialized event object needed (unless you make one)


## <a name="requirements"></a> Requirements

- PHP 5.3 or newer


## <a name="usage"></a> Usage

### <a name="event-emitters"></a> Event emitters

The `EventEmitter` class is the core of the event system. It maintains a list of listeners
and dispatches events to them.

There are 2 main ways incorporate this functionality into your code:

1) extending the `EventEmitter` class (or using the `EventEmitterTrait`)

    use Kuria\Event\EventEmitter;
    
    class Foo extends EventEmitter
    {
        // your code
    }

    $emitter = new Foo();

2) creating an instance of `EventEmitter` and using it as a mediator

    use Kuria\Event\EventEmitter;
    
    $emitter = new EventEmitter();


### <a name="listeners"></a> Listening to events

#### Event listeners

To listen to an event, simply call the `on()` method on an instance of `EventEmitter`.

    $emitter->on('some.event', function ($arg1, $arg2) {
        // do something
    });


#### Event subscribers

Event subscribers subscribe to a list of events. Each event is mapped to one or more methods
of the subscriber.

    use Kuria\Event\EventSubscriberAbstract;

    class MySubscriber extends EventSubscriberAbstract
    {
        public function getEvents()
        {
            return array(
                'foo.bar' => 'onFooBar',
                'lorem.ipsum' => array('onLoremIpsum', 10),
                'dolor.sit' => array(
                    array('onDolorSitA'),
                    array('onDolorSitB', 5),
                ),
            );
        }

        public function onFooBar() { /* do something */ }
        public function onLoremIpsum() { /* do something */ }
        public function onDolorSitA() { /* do something */ }
        public function onDolorSitB() { /* do something */ }
    }

Registering the event subscriber:

    // 1st way
    $subscriber->subscribeTo($emitter);

    // 2nd way
    $emitter->subscribe($subscriber);

Unregistering the event subsriber:

    // 1st way
    $subscriber->unsubscribeFrom($emitter);

    // 2nd way
    $emitter->unsubscribe($subscriber);


#### Listener priority

Priority can be specified when a listener is being registered.

- listeners with greater priority are invoked sooner
- listeners with lesser priority are invoked later
- if the priorities are equal, the order of invocation is undefined
- priority can be negative
- default priority is `0`


### <a name="emitting"></a> Emitting events

Events are emitted using the `emit()` method.

    $emitter->emit('foo');

Any extra arguments will be passed to the listeners. Note that references cannot be passed
this way.

    $emitter->emit('foo', 'hello', 123);

If you need to pass variable number of arguments or references, use the `emitArray()` method.

    $emitter->emitArray('foo', array('hello', 123));


#### Stopping event propagation

Any listener can stop further propagation of the current event by returning `FALSE`.

This prevents any pending listeners from being invoked.


#### Documenting emitted events

You can use custom annotations to document the list of emitted events for a quick future reference.

- this is just a documentation practice suggestion and has no impact on functionality
- other Kuria components use this way to document their events


##### Annotation format

The syntax is identical to the phpDoc [@method](http://phpdoc.org/docs/latest/references/phpdoc/tags/method.html) tag, but without the return type declaration:

    @emits <name>(<type1> <$parameter1>, ...) [<description>]


##### Example

    use Kuria\Event\EventEmitter;

    /**
     * @emits change(mixed $newValue, mixed $oldValue) when the value changes
     */
    class Field extends EventEmitter
    {
        private $value;

        public function getValue()
        {
            return $this->value;
        }

        public function setValue($newValue)
        {
            $this->emit('change', $newValue, $this->value);

            $this->value = $newValue;
        }
    }
