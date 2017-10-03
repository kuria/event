<?php declare(strict_types=1);

namespace Kuria\Event;

interface EventEmitterPropInterface
{
    /**
     * Get the inner event emitter instance
     */
    function getEventEmitter(): EventEmitterInterface;
}
