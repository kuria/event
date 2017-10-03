<?php declare(strict_types=1);

namespace Kuria\Event;

interface EventEmitterInterface extends ObservableInterface
{
    /**
     * See if a specific callback is registered for the given event
     */
    function hasCallback(string $event, $callback): bool;

    /**
     * See if a specific listener instance is registered
     */
    function hasListener(EventListener $listener): bool;

    /**
     * See if at least one event listener exists
     *
     * - if an event name is given, checks listeners of that event only
     * - if no event name is given, checks listeners of any event
     */
    function hasListeners(?string $event = null): bool;

    /**
     * Get registered event listeners
     *
     * - if an event name is given, returns a list listeners of that event only
     * - if no event name is given, returns a multi-dimensional array where
     *    the keys are event names and the values are lists of listeners
     * - the returned listener lists are sorted by priority
     *
     * @return EventListener[]|EventListener[][]
     */
    function getListeners(?string $event = null): array;

    /**
     * Clear event listeners
     *
     * If an event name is given, clears listeners of that event only.
     */
    function clearListeners(?string $event = null): void;
}
