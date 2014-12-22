<?php

namespace Kuria\Event;

class EventTest extends \PHPUnit_Framework_TestCase
{
    public function testApi()
    {
        $event = new Event();
        $event->setName('foo');

        $this->assertSame('foo', $event->getName());
        $this->assertFalse($event->isStopped());
        $this->assertFalse($event->isHandled());

        $event->stop();
        $event->setHandled(true);

        $this->assertTrue($event->isStopped());
        $this->assertTrue($event->isHandled());
    }
}
