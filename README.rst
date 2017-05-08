Event
#####

Event library that implements variations of the mediator and observer patterns.

.. contents::
   :depth: 3


Features
********

-  managing listeners for specific events (callbacks & event subscribers)
-  managing global listeners (invoked before any event)
-  ordering listeners by priority
-  dispatching events

   -  event name + any number of arguments
   -  no specialized event object is required (unless you make one)


Requirements
************

-  PHP 5.3+ or 7.0+


Usage
*****

Event emitters
==============

The ``EventEmitter`` class is the core of the event system. It maintains a list of listeners and dispatches events to them.

There are several ways to incorporate its functionality into your code:


1. **extending EventEmitter**

   .. code:: php

      <?php

      use Kuria\Event\EventEmitter;

      class Foo extends EventEmitter
      {
          // your code
      }

      $emitter = new Foo();

2. **using EventEmitterTrait** (requires PHP 5.4+)

   .. code:: php

      <?php

      use Kuria\Event\EventEmitterInterface;
      use Kuria\Event\EventEmitterTrait;

      class Foo implements EventEmitterInterface
      {
          use EventEmitterTrait;
      }

3. **using EventEmitter as a mediator**

   .. code:: php

      <?php

      use Kuria\Event\EventEmitter;

      $emitter = new EventEmitter();


Listening to events
===================

Event listeners
---------------

To listen to an event, register your callback using the ``on()`` method:

.. code:: php

   <?php

   $emitter->on('some.event', function ($arg1, $arg2) {
       // do something
   });

To listen to an event only once, register your callback using the ``once()`` method:

.. code:: php

   <?php

   $emitter->once('some.event', function ($arg1, $arg2) {
       // do something
       // invoked only once (then removed)
   });

To listen to all events, use ``*`` as the event name. Global listeners are invoked before the event-specific ones. Works with ``once()`` as well.

.. code:: php

   <?php

   $emitter->on('*', function ($event, $arg1, $arg2) {
       // do something
       // invoked before any event
   });


Event subscribers
-----------------

Event subscribers subscribe to a list of events. Each event is mapped to one or more methods of the subscriber.

.. code:: php

   <?php

   use Kuria\Event\EventSubscriber;

   class MySubscriber extends EventSubscriber
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

.. code:: php

   <?php

   // 1st way
   $subscriber->subscribeTo($emitter);

   // 2nd way
   $emitter->subscribe($subscriber);

Unregistering the event subsriber:

.. code:: php

   <?php

   // 1st way
   $subscriber->unsubscribeFrom($emitter);

   // 2nd way
   $emitter->unsubscribe($subscriber);


Listener priority
-----------------

Priority can be specified when a listener is being registered.

-  listeners with greater priority are invoked sooner
-  listeners with lesser priority are invoked later
-  if the priorities are equal, the order of invocation is undefined
-  priority can be negative
-  default priority is ``0``


Emitting events
---------------

Events are emitted using the ``emit()`` method.

.. code:: php

   <?php

   $emitter->emit('foo');

Any extra arguments will be passed to the listeners. Note that references cannot be passed this way.

.. code:: php

   <?php

   $emitter->emit('foo', 'hello', 123);

If you need to pass variable number of arguments or references, use the ``emitArray()`` method.

.. code:: php

   <?php

   $emitter->emitArray('foo', array('hello', 123));


Stopping event propagation
--------------------------

Any listener can stop further propagation of the current event by returning ``FALSE``.

This prevents any other listeners from being invoked.


Documenting emitted events
--------------------------

You can use custom annotations to document the list of emitted events for a quick future reference.

-  this is just a documentation practice suggestion and has no impact on functionality
-  other Kuria components use this way to document their events


Annotation format
^^^^^^^^^^^^^^^^^

The syntax is identical to the phpDoc `@method <http://phpdoc.org/docs/latest/references/phpdoc/tags/method.html>`_ tag, but without the return type declaration:

::

  @emits <name>(<type1> <$parameter1>, ...) [<description>]


Example
^^^^^^^

.. code:: php

   <?php

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
