<?php

namespace Kuria\Event;

/**
 * @requires PHP 5.4.0
 */
class EventEmitterTraitTest extends EventEmitterTest
{
    protected function createEventEmitter()
    {
        require_once __DIR__ . '/TestEventEmitterFromTrait.php';

        return new TestEventEmitterFromTrait();
    }
}
