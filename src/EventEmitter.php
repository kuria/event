<?php

namespace Kuria\Event;

/**
 * Event emitter
 *
 * @author ShiraNai7 <shira.cz>
 */
class EventEmitter implements EventEmitterInterface
{
    /*
     * Implementation notes:
     *
     * - each listener is stored as an entry in the respective array
     * - ordered callback lists are constructed only when needed and cached
     *   to keep processing to a reasonable minimum
     */

    /** @var array event => callable[]|null */
    private $listeners;
    /** @var array event => array[] */
    private $entries;
    /** @var array event => true */
    private $unsortedEventsMap;
    /** @var callable[] */
    private $globalListeners;
    /** @var array[] */
    private $globalEntries;

    public function hasAnyListeners()
    {
        return $this->entries || $this->globalEntries;
    }

    public function hasListener($event = null)
    {
        return null !== $event
            ? isset($this->entries[$event])
            : !empty($this->entries)
        ;
    }

    public function hasGlobalListeners()
    {
        return !empty($this->globalEntries);
    }

    public function getListeners($event = null)
    {
        if (null !== $this->entries) {
            if (null !== $event) {
                // single event
                if (isset($this->entries[$event])) {
                    // sort first if not done yet
                    if (isset($this->unsortedEventsMap[$event])) {
                        $this->sortListeners($this->listeners[$event], $this->entries[$event]);
                        unset($this->unsortedEventsMap[$event]);
                    }

                    return $this->listeners[$event];
                }
            } else {
                // all events

                // sort first if not done yet
                if ($this->unsortedEventsMap) {
                    foreach ($this->unsortedEventsMap as $event => $_) {
                        $this->sortListeners($this->listeners[$event], $this->entries[$event]);
                    }
                    $this->unsortedEventsMap = null;
                }

                return $this->listeners;
            }
        }

        return array();
    }

    public function getGlobalListeners()
    {
        if ($this->globalEntries) {
            // sort first if not done yet
            if (null === $this->globalListeners) {
                $this->sortListeners($this->globalListeners, $this->globalEntries);
            }

            return $this->globalListeners;
        }

        return array();
    }

    public function on($event, $listener, $priority = 0)
    {
        $listenersReset = false;

        $this->registerListener(
            $listener,
            $priority,
            false,
            $this->listeners[$event],
            $this->entries[$event],
            $listenersReset
        );

        if ($listenersReset) {
            $this->unsortedEventsMap[$event] = true;
        }

        return $this;
    }

    public function once($event, $listener, $priority = 0)
    {
        $listenersReset = false;

        $this->registerListener(
            $listener,
            $priority,
            true,
            $this->listeners[$event],
            $this->entries[$event],
            $listenersReset
        );

        if ($listenersReset) {
            $this->unsortedEventsMap[$event] = true;
        }

        return $this;
    }

    public function onAny($listener, $priority = 0)
    {
        $this->registerListener(
            $listener,
            $priority,
            null,
            $this->globalListeners,
            $this->globalEntries
        );

        return $this;
    }

    public function removeListener($event, $listener)
    {
        if (isset($this->entries[$event])) {
            if ($this->unregisterListener($listener, $this->listeners[$event], $this->entries[$event])) {
                // cleanup
                if (null === $this->entries[$event]) {
                    unset(
                        $this->entries[$event],
                        $this->listeners[$event],
                        $this->unsortedEventsMap[$event]
                    );
                }
            }
        }

        return $this;
    }

    public function removeGlobalListener($listener)
    {
        if ($this->globalEntries) {
            $this->unregisterListener($listener, $this->globalListeners, $this->globalEntries);
        }

        return $this;
    }

    public function clearListeners($event = null)
    {
        if (null !== $event) {
            // single event
            unset(
                $this->listeners[$event],
                $this->entries[$event],
                $this->unsortedEventsMap[$event]
            );
        } else {
            // all events
            $this->listeners = null;
            $this->entries = null;
            $this->unsortedEventsMap = null;
        }

        return $this;
    }

    public function clearGlobalListeners()
    {
        $this->globalListeners = null;
        $this->globalEntries = null;

        return $this;
    }

    public function subscribe(EventSubscriberInterface $subscriber)
    {
        $subscriber->subscribeTo($this);

        return $this;
    }

    public function unsubscribe(EventSubscriberInterface $subscriber)
    {
        $subscriber->unsubscribeFrom($this);

        return $this;
    }

