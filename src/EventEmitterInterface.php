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
     * Get number of listeners
     *
     * If an event name is given, counts listeners of that event only.
     *
     * @param string $event
     * @return int
     */
    public function getListenerCount($event = null);

    /**
     * See if at least one listener exists
     *
     * If an event name is given, checks listeners of that event only.
     *
     * @param string $event
     * @return bool
     */
    public function hasListener($event = null);

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
     * In case of duplicate listeners, only one will be removed.
     *
     * @param string   $event
     * @param callable $listener
     * @return static
     */
    public function removeListener($event, $listener);

    /**
     * Unregister all event listeners
     *
     * If an event name is given, unregisters listeners of that event only.
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
     * @return bool
     */
    public function emit($event);

    /**
     * Emit an event using an array as the argument list
     *
     * Useful for variable arguments and passing references.
     *
     * @param string $event
     * @param array  $args
     * @return bool
     */
    public function emitArray($event, array $args);
}
