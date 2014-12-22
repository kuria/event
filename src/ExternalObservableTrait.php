<?php

namespace Kuria\Event;

/**
 * External observable trait
 *
 * The code is copy-pasted from ExternalObservable to retain
 * as much PHP 5.3 compatibility as possible.
 *
 * Remember to implement the ExternalObservableInterface!
 *
 * @author ShiraNai7 <shira.cz>
 */
trait ExternalObservableTrait
{
    /** @var ObservableInterface */
    protected $observable;

    public function getNestedObservable()
    {
        return $this->observable;
    }

    public function setNestedObservable(ObservableInterface $observable = null)
    {
        $this->observable = $observable;

        return $this;
    }

    /**
     * Handle NULL observable state
     *
     * This method should either initialize it or throw an exception.
     *
     * @throws \LogicException
     */
    protected function handleNullObservable()
    {
        throw new \LogicException('The underlying observable object has not been set');
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        if (null === $this->observable) {
            $this->handleNullObservable();
        }
        $this->observable->addSubscriber($subscriber);

        return $this;
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        if (null === $this->observable) {
            $this->handleNullObservable();
        }

        $this->observable->removeSubscriber($subscriber);

        return $this;
    }

    public function addListener($eventName, $callback, $priority = 0)
    {
        if (null === $this->observable) {
            $this->handleNullObservable();
        }

        $this->observable->addListener($eventName, $callback, $priority);

        return $this;
    }

    public function removeListener($eventName, $callback)
    {
        if (null === $this->observable) {
            $this->handleNullObservable();
        }

        $this->observable->removeListener($eventName, $callback);

        return $this;
    }

    public function hasObservers($eventName)
    {
        if (null === $this->observable) {
            $this->handleNullObservable();
        }

        return $this->observable->hasObservers($eventName);
    }

    public function clearObservers($eventName = null)
    {
        if (null === $this->observable) {
            $this->handleNullObservable();
        }

        $this->observable->clearObservers($eventName);
    }

    public function notifyObservers(EventInterface $event, ObservableInterface $observable = null)
    {
        if (null === $this->observable) {
            $this->handleNullObservable();
        }

        if (null === $observable) {
            $observable = $this;
        }

        $this->observable->notifyObservers($event, $observable);
    }
}
