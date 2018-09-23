<?php declare(strict_types=1);

namespace Kuria\Event;

use Kuria\DevMeta\Test;

class EventListenerTest extends Test
{
    /**
     * @dataProvider provideEventListeners
     */
    function testShouldCreateEventListener(
        EventListener $listener,
        string $expectedEvent,
        $expectedCallback,
        int $expectedPriority
    ) {
        $this->assertSame($listener->event, $expectedEvent);
        $this->assertSame($listener->callback, $expectedCallback);
        $this->assertSame($listener->priority, $expectedPriority);
    }

    function provideEventListeners()
    {
        return [
            // listener, epxectedEvent, expectedCallback, expectedPriority
            'default priority' => [new EventListener('foo', 'callback_a'), 'foo', 'callback_a', 0],
            'specified priority' => [new EventListener('bar', 'callback_b', 123), 'bar', 'callback_b', 123],
        ];
    }
}
