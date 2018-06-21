<?php declare(strict_types=1);

namespace Kuria\Event;

class EventEmitter implements EventEmitterInterface
{
    /** @var EventListener[][]|null event => EventListener[] */
    private $listeners;

    /** @var array|null event => true (sorted) or int (common priority) */
    private $listenerSortStatus;

    function hasCallback(string $event, $callback): bool
    {
        return $this->findCallback($event, $callback) !== null;
    }

    function hasListener(EventListener $listener): bool
    {
        return $this->findListener($listener) !== null;
    }

    function hasListeners(?string $event = null): bool
    {
        if ($event === null) {
            return !empty($this->listeners);
        }

        return isset($this->listeners[$event]) || isset($this->listeners[static::ANY_EVENT]);
    }

    function getListeners(?string $event = null): array
    {
        if ($this->listeners) {
            if ($event !== null) {
                // single event
                if (isset($this->listeners[$event])) {
                    // sort first if not done yet
                    if (!isset($this->listenerSortStatus[$event])) {
                        $this->sortListeners($event);
                    }

                    return $this->listeners[$event];
                }
            } else {
                // all events

                // ensure all listeners are sorted
                foreach (array_keys($this->listeners) as $event) {
                    if (!isset($this->listenerSortStatus[$event])) {
                        $this->sortListeners($event);
                    }
                }

                return $this->listeners;
            }
        }

        return [];
    }

    function on(string $event, $callback, int $priority = 0): void
    {
        $this->addListener(new EventListener($event, $callback, $priority));
    }

    function off(string $event, $callback): bool
    {
        $index = $this->findCallback($event, $callback);

        if ($index !== null) {
            $this->removeListenerByIndex($event, $index);

            return true;
        }

        return false;
    }

    function addListener(EventListener $listener): void
    {
        // set or append listener
        if (!isset($this->listeners[$listener->event])) {
            // set first listener and common priority
            $this->listeners[$listener->event] = [$listener];
            $this->listenerSortStatus[$listener->event] = $listener->priority;
        } else {
            // append listener
            $this->listeners[$listener->event][] = $listener;

            // reset sort status for the event if common priority does not match
            if (
                isset($this->listenerSortStatus[$listener->event])
                && $this->listenerSortStatus[$listener->event] !== $listener->priority
            ) {
                unset($this->listenerSortStatus[$listener->event]);
            }
        }
    }

    function removeListener(EventListener $listener): bool
    {
        $index = $this->findListener($listener);

        if ($index !== null) {
            $this->removeListenerByIndex($listener->event, $index);

            return true;
        }

        return false;
    }

    function clearListeners(?string $event = null): void
    {
        if ($event !== null) {
            // single event
            unset(
                $this->listeners[$event],
                $this->listenerSortStatus[$event]
            );
        } else {
            // all events
            $this->listeners = null;
            $this->listenerSortStatus = null;
        }
    }

    function emit(string $event, ...$args): void
    {
        // invoke global listeners, then specific ones
        foreach ([static::ANY_EVENT, $event] as $pass => $current) {
            if (isset($this->listeners[$current])) {
                // sort first if not done yet
                if (!isset($this->listenerSortStatus[$current])) {
                    $this->sortListeners($current);
                }

                // iterate
                foreach ($this->listeners[$current] as $index => $listener) {
                    if (
                        ($pass === 0
                            ? ($listener->callback)($event, ...$args)
                            : ($listener->callback)(...$args)
                        ) === false
                    ) {
                        break 2;
                    }
                }
            }
        }
    }

    private function findCallback(string $event, $callback): ?int
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $index => $listener) {
                if ($callback === $listener->callback) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function findListener(EventListener $listenerToFind): ?int
    {
        if (isset($this->listeners[$listenerToFind->event])) {
            foreach ($this->listeners[$listenerToFind->event] as $index => $listener) {
                if ($listenerToFind === $listener) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function removeListenerByIndex(string $event, int $index): void
    {
        array_splice($this->listeners[$event], $index, 1);

        if (empty($this->listeners[$event])) {
            unset($this->listeners[$event], $this->listenerSortStatus[$event]);
        }
    }

    private function sortListeners(string $event): void
    {
        /** @var mixed $sortStatus */
        $sortStatus = null;

        usort($this->listeners[$event], function (EventListener $a, EventListener $b) use (&$sortStatus) {
            $comparison = $b->priority <=> $a->priority;

            // determine common priority
            if ($sortStatus !== true) {
                if ($comparison === 0 && ($sortStatus === null || $sortStatus === $a->priority)) {
                    $sortStatus = $a->priority;
                } else {
                    $sortStatus = true;
                }
            }

            return $comparison;
        });

        $this->listenerSortStatus[$event] = $sortStatus ?? true;
    }
}
