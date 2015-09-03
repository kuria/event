<?php

namespace Kuria\Event;

/**
 * Event emitter interface
 *
 * @author ShiraNai7 <shira.cz>
 */
interface EventEmitterInterface
{
    /** Global event (emitted before any other event) */
    const ANY_EVENT = '*';
    
    /**
     * See at least one event listener exists
     *
     *  - if an event name is given, checks listeners of that event only
     *  - if no event name is given, checks listeners of any event
     *
     * @param string $event
     * @param bool   $checkGlobal if checking a specific event, check existence of any global listeners too 1/0
     * @return bool
     */
    public function hasListeners($event = null, $checkGlobal = true);

    /**
     * Get registered event listeners
     *
     *  - if an event name is given, returns a list listeners of that event only
     *  - if no event name is given, returns a multi-dimensional array where
     *    the keys are event names and the values are lists of callbacks
     *  - the returned callback lists are sorted by priority
     *
     * @param string|null $event
     * @return array
     */
    public function getListeners($event = null);

    /**
     * Register an event listener
     *
     * @param string   $event
     * @param callable $listener
     * @param int      $priority
     * @return static
     */
    public function on($event, $listener, $priority = 0);

    /**
     * Register an event listener that will be invoked only once,
     * after which it is removed.
     *
     * @param string   $event
     * @param callable $listener
     * @param int      $priority
     * @return static
     */
    public function once($event, $listener, $priority = 0);

    /**
     * Unregister an event listener
     *
     * @param string   $event
     * @param callable $listener
     * @return static
     */
    public function removeListener($event, $listener);

    /**
     * Clear event listeners
     *
     *  - if an event name is given, clears listeners of that event only.
     *
     * @param string|null $event
     * @return static
     */
    public function clearListeners($event = null);

    /**
     * Register an event subscriber
     *
     * @param EventSubscriberInterface $subscriber
     * @return static
     */
    public function subscribe(EventSubscriberInterface $subscriber);

    /**
     * Unregister an event subscriber
     *
     * @param EventSubscriberInterface $subscriber
     * @return static
     */
    public function unsubscribe(EventSubscriberInterface $subscriber);

    /**
     * Emit an event
     *
     * Use {@see emitArray()} if you need to preserve references.
     *
     * @param string $event
     * @param mixed  $arg1,...
     */
    public function emit($event);

    /**
     * Emit an event using an array as the argument list
     *
     * Useful for variable arguments and passing references.
     *
     * @param string $event
     * @param array  $args
     */
    public function emitArray($event, array $args);
}
