Event
#####

Event library that implements variations of the mediator and observer patterns.

.. image:: https://travis-ci.com/kuria/event.svg?branch=master
   :target: https://travis-ci.com/kuria/event

.. contents::
   :depth: 2


Features
********

- emitting events with any number of arguments
- managing listeners for specific or all events
- ordering listeners by priority
- stopping event propagation
- multiple ways to embed the event system


Requirements
************

- PHP 7.1+


Components
**********

Event emitter
=============

The ``EventEmitter`` class maintains a list of listeners and dispatches events
to them.

It is intended to be used as a mediator.

.. code:: php

   <?php

   use Kuria\Event\EventEmitter;

   $emitter = new EventEmitter();

``EventEmitter`` implements ``ObservableInterface``.


Observable
==========

The abstract ``Observable`` class implements the ``ObservableInterface`` using
an inner event emitter.

It is intended to be extended by child classes that will emit their own events:

.. code:: php

   <?php

   use Kuria\Event\Observable;

   class MyComponent extends Observable
   {
       function doSomething()
       {
           $this->emit('something');
       }
   }

Alternatively, you can use the ``ObservableTrait`` to achieve the same result:

.. code:: php

   <?php

   use Kuria\Event\EventEmitterPropInterface;
   use Kuria\Event\ObservableInterface;
   use Kuria\Event\ObservableTrait;

   class MyComponent implements ObservableInterface, EventEmitterPropInterface
   {
       use ObservableTrait;

       // ...
   }


Usage
*****

The following applies to both `event emitter`_ and `observable`_ as they
both implement the ``ObservableInterface``.


Listening to events
===================

Using a callback
----------------

To register a callback to be called when a specific event occurs, register it
using the ``on()`` method. Any event arguments will be passed directly to it.

.. code:: php

   <?php

   $observable->on('some.event', function ($arg1, $arg2) {
       // do something
   });

- the callback can stop event propagation by returning ``FALSE``
- `listener priority`_ can be specified using the 3rd argument of ``on()``

To unregister a callback, call the ``off()`` method with the same callback
(in case of closures this means the same object):

.. code:: php

   <?php

   $observable->off('some.event', $callback); // returns TRUE on success


Using an event listener
-----------------------

To register an event listener, use the ``addListener()`` method:

.. code:: php

   <?php

   use Kuria\Event\EventListener;

   $observable->addListener(
       new EventListener(
           'some.event',
           function ($arg1, $arg2) {}
       )
   );

- `listener priority`_ can be specified by using the 3rd argument of
  the ``EventListener`` constructor
- the callback can stop event propagation by returning ``FALSE``

To unregister a listener, call the ``removeListener()`` method with the same
event listener object:

.. code:: php

   <?php

   $observable->removeListener($eventListener); // returns TRUE on success


Using an event subscriber
-------------------------

Event subscribers subscribe to a list of events. Each event is usually mapped
to one method of the subscriber.

The listeners can be created using the convenient ``listen()`` method
(as shown in the example below) or by manually creating ``EventListener``
instances.

- any callback or method can stop event propagation by returning ``FALSE``
- `listener priority`_ can be specified using 3rd argument of ``listen()``
  or the ``EventListener`` constructor

.. code:: php

   <?php

   use Kuria\Event\EventSubscriber;

   class MySubscriber extends EventSubscriber
   {
       protected function getListeners(): array
       {
           return [
               $this->listen('foo.bar', 'onFooBar'),
               $this->listen('lorem.ipsum', 'onLoremIpsum', 10),
               $this->listen('dolor.sit', 'onDolorSitA'),
               $this->listen('dolor.sit', 'onDolorSitB', 5),
           ];
       }

       function onFooBar() { /* do something */ }
       function onLoremIpsum() { /* do something */ }
       function onDolorSitA() { /* do something */ }
       function onDolorSitB() { /* do something */ }
   }

   $subscriber = new MySubscriber();


Registering the event subscriber:

