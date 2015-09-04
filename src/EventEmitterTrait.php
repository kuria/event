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

    public function hasListeners($event = null, $checkGlobal = true)
    {
        return null !== $event
            ? isset($this->entries[$event]) || $checkGlobal && isset($this->entries[self::ANY_EVENT])
            : !empty($this->entries)
        ;
    }

    public function getListeners($event = null)
    {
        if (null !== $this->entries) {
            if (null !== $event) {
                if (isset($this->entries[$event])) {
                    if (isset($this->unsortedEventsMap[$event])) {
                        $this->sortListeners($event);
                    }

                    return $this->listeners[$event];
                }
            } else {

                if ($this->unsortedEventsMap) {
                    foreach ($this->unsortedEventsMap as $event => $_) {
                        $this->sortListeners($event);
                    }
                    $this->unsortedEventsMap = null;
                }

                return $this->listeners;
            }
        }

        return array();
    }

    public function on($event, $listener, $priority = 0)
    {
        $this->registerListener($event, $listener, $priority, false);

        return $this;
    }

    public function once($event, $listener, $priority = 0)
    {
        $this->registerListener($event, $listener, $priority, true);

        return $this;
    }

    public function removeListener($event, $listener)
    {
        if (isset($this->entries[$event])) {
            $this->unregisterListener($event, $listener);
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
        $args = null;
        $toRemove = null;

        foreach (array(self::ANY_EVENT, $event) as $pass => $current) {
            if (isset($this->entries[$current])) {
                if (0 === $pass) {
                    $args = func_get_args();
                } else {
                    $args = array_slice(null === $args ? func_get_args() : $args, 1);
                }

                if (isset($this->unsortedEventsMap[$current])) {
                    $this->sortListeners($current);
                    unset($this->unsortedEventsMap[$current]);
                }

                foreach ($this->listeners[$current] as $index => $callback) {
                    if (isset($this->entries[$current][$index]['once'])) {
                        $toRemove[$current][] = $index;
                    }

                    if (false === call_user_func_array($callback, $args)) {
                        return;
                    }
                }
            }
        }

        if ($toRemove) {
            foreach ($toRemove as $current => $indexes) {
                for ($i = sizeof($indexes) - 1; $i >= 0; --$i) {
                    $this->unregisterListener($current, $indexes[$i], true);
                }
            }
        }
    }

    public function emitArray($event, array $args)
    {
        $toRemove = null;

        foreach (array(self::ANY_EVENT, $event) as $pass => $current) {
            if (isset($this->entries[$current])) {
                if (0 === $pass) {
                    $currentArgs = array_merge(array($event), $args);
                } else {
                    $currentArgs = $args;
                }

                if (isset($this->unsortedEventsMap[$current])) {
                    $this->sortListeners($current);
                    unset($this->unsortedEventsMap[$current]);
                }

                foreach ($this->listeners[$current] as $index => $callback) {
                    if (isset($this->entries[$current][$index]['once'])) {
                        $toRemove[$current][] = $index;
                    }

                    if (false === call_user_func_array($callback, $currentArgs)) {
                        return;
                    }
                }
            }
        }

        if ($toRemove) {
            foreach ($toRemove as $current => $indexes) {
                for ($i = sizeof($indexes) - 1; $i >= 0; --$i) {
                    $this->unregisterListener($current, $indexes[$i], true);
                }
            }
        }
    }

    private function registerListener($event, $listener, $priority, $once)
    {
        if (!isset($this->entries[$event])) {
            $this->entries[$event] = array();
            $this->listeners[$event] = array($listener);
        } else {
            $this->unsortedEventsMap[$event] = true;
        }

        $entry = array(
            'listener' => $listener,
            'priority' => $priority,
        );

        if ($once) {
            $entry['once'] = true;
        }

        $this->entries[$event][] = $entry;
    }

    private function unregisterListener($event, $listener, $byIndex = false)
    {
        if ($byIndex) {
            $index = $listener;
            $found = isset($this->entries[$event][$index]);
        } else {
            $found = false;
            foreach ($this->entries[$event] as $index => $entry) {
                if ($entry['listener'] === $listener) {
                    $found = true;
                    break;
                }
            }
        }


        if ($found) {
            array_splice($this->entries[$event], $index, 1);

            if ($this->entries[$event]) {
                if (isset($this->listeners[$event])) {
                    array_splice($this->listeners[$event], $index, 1);
                }
            } else {
                unset(
                    $this->entries[$event],
                    $this->listeners[$event],
                    $this->unsortedEventsMap[$event]
                );
            }
        }

        return $found;
    }

    private function sortListeners($event)
    {
        $this->listeners[$event] = array();

        usort($this->entries[$event], function ($a, $b) {
            return $a['priority'] > $b['priority']
                ? -1
                : ($a['priority'] < $b['priority'] ? 1 : 0)
            ;
        });

        foreach ($this->entries[$event] as $entry) {
            $this->listeners[$event][] = $entry['listener'];
        }

        unset($this->unsortedEventsMap[$event]);
    }
}
