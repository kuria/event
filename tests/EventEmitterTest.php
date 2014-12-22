<?php

namespace Kuria\Event;

class EventEmitterTest extends ObservableTest
{
    protected function getObservableClass()
    {
        return __NAMESPACE__ . '\EventEmitter';
    }

    protected function emitEvent($observable, $eventName)
    {
        $observable->emit($eventName);
    }
}
