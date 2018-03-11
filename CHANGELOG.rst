Changelog
#########

2.0.1
*****

- cs fixes, added codestyle checks


2.0.0
*****

- updated to PHP 7.1
- simplified ``EventEmitter`` internals
- optimized listener sorting in case all listeners have the same priority
- the ``EventListener`` value object is now used for listener definitions instead of arrays
- removed:

  - ``EventEmitter::emitArray()``
  - ``EventEmitter::subscribe()``
  - ``EventEmitter::unsubscribe()``
  - ``EventEmitter::once()``
  - ``EventEmitterTrait``

- added:

  - ``Observable``
  - ``ObservableTrait``
  - ``ObservableInterface``


1.0.1
*****

- code style and test improvements


1.0.0
*****

- implemented global listeners
- refactored ``EventEmitter``
- code style fixes


0.2.0
*****

- refactoring
- API simplification
- allow emitting anyting (removed mandatory event class)
- implemented ``once()``


0.1.0
*****

Initial release
