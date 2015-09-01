<?php

namespace Kuria\Event;

/**
 * Event emitter interface
 *
 * @author ShiraNai7 <shira.cz>
 */
interface EventEmitterInterface
{
    /**
     * See if any listener exists
     *
     * Shortcut for: $emitter->hasGlobalListeners() || $emitter->hasListener($event)
     *
     * - if any global listeners exist, returns TRUE
     * - if no global listeners exist, behaves the same way as {@see hasListener()}
     *
     * @param string|null $event
     * @return bool
     */
    public function hasAnyListeners($event = null);

    /**
     * See at least one event listener exists
     *
     *  - if an event name is given, checks listeners of that event only
     *  - if no event name is given, checks listeners of any event
     *  - does not check global listeners, use {@see hasGlobalListeners()} for that
     *
     * @param string $event
     * @return bool
     */
    public function hasListener($event = null);

    /**
     * See if at least one global event listener is registered
     *
     * @return bool
     */
    public function hasGlobalListeners();

    /**
     * Get registered event listeners
     *
     *  - if an event name is given, returns a list listeners of that event only
     *  - if no event name is given, returns a multi-dimensional array where
     *    the keys are event names and the values are lists of callbacks
     *  - the returned callback lists are sorted by priority
     *  - does not include global listeners, use {@see getGlobalListeners()}
     *    if you need to get those
     *
     * @param string|null $event
     * @return array
     */
    public function getListeners($event = null);

    /**
     * Get registered global event listeners
     *
     * Returns a list of callbacks sorted by priority.
     *
     * @return callable[]
     */
    public function getGlobalListeners();

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
     * Register a global event listener that will be invoked for any event
     *
     * Global event listeners are called before any other listeners.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public function onAny($listener, $priority = 0);

    /**
     * Unregister an event listener
     *
     * @param string   $event
     * @param callable $listener
     * @return static
     */
    public function removeListener($event, $listener);

    /**
     * Unregister a global event listener
     *
     * @param callable $listener
     * @return static
     */
    public function removeGlobalListener($listener);

    /**
     * Clear event listeners
     *
     *  - if an event name is given, clears listeners of that event only.
     *  - does not clear global event listeners, use {@see clearGlobalListeners()}
     *    if you need to clear those
     *
     * @param string|null $event
     * @return static
     */
    public function clearListeners($event = null);

    /**
     * Unregister all global event listeners
     *
     * @return static
     */
    public function clearGlobalListeners();

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
