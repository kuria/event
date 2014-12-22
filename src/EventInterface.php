<?php

namespace Kuria\Event;

/**
 * Event interface
 *
 * @author ShiraNai7 <shira.cz>
 */
interface EventInterface
{
    /**
     * Stop event propagation
     *
     * This prevents pending listeners from being called.
     */
    public function stop();

    /**
     * See if the event propagation has been stopped
     *
     * @return bool
     */
    public function isStopped();

    /**
     * Set handled status
     *
     * @param bool $handled
     * @return static
     */
    public function setHandled($handled);

    /**
     * See if the event has been handled
     *
     * @return bool
     */
    public function isHandled();

    /**
     * Get event name
     *
     * @return string
     */
    public function getName();

    /**
     * Set event name
     *
     * @param string $name
     * @return static
     */
    public function setName($name);
}
