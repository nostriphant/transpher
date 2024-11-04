<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Relay\Incoming\Constraint;
use nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Nostr\Event\KindClass;

readonly class Limits {

    private array $checks;

    public function __construct(
            int $created_at_lower_delta = (60 * 60 * 24),
            int $created_at_upper_delta = (60 * 15),
            ?array $kind_whitelist = null,
            ?array $kind_blacklist = null,
            null|int|array $content_maxlength = null
    ) {
        $checks = [
            'signature is wrong' => fn(Event $event): bool => Event::verify($event) === false
        ];

        if ($created_at_lower_delta > 0) {
            $checks['the event created_at field is out of the acceptable range (-' . self::secondsTohuman($created_at_lower_delta) . ') for this relay'] = fn(Event $event): bool => Event::determineClass($event) !== KindClass::REGULAR && time() - $created_at_lower_delta > $event->created_at;
        }
        if ($created_at_upper_delta > 0) {
            $checks['the event created_at field is out of the acceptable range (+' . self::secondsTohuman($created_at_upper_delta) . ') for this relay'] = fn(Event $event): bool => Event::determineClass($event) !== KindClass::REGULAR && $event->created_at > time() + $created_at_upper_delta;
        }
        if (isset($kind_whitelist)) {
            $checks['event kind is not whitelisted'] = fn(Event $event): bool => in_array($event->kind, $kind_whitelist) === false;
        }
        if (isset($kind_blacklist)) {
            $checks['event kind is blacklisted'] = fn(Event $event): bool => in_array($event->kind, $kind_blacklist);
        }

        if (is_int($content_maxlength)) {
            $checks['content is longer than ' . $content_maxlength . ' bytes'] = fn(Event $event): bool => mb_strlen($event->content) > $content_maxlength;
        } elseif (is_array($content_maxlength) === false) {
            
        } elseif (count($content_maxlength) === 2) {
            $checks['content is longer than ' . $content_maxlength[0] . ' bytes'] = fn(Event $event): bool => $event->kind === $content_maxlength[1] && mb_strlen($event->content) > $content_maxlength[0];
        } elseif (count($content_maxlength) === 3) {
            $checks['content is longer than ' . $content_maxlength[0] . ' bytes'] = fn(Event $event): bool => in_range($event->kind, $content_maxlength[1], $content_maxlength[2]) && mb_strlen($event->content) > $content_maxlength[0];
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
