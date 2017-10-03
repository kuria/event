<?php declare(strict_types=1);

namespace Kuria\Event;

interface EventSubscriberInterface
{
    /**
     * Subscribe to the given observable
     */
    function subscribeTo(ObservableInterface $emitter): void;

    /**
     * Unsubscribe from the given observable
     */
    function unsubscribeFrom(ObservableInterface $emitter): void;
}
