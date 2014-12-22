<?php

namespace Kuria\Event;

/**
 * Event subscriber interface
 *
 * @author ShiraNai7 <shira.cz>
 */
interface EventSubscriberInterface
{
    /**
     * Get map of events the subscriber wants to listen to
     *
     * This is called once upon registration.
     *
     * Return value format:
     *
     * - Event names are used as keys
     * - An entry can be either a string (method name) or an array consisting
     *   of method name as the first element and priority as the second
     *
     * Example:
     *
     *  array(
     *      'some.event' => 'onSomeEvent',
     *      'other.event' => array('onOtherEvent', 1),
     *      'yet.another.event' => array('onYetAnotherEvent', 5),
     *  )
     *
     * @return array
     */
    public function getEvents();
}
