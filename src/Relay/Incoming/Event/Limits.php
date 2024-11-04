<?php


namespace nostriphant\Transpher\Relay\Incoming\Event;
use nostriphant\Transpher\Relay\Incoming\Constraint;
use nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Nostr\Event\KindClass;

readonly class Limits {

    public function __construct(
            private int $created_at_lower_delta = (60 * 60 * 24),
            private int $created_at_upper_delta = (60 * 15),
            private ?array $kind_whitelist = null,
            private ?array $kind_blacklist = null
    ) {

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
        $now = time();
        if (Event::verify($event) === false) {
            return Constraint::reject('signature is wrong');
        } elseif (Event::determineClass($event) !== KindClass::REGULAR && ($now - $this->created_at_lower_delta > $event->created_at || $event->created_at > $now + $this->created_at_upper_delta)) {
            return Constraint::reject('the event created_at field is out of the acceptable range (-' . self::secondsTohuman($this->created_at_lower_delta) . ', +' . self::secondsTohuman($this->created_at_upper_delta) . ') for this relay');
        } elseif (isset($this->kind_whitelist) && in_array($event->kind, $this->kind_whitelist) === false) {
            return Constraint::reject('event kind is not whitelisted');
        } elseif (isset($this->kind_blacklist) && in_array($event->kind, $this->kind_blacklist) === true) {
            return Constraint::reject('event kind is not whitelisted');
        }
        return Constraint::accept();
    }
}
