<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Relay\Incoming\Constraint;
use nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Nostr\Event\KindClass;

readonly class Limits {

    private array $checks;

    public function __construct(
            private int $created_at_lower_delta = (60 * 60 * 24),
            private int $created_at_upper_delta = (60 * 15),
            private ?array $kind_whitelist = null,
            private ?array $kind_blacklist = null
    ) {
        $checks = [
            'signature is wrong' => fn(Event $event): bool => Event::verify($event) === false
        ];

        if ($this->created_at_lower_delta > 0) {
            $checks['the event created_at field is out of the acceptable range (-' . self::secondsTohuman($this->created_at_lower_delta) . ') for this relay'] = fn(Event $event): bool => Event::determineClass($event) !== KindClass::REGULAR && time() - $this->created_at_lower_delta > $event->created_at;
        }
        if ($this->created_at_upper_delta > 0) {
            $checks['the event created_at field is out of the acceptable range (+' . self::secondsTohuman($this->created_at_upper_delta) . ') for this relay'] = fn(Event $event): bool => Event::determineClass($event) !== KindClass::REGULAR && $event->created_at > time() + $this->created_at_upper_delta;
        }
        if (isset($this->kind_whitelist)) {
            $checks['event kind is not whitelisted'] = fn(Event $event): bool => in_array($event->kind, $this->kind_whitelist) === false;
        }
        if (isset($this->kind_blacklist)) {
            $checks['event kind is blacklisted'] = fn(Event $event): bool => in_array($event->kind, $this->kind_blacklist);
        }

        $this->checks = $checks;
    }

    private static function secondsTohuman(int $amount): string {
        $scales = array_reverse([
            'sec' => $seconds = 1,
            'min' => $minutes = $seconds * 60,
            'h' => $hours = $minutes * 60,
            'days' => $days = $hours * 24,
            'weeks' => $days * 7
        ]);

        $human = [];
        foreach ($scales as $human_identifier => $limit) {
            if ($amount >= $limit) {
                $remainder = $amount % $limit;
                $units = (($amount - $remainder) / $limit);
                if ($units > 1) {
                    $human[] = $units . $human_identifier;
                    $amount = $remainder;
                }
            }
        }

        return join(', ', $human);
    }

    public function __invoke(Event $event): Constraint {
        foreach ($this->checks as $reason => $check) {
            if ($check($event)) {
                return Constraint::reject($reason);
            }
        }
        return Constraint::accept();
    }
}
