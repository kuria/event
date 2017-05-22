<?php

namespace Kuria\Event;

/**
 * Event emitter
 *
 * @author ShiraNai7 <shira.cz>
 */
class EventEmitter implements EventEmitterInterface
{
    /** @var array event => callable[] */
    private $listeners;
    /** @var array event => array[] */
    private $entries;
    /** @var array event => true */
    private $unsortedEventsMap;

    public function hasListeners($event = null, $checkGlobal = true)
    {
        return null !== $event
            ? isset($this->entries[$event]) || $checkGlobal && isset($this->entries[static::ANY_EVENT])
            : !empty($this->entries);
    }

    public function getListeners($event = null)
    {
        if (null !== $this->entries) {
            if (null !== $event) {
                // single event
                if (isset($this->entries[$event])) {
                    // sort first if not done yet
                    if (isset($this->unsortedEventsMap[$event])) {
                        $this->sortListeners($event);
                    }

                    return $this->listeners[$event];
                }
            } else {
                // all events

                // sort first if not done yet
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

        // invoke global listeners, then specific ones
        foreach (array(static::ANY_EVENT, $event) as $pass => $current) {
            if (isset($this->entries[$current])) {
                // prepare arguments
                if (0 === $pass) {
                    // first pass = any event (args with event name)
                    $args = func_get_args();
                } else {
                    // second pass = specific event (args without event name)
                    $args = array_slice(null === $args ? func_get_args() : $args, 1);
                }

                // sort first if not done yet
                if (isset($this->unsortedEventsMap[$current])) {
                    $this->sortListeners($current);
                    unset($this->unsortedEventsMap[$current]);
                }

                // iterate
                foreach ($this->listeners[$current] as $index => $callback) {
                    // schedule for removal if the once flag is present
                    if (isset($this->entries[$current][$index]['once'])) {
                        $toRemove[$current][] = $index;
                    }

                    if (false === call_user_func_array($callback, $args)) {
                        // propagation stopped
                        return;
                    }
                }
            }
        }

        // handle scheduled removals
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
        // the code below is almost identical to emit(), but is copy pasted
        // for performance reasons (thousands of events may be emitted)

        $toRemove = null;

        // invoke global listeners, then specific ones
        foreach (array(static::ANY_EVENT, $event) as $pass => $current) {
            if (isset($this->entries[$current])) {
                // prepare arguments
                if (0 === $pass) {
                    // first pass = any event (args with event name)
                    $currentArgs = array_merge(array($event), $args);
                } else {
                    // second pass = specific event (args without event name)
                    $currentArgs = $args;
                }

                // sort first if not done yet
                if (isset($this->unsortedEventsMap[$current])) {
                    $this->sortListeners($current);
                    unset($this->unsortedEventsMap[$current]);
                }

                // iterate
                foreach ($this->listeners[$current] as $index => $callback) {
                    // schedule for removal if the once flag is present
                    if (isset($this->entries[$current][$index]['once'])) {
                        $toRemove[$current][] = $index;
                    }

                    if (false === call_user_func_array($callback, $currentArgs)) {
                        // propagation stopped
                        return;
                    }
                }
            }
        }

        // handle scheduled removals
        if ($toRemove) {
            foreach ($toRemove as $current => $indexes) {
                for ($i = sizeof($indexes) - 1; $i >= 0; --$i) {
                    $this->unregisterListener($current, $indexes[$i], true);
                }
            }
        }
    }

    /**
     * @param string   $event
     * @param callable $listener
     * @param int      $priority
     * @param bool     $once
     */
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

    /**
     * @param string       $event
     * @param callable|int $listener
     * @param bool         $byIndex
     * @return bool
     */
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

    /**
     * @param string $event
     */
    private function sortListeners($event)
    {
        $this->listeners[$event] = array();

        usort($this->entries[$event], function ($a, $b) {
            return $a['priority'] > $b['priority']
                ? -1
                : ($a['priority'] < $b['priority'] ? 1 : 0);
        });

        foreach ($this->entries[$event] as $entry) {
            $this->listeners[$event][] = $entry['listener'];
        }

        unset($this->unsortedEventsMap[$event]);
    }
}
