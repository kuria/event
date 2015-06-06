<?php

namespace Kuria\Event;

/**
 * Event subscriber interface
 *
 * @author ShiraNai7 <shira.cz>
 */
interface EventSubscriberInterface
{
    /**
     * Subscribe to the given event emitter
     *
     * @param EventEmitter $emitter
     */
    public function subscribeTo(EventEmitterInterface $emitter);

    /**
     * Unsubscribe from the given event emitter
     *
     * @param EventEmitter $emitter
     */
    public function unsubscribeFrom(EventEmitterInterface $emitter);
}
