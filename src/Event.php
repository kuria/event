<?php

namespace Kuria\Event;

/**
 * Event implementation
 *
 * @author ShiraNai7 <shira.cz>
 */
class Event implements EventInterface
{
    /** @var string */
    protected $name;
    /** @var bool */
    protected $stopped = false;
    /** @var bool */
    protected $handled = false;

    public function stop()
    {
        $this->stopped = true;
    }

    public function isStopped()
    {
        return $this->stopped;
    }

    public function setHandled($handled)
    {
        $this->handled = $handled;

        return $this;
    }

    public function isHandled()
    {
        return $this->handled;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
