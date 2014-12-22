<?php

namespace Kuria\Event;

/**
 * Observable object
 *
 * @author ShiraNai7 <shira.cz>
 */
class Observable implements ObservableInterface
{
    /** Observer type - subscriber */
    const OBSERVER_SUBSCRIBER = 0;
    /** Observer type - listener */
    const OBSERVER_LISTENER = 1;

    /**
     * @var array
     * 
     * Map of the registered observers.
     * 
     * event_name => array(
     *      array(
     *          0 => type (Observable::OBSERVER_SUBSCRIBER or OBSERVER_LISTENER)
     *          1 => priority (integer)
     *          2 => SubscriberInterface or a callable (depends on type)
     *          3 => method name (string; only for subscribers)
     *      ),
     *      ...
     * )
     */
    protected $observerMap = array();

    /**
     * @var array
     * 
     * Map of events and their sorted state.
     * 
     * event_name => sorted 1/0
     */
    protected $sortMap = array();

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getEvents() as $eventName => $eventData) {
            // process event data
            if (is_array($eventData)) {
                $method = (string) $eventData[0];
                $priority = isset($eventData[1]) ? (int) $eventData[1] : 0;
            } else {
                $method = (string) $eventData;
                $priority = 0;
            }

            // add to the map
            $this->sortMap[$eventName] = false;
            $this->observerMap[$eventName][] = array(
                self::OBSERVER_SUBSCRIBER,
                $priority,
                $subscriber,
                $method,
            );
        }

        return $this;
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        // iterate through the observer map
        foreach ($this->observerMap as $eventName => &$listeners) {
            foreach ($listeners as $listenerIndex => $listenerEntry) {
                // match type and instance
                if (self::OBSERVER_SUBSCRIBER === $listenerEntry[0] && $subscriber === $listenerEntry[2]) {
                    unset($listeners[$listenerIndex]);
                }

                // clean-up
                if (empty($listeners)) {
                    unset(
                        $this->observerMap[$eventName],
                        $this->sortMap[$eventName]
                    );
                    break;
                }
            }
        }

        return $this;
    }

    public function addListener($eventName, $callback, $priority = 0)
    {
        // add to the map
        $this->sortMap[$eventName] = false;
        $this->observerMap[$eventName][] = array(
            self::OBSERVER_LISTENER,
            (int) $priority,
            $callback,
        );

        return $this;
    }

    public function removeListener($eventName, $callback)
    {
        // iterate through the observer map
        foreach ($this->observerMap as $eventName => &$listeners) {
            foreach ($listeners as $listenerIndex => $listenerEntry) {
                // match type and callback
                if (self::OBSERVER_LISTENER === $listenerEntry[0] && $callback === $listenerEntry[2]) {
                    // remove
                    unset($listeners[$listenerIndex]);

                    // clean-up
                    if (empty($listeners)) {
                        unset(
                            $this->observerMap[$eventName],
                            $this->sortMap[$eventName]
                        );
                        break;
                    }
                }
            }
        }

        return $this;
    }

    public function hasObservers($eventName)
    {
        return isset($this->observerMap[$eventName]);
    }

    public function clearObservers($eventName = null)
    {
        // clear
        if (null === $eventName) {
            // all
            $this->observerMap = array();
            $this->sortMap = array();
        } else {
            // specific
            unset(
                $this->observerMap[$eventName],
                $this->sortMap[$eventName]
            );
        }
    }

    public function notifyObservers(EventInterface $event, ObservableInterface $observable = null)
    {
        $eventName = $event->getName();

        if (null === $eventName) {
            throw new \LogicException('Cannot send an unnamed event');
        }

        if (isset($this->observerMap[$eventName])) {
            // there is at least one observer
            // mark the event as handled
            $event->setHandled(true);

            // sort event map?
            if (!$this->sortMap[$eventName]) {
                $this->sortObserverMap($eventName);
                $this->sortMap[$eventName] = true;
            }

            // check observable
            if (null === $observable) {
                $observable = $this;
            }

            // notify
            foreach ($this->observerMap[$eventName] as $eventEntry) {
                // invoke handler
                if (self::OBSERVER_SUBSCRIBER === $eventEntry[0]) {
                    // subscriber
                    $eventEntry[2]->{$eventEntry[3]}($event, $observable);
                } else {
                    // listener
                    call_user_func($eventEntry[2], $event, $observable);
                }

                // check stopped state
                if ($event->isStopped()) {
                    break;
                }
            }
        }

        return $event;
    }

    /**
     * Sort observer map
     *
     * @param string $eventName
     */
    protected function sortObserverMap($eventName)
    {
        usort($this->observerMap[$eventName], function($a, $b) {
            if ($a[1] === $b[1]) {
                // same priority
                return 0;
            }

            if ($a[1] > $b[1]) {
                // a has greater priority than b
                return -1;
            }

            // a has lesser priority than b
            return 1;
        });
    }
}