    public function emit($event)
    {
        // prepare arguments
        if ($this->globalEntries || isset($this->entries[$event])) {
            $args = func_get_args();
        }

        // call global listeners
        if ($this->globalEntries) {
            // sort first if not done yet
            if (null === $this->globalListeners) {
                $this->sortListeners($this->globalListeners, $this->globalEntries);
            }

            // iterate
            foreach ($this->globalListeners as $callback) {
                if (false === call_user_func_array($callback, $args)) {
                    // propagation stopped
                    return;
                }
            }
        }

        // call listeners
        if (isset($this->entries[$event])) {
            // remove event name
            $args = array_slice($args, 1);

            // sort first if not done yet
            if (isset($this->unsortedEventsMap[$event])) {
                $this->sortListeners($this->listeners[$event], $this->entries[$event]);
                unset($this->unsortedEventsMap[$event]);
            }

            // iterate
            $indexesToRemove = null;
            foreach ($this->listeners[$event] as $index => $callback) {
                // schedule for removal if the once flag is present
                if (isset($this->entries[$event][$index]['once'])) {
                    $indexesToRemove[] = $index;
                }

                if (false === call_user_func_array($callback, $args)) {
                    // propagation stopped
                    return;
                }
            }

            // handle scheduled removals
            if ($indexesToRemove) {
                $this->unregisterListenersAtIndexes($indexesToRemove, $this->listeners[$event], $this->entries[$event]);

                // cleanup
                if (null === $this->entries[$event]) {
                    unset(
                        $this->entries[$event],
                        $this->listeners[$event]
                    );
                }
            }
        }
    }

    public function emitArray($event, array $args)
    {
        // the code below is almost identical to emit(), but is copy pasted
        // for performance reaons (thousands of events may be emitted)

        // call global listeners
        if ($this->globalEntries) {
            // prepend event name
            $globalArgs = array_merge(array($event), $args);

            // sort first if not done yet
            if (null === $this->globalListeners) {
                $this->sortListeners($this->globalListeners, $this->globalEntries);
            }

            // iterate
            foreach ($this->globalListeners as $callback) {
                if (false === call_user_func_array($callback, $globalArgs)) {
                    // propagation stopped
                    return;
                }
            }

            $globalArgs = null;
        }

        // call listeners
        if (isset($this->entries[$event])) {
            // sort first if not done yet
            if (isset($this->unsortedEventsMap[$event])) {
                $this->sortListeners($this->listeners[$event], $this->entries[$event]);
                unset($this->unsortedEventsMap[$event]);
            }

            // iterate
            $indexesToRemove = null;
            foreach ($this->listeners[$event] as $index => $callback) {
                // schedule for removal if the once flag is present
                if (isset($this->entries[$event][$index]['once'])) {
                    $indexesToRemove[] = $index;
                }

                if (false === call_user_func_array($callback, $args)) {
                    // propagation stopped
                    return;
                }
            }

            // handle scheduled removals
            if ($indexesToRemove) {
                $this->unregisterListenersAtIndexes($indexesToRemove, $this->listeners[$event], $this->entries[$event]);

                // cleanup
                if (null === $this->entries[$event]) {
                    unset(
                        $this->entries[$event],
                        $this->listeners[$event]
                    );
                }
            }
        }
    }

    /**
     * @param callable   $listener
     * @param int        $priority
     * @param bool|null  $once
     * @param array|null $listeners
     * @param array|null $entries
     * @param bool       $listenersReset
     */
    private function registerListener($listener, $priority, $once, &$listeners, &$entries, &$listenersReset = null)
    {
        if (null === $entries) {
            $entries = array();
            $listeners = array($listener);
        } else {
            $listeners = null;
            $listenersReset = true;
        }

        $entry = array(
            'listener' => $listener,
            'priority' => $priority,
        );

        if ($once) {
            $entry['once'] = true;
        }

        $entries[] = $entry;
    }

    /**
     * @param callable $listener
     * @param array    $listeners
     * @param array    $entries
     * @return bool
     */
    private function unregisterListener($listener, array &$listeners, array &$entries)
    {
        $found = false;
        foreach ($entries as $index => $entry) {
            if ($entry['listener'] === $listener) {
                $found = true;
                break;
            }
        }

        if ($found) {
            array_splice($entries, $index, 1);

            if (!$entries) {
                $entries = null;
                $listeners = null;
            } elseif (null !== $listeners) {
                array_splice($listeners, $index, 1);
            }
        }

        return $found;
    }

    /**
     * @param int[] $indexes   0-based list ordered low to high (!)
     * @param array $listeners
     * @param array $entries
     */
    private function unregisterListenersAtIndexes(array $indexes, array &$listeners, array &$entries)
    {
        for ($i = sizeof($indexes) - 1; $i >= 0; --$i) {
            array_splice($listeners, $indexes[$i], 1);
            array_splice($entries, $indexes[$i], 1);
        }
        
        if (!$entries) {
            $entries = null;
            $listeners = null;
        }
    }

    /**
     * @param array|null $listeners
     * @param array      $entries
     */
    private function sortListeners(&$listeners, array &$entries)
    {
        $listeners = array();

        usort($entries, function ($a, $b) {
            return $a['priority'] > $b['priority']
                ? -1
                : ($a['priority'] < $b['priority'] ? 1 : 0)
            ;
        });

        foreach ($entries as $entry) {
            $listeners[] = $entry['listener'];
        }
    }
}
