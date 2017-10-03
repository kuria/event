<?php declare(strict_types=1);

namespace Kuria\Event;

abstract class Observable implements ObservableInterface, EventEmitterPropInterface
{
    use ObservableTrait;
}
