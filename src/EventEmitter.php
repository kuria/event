<?php

namespace Kuria\Event;

/**
 * Event emitter
 *
 * @author ShiraNai7 <shira.cz>
 */
class EventEmitter extends Observable implements EventEmitterInterface
{
    public function emit($eventName, EventInterface $event = null)
    {
        // prepare the event
        if (null === $event) {
            $event = new Event();
        }
        $event->setName($eventName);

        // notify the observers
        if (isset($this->observerMap[$eventName])) {
            $this->notifyObservers($event);
        }

        return $event;
    }
}
