<?php

namespace nostriphant\Transpher\Relay\Incoming;

use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\Transpher\Nostr\Event\KindClass;
use nostriphant\Transpher\Relay\Condition;

readonly class Event implements Type {

    public function __construct(
            private Event\Accepted $accepted,
            private \nostriphant\Transpher\Relay\Limits $limits
    ) {
        
    }

    #[\Override]
    public function __invoke(array $payload): \Generator {
        $event = new \nostriphant\Transpher\Nostr\Event(...$payload[0]);
        $constraint = ($this->limits)($event);
        yield from $constraint(
                        accepted: fn() => yield from ($this->accepted)($event),
                        rejected: fn(string $reason) => yield Factory::ok($event->id, false, 'invalid:' . $reason)
                );
    }
}
