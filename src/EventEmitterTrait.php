<?php

namespace Kuria\Event;

/**
 * Event emitter trait
 *
 * Implements EventEmitterInterface
 *
 * The code here is copy-pasted from EventEmitter to maintain compatibility with PHP 5.3
 *
 * @author ShiraNai7 <shira.cz>
 */
trait EventEmitterTrait
{
    private $listeners;
    private $entries;
    private $unsortedEventsMap;
    private $globalListeners;
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
                if (isset($this->entries[$event])) {
                    if (isset($this->unsortedEventsMap[$event])) {
                        $this->sortListeners($this->listeners[$event], $this->entries[$event]);
                        unset($this->unsortedEventsMap[$event]);
                    }

                    return $this->listeners[$event];
                }
            } else {
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
            unset(
                $this->listeners[$event],
                $this->entries[$event],
                $this->unsortedEventsMap[$event]
            );
        } else {
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
        if ($this->globalEntries || isset($this->entries[$event])) {
            $args = func_get_args();
        }

        if ($this->globalEntries) {
            if (null === $this->globalListeners) {
                $this->sortListeners($this->globalListeners, $this->globalEntries);
            }

            foreach ($this->globalListeners as $callback) {
                if (false === call_user_func_array($callback, $args)) {
                    return;
                }
            }
        }

        if (isset($this->entries[$event])) {
            $args = array_slice($args, 1);

            if (isset($this->unsortedEventsMap[$event])) {
                $this->sortListeners($this->listeners[$event], $this->entries[$event]);
                unset($this->unsortedEventsMap[$event]);
            }

            $indexesToRemove = null;
            foreach ($this->listeners[$event] as $index => $callback) {
                if (isset($this->entries[$event][$index]['once'])) {
                    $indexesToRemove[] = $index;
                }

                if (false === call_user_func_array($callback, $args)) {
                    return;
                }
            }

            if ($indexesToRemove) {
                $this->unregisterListenersAtIndexes($indexesToRemove, $this->listeners[$event], $this->entries[$event]);

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
        if ($this->globalEntries) {
            $globalArgs = array_merge(array($event), $args);

            if (null === $this->globalListeners) {
                $this->sortListeners($this->globalListeners, $this->globalEntries);
            }

            foreach ($this->globalListeners as $callback) {
                if (false === call_user_func_array($callback, $globalArgs)) {
                    return;
                }
            }

            $globalArgs = null;
        }

        if (isset($this->entries[$event])) {
            if (isset($this->unsortedEventsMap[$event])) {
                $this->sortListeners($this->listeners[$event], $this->entries[$event]);
                unset($this->unsortedEventsMap[$event]);
            }

            $indexesToRemove = null;
            foreach ($this->listeners[$event] as $index => $callback) {
                if (isset($this->entries[$event][$index]['once'])) {
                    $indexesToRemove[] = $index;
                }

                if (false === call_user_func_array($callback, $args)) {
                    return;
                }
            }

            if ($indexesToRemove) {
                $this->unregisterListenersAtIndexes($indexesToRemove, $this->listeners[$event], $this->entries[$event]);

                if (null === $this->entries[$event]) {
                    unset(
                        $this->entries[$event],
                        $this->listeners[$event]
                    );
                }
            }
        }
    }

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
