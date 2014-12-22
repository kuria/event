<?php

namespace Kuria\Event;

/**
 * Event emitter interface
 *
 * @author ShiraNai7 <shira.cz>
 */
interface EventEmitterInterface extends ObservableInterface
{
    /**
     * Emit an event
     *
     * @param string              $eventName
     * @param EventInterface|null $event
     * @return EventInterface the emitted event
     */
    public function emit($eventName, EventInterface $event = null);
}
