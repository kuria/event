<?php declare(strict_types=1);

namespace Kuria\Event;

abstract class EventSubscriber implements EventSubscriberInterface
{
    /** @var EventListener[]|null */
    private $listeners;

    function subscribeTo(ObservableInterface $emitter): void
    {
        foreach ($this->cachedListeners() as $listener) {
            $emitter->addListener($listener);
        }
    }

    function unsubscribeFrom(ObservableInterface $emitter): void
    {
        foreach ($this->cachedListeners() as $listener) {
            $emitter->removeListener($listener);
        }
    }

    /**
     * Get listeners
     *
     * Result of this function is cached internally.
     *
     * @see EventSubscriber::listen()
     * @return EventListener[]
     */
    abstract protected function getListeners(): array;

    /**
     * Get listeners and cache them internally for later use
     *
     * @return EventListener[]
     */
    protected function cachedListeners(): array
    {
        return $this->listeners ?? ($this->listeners = $this->getListeners());
    }

    /**
     * Create an event listener and map it to a method of this class
     */
    protected function listen(string $event, string $methodName, int $priority = 0): EventListener
    {
        return new EventListener($event, [$this, $methodName], $priority);
    }
}
