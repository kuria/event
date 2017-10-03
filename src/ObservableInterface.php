<?php declare(strict_types=1);

namespace Kuria\Event;

interface ObservableInterface
{
    /** Matches all events (dispatched before the actual event) */
    const ANY_EVENT = '*';

    /**
     * Register a callback
     */
    function on(string $event, $callback, int $priority = 0): void;

    /**
     * Unregister a callback
     */
    function off(string $event, $callback): bool;

    /**
     * Register an event listener
     */
    function addListener(EventListener $listener): void;

    /**
     * Unregister an event listener
     */
    function removeListener(EventListener $listener): bool;

    /**
     * Emit an event
     */
    function emit(string $event, ...$args): void;
}
