<?php

namespace Kuria\Event;

/**
 * Event emitter trait
 *
 * Implements EventEmitterInterface
 *
 * The code here is copy-pasted from EventEmitter
 * (to maintain compatibility with PHP 5.3)
 *
 * @author ShiraNai7 <shira.cz>
 */
trait EventEmitterTrait
{
    protected $listeners = array();
    protected $listenerSortState = array();

    public function getListenerCount($event = null)
    {
        if (null !== $event) {
            return isset($this->listeners[$event])
                ? sizeof($this->listeners[$event])
                : 0
            ;
        } else {
            $count = 0;

            foreach ($this->listeners as $entries) {
                $count += sizeof($entries);
            }

            return $count;
        }
    }

    public function hasListener($event = null)
    {
        return null !== $event
            ? isset($this->listeners[$event])
            : !empty($this->listeners)
        ;
    }

    public function on($event, $listener, $priority = 0)
    {
        $this->listeners[$event][] = array($listener, $priority);
        $this->listenerSortState[$event] = false;

        return $this;
    }

    public function once($event, $listener, $priority = 0)
    {
        $this->listeners[$event][] = array($listener, $priority, true);
        $this->listenerSortState[$event] = false;

        return $this;
    }

    public function removeListener($event, $listener)
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $index => $entry) {
                if ($listener === $entry[0]) {
                    // match, remove listener
                    unset($this->listeners[$event][$index]);

                    // cleanup
                    if (empty($this->listeners[$event])) {
                        unset($this->listeners[$event], $this->listenerSortState[$event]);
                    }

                    break;
                }
            }
        }

        return $this;
    }

    public function clearListeners($event = null)
    {
        if (null !== $event) {
            unset($this->listeners[$event], $this->listenerSortState[$event]);
        } else {
            $this->listeners = array();
            $this->listenerSortState = array();
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
        if (isset($this->listeners[$event])) {
            if (!$this->listenerSortState[$event]) {
                $this->sortListeners($event);
                $this->listenerSortState[$event] = true;
            }

            $args = array_slice(func_get_args(), 1);

            foreach ($this->listeners[$event] as $index => $entry) {
                // remove the listener if it has the once flag
                if (isset($entry[2])) {
                    unset($this->listeners[$event][$index]);
                }

                // invoke the listener
                if (false === call_user_func_array($entry[0], $args)) {
                    // propagation stopped
                    break;
                }
            }

            // cleanup after possibly removed once listeners
            if (empty($this->listeners[$event])) {
                unset($this->listeners[$event]);
            }

            return true;
        }

        return false;
    }

    public function emitArray($event, array $args)
    {
        if (isset($this->listeners[$event])) {
            if (!$this->listenerSortState[$event]) {
                $this->sortListeners($event);
                $this->listenerSortState[$event] = true;
            }

            foreach ($this->listeners[$event] as $index => $entry) {
                // remove the listener if it has the once flag
                if (isset($entry[2])) {
                    unset($this->listeners[$event][$index]);
                }

                // invoke the listener
                if (false === call_user_func_array($entry[0], $args)) {
                    // propagation stopped
                    break;
                }
            }

            // cleanup after possibly removed once listeners
            if (empty($this->listeners[$event])) {
                unset($this->listeners[$event]);
            }

            return true;
        }

        return false;
    }

    protected function sortListeners($event)
    {
        usort($this->listeners[$event], function ($a, $b) {
            return $a[1] > $b[1] ? -1 : ($a[1] < $b[1] ? 1 : 0);
        });
    }
}
