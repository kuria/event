<?php

namespace Kuria\Event;

/**
 * Observable interface
 *
 * @author ShiraNai7 <shira.cz>
 */
interface ObservableInterface
{
    /**
     * Register subscriber
     *
     * @param EventSubscriberInterface the subscriber instance
     * @return static
     */
    public function addSubscriber(EventSubscriberInterface $subscriber);

    /**
     * Remove subscriber
     *
     * @param EventSubscriberInterface the subscriber instance
     * @return static
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber);

    /**
     * Register listener
     *
     * @param string   $eventName
     * @param callable $callback  callback(EventInterface, ObservableInterface): void
     * @param int      $priority
     * @return static
     */
    public function addListener($eventName, $callback, $priority = 0);

    /**
     * Remove listener
     *
     * @param string   $eventName
     * @param callable $callback
     * @return static
     */
    public function removeListener($eventName, $callback);

    /**
     * See if specified event has at least one observer
     *
     * @param string $eventName
     * @return bool
     */
    public function hasObservers($eventName);

    /**
     * Clear all or event-specific observers
     *
     * @param string|null $eventName event name or null (= all)
     */
    public function clearObservers($eventName = null);

    /**
     * Notify observers of the given event
     *
     * @param EventInterface           $event
     * @param ObservableInterface|null $observable observable to pass to the obserers (null = $this)
     * @throws \LogicException if the event cannot be sent
     * @return EventInterface the sent event
     */
    public function notifyObservers(EventInterface $event, ObservableInterface $observable = null);
}
