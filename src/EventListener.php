<?php declare(strict_types=1);

namespace Kuria\Event;

/**
 * Event listener definition
 */
class EventListener
{
    /** @var string read-only */
    public $event;

    /** @var callable read-only */
    public $callback;

    /** @var int read-only */
    public $priority;

    function __construct(string $event, $callback, int $priority = 0)
    {
        $this->event = $event;
        $this->callback = $callback;
        $this->priority = $priority;
    }
}