.. code:: php

   <?php

   $subscriber->subscribeTo($observable);

Unregistering the event subsriber:

.. code:: php

   <?php

   $subscriber->unsubscribeFrom($observable);


Stopping event propagation
--------------------------

Any listener can stop further propagation of the current event by returning ``FALSE``.

This prevents any other listeners from being invoked.


Listener priority
-----------------

Listener priority determines the order in which the listeners are invoked:

- listeners with greater priority are invoked sooner
- listeners with lesser priority are invoked later
- if the priorities are equal, the order of invocation is undefined
- priority can be negative
- default priority is ``0``


Listening to all events
=======================

To listen to all events, use ``ObservableInterface::ANY_EVENT`` in place
of the event name:

.. code:: php

   <?php

   use Kuria\Event\EventListener;
   use Kuria\Event\ObservableInterface;

   $observable->on(
       ObservableInterface::ANY_EVENT,
       function ($event, $arg1, $arg2) {}
   );

   $observable->addListener(
       new EventListener(
           ObservableInterface::ANY_EVENT,
           function ($event, $arg1, $arg2) {}
       )
   );

- global listeners are invoked before listeners of specific events
- global listeners get an extra event name argument before the emitted
  event arguments
- global listeners can also stop event propagation by returning ``FALSE``
  and may have specified `listener priority`_


Emitting events
===============

Events are emitted using the ``emit()`` method.

.. code:: php

   <?php

   $observable->emit('foo');

Any extra arguments will be passed to the listeners.

.. code:: php

   <?php

   $observable->emit('foo', 'hello', 123);


.. NOTE::

   Variable references cannot be emitted directly as an argument. If you need to use
   references, wrap them in an object or an array.


Documenting events
==================

While the event library itself doesn't require this, it is a good idea to explicitly define
possible event names and their arguments somewhere.

The example below defines a ``FieldEvents`` class for this purpose. Constants of this class
are then used in place of event names and their annotations serve as documentation. This also
allows for code-completion.

.. code:: php

   <?php
   
   use Kuria\Event\Observable;

   /**
    * @see Field
    */
   abstract class FieldEvents
   {
       /**
        * Emitted when field value is about to be changed.
        *
        * @param Field $field
        * @param mixed $oldValue
        * @param mixed $newValue
        */
       const CHANGE = 'change';
   
       /**
        * Emitted when field value is about to be cleared.
        *
        * @param Field $field
        */
       const CLEAR = 'clear';
   }
   
   /**
    * @see FieldEvents
    */
   class Field extends Observable
   {
       private $name;
       private $value;
   
       function __construct(string $name, $value = null)
       {
           $this->name = $name;
           $this->value = $value;
       }
   
       function getName(): string
       {
           return $this->name;
       }
   
       function getValue()
       {
           return $this->value;
       }
   
       function setValue($value): void
       {
           $this->emit(FieldEvents::CHANGE, $this, $this->value, $value);
   
           $this->value = $value;
       }
   
       function clear()
       {
           $this->emit(FieldEvents::CLEAR, $this);
   
           $this->value = null;
       }
   }
   
.. NOTE::

   Using ``@param`` annotations on class constants is non-standard, but IDE's dont mind
   it and some documentation-generators (such as Doxygen) even display them nicely.


Usage example
-------------

.. code:: php

   <?php
  
   $field = new Field('username');
   
   $field->on(FieldEvents::CHANGE, function (Field $field, $oldValue, $newValue) {
       echo "Field '{$field->getName()}' has been changed from '{$oldValue}' to '{$newValue}'\n";
   });
   
   $field->on(FieldEvents::CLEAR, function (Field $field) {
       echo "Field '{$field->getName()}' has been cleared\n";
   });
   
   $field->setValue('john.smith');
   $field->setValue('foo.bar123');
   $field->clear();

Output:

::

  Field 'username' has been changed from '' to 'john.smith'
  Field 'username' has been changed from 'john.smith' to 'foo.bar123'
  Field 'username' has been cleared
