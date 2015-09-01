<?php

namespace Kuria\Event;

/**
 * Event subscriber
 *
 * Maps its methods as listeners for specific events.
 *
 * @author ShiraNai7 <shira.cz>
 */
abstract class EventSubscriber implements EventSubscriberInterface
{
    /**
     * Get event map
     *
     * The return value must be an associative in the following format:
     *
     *  array(
     *      // single method
     *      'foo' => 'onFoo',
     *
     *      // single method with priority
     *      'bar' => array('onBar', 10),
     *
     *      // multiple methods (indexed list; each entry must be an array)
     *      'baz' => array(
     *          array('onBazA'),
     *          array('onBazB', 5),
     *      ),
     *
     *      // ...
     *  )
     *
     * @return array
     */
    abstract protected function getEvents();

    public function subscribeTo(EventEmitterInterface $emitter)
    {
        foreach ($this->getEvents() as $event => $params) {
            if (is_string($params)) {
                $emitter->on($event, array($this, $params));
            } elseif (is_string($params[0])) {
                $emitter->on($event, array($this, $params[0]), $params[1]);
            } else {
                foreach ($params as $listener) {
                    $emitter->on($event, array($this, $listener[0]), isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }

    public function unsubscribeFrom(EventEmitterInterface $emitter)
    {
        foreach ($this->getEvents() as $event => $params) {
            if (is_string($params)) {
                $emitter->removeListener($event, array($this, $params));
            } elseif (is_string($params[0])) {
                $emitter->removeListener($event, array($this, $params[0]));
            } else {
                foreach ($params as $listener) {
                    $emitter->removeListener($event, array($this, $listener[0]));
                }
            }
        }
    }
}
