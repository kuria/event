<?php declare(strict_types=1);

namespace Kuria\Event;

/**
 * Classes using this trait should also implement ObservableInterface + EventEmitterPropInterface.
 *
 * @see ObservableInterface
 * @see EventEmitterPropInterface
 */
trait ObservableTrait
{
    /** @var EventEmitterInterface|null */
    private $eventEmitter;

    /**
     * Register a callback
     */
    function on(string $event, $callback, int $priority = 0): void
    {
        $this->getEventEmitter()->on($event, $callback, $priority);
    }

    /**
     * Unregister a callback
     */
    function off(string $event, $callback): bool
    {
        return $this->getEventEmitter()->off($event, $callback);
    }

    /**
     * Register an event listener
     */
    function addListener(EventListener $listener): void
    {
        $this->getEventEmitter()->addListener($listener);
    }

    /**
     * Unregister an event listener
     */
    function removeListener(EventListener $listener): bool
    {
        return $this->getEventEmitter()->removeListener($listener);
    }

    /**
     * Emit an event
     */
    function emit(string $event, ...$args): void
    {
        // only emit if the event emitter has been initialized already
        // (if it has not been initialized, there can be no listeners)
        if ($this->eventEmitter) {
            $this->eventEmitter->emit($event, ...$args);
        }
    }

    /**
     * Get the inner event emitter instance
     */
    final function getEventEmitter(): EventEmitterInterface
    {
        return $this->eventEmitter ?? ($this->eventEmitter = $this->createEventEmitter());
    }

    /**
     * Create the inner event emitter instance
     */
    protected function createEventEmitter(): EventEmitterInterface
    {
        return new EventEmitter();
    }
}
