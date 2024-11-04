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

        if (isset($content_maxlength)) {
            if (is_int($content_maxlength)) {
                $content_maxlength = [$content_maxlength];
            }

            $content_maxlength_check = fn(Event $event): bool => mb_strlen($event->content) > $content_maxlength[0];
            if (count($content_maxlength) === 2) {
                $content_maxlength_check = fn(Event $event): bool => $event->kind === (int) $content_maxlength[1] && $content_maxlength_check($event);
            } elseif (count($content_maxlength) === 3) {
                $content_maxlength_check = fn(Event $event): bool => in_range($event->kind, $content_maxlength[1], $content_maxlength[2]) && $content_maxlength_check($event);
            }
            $checks['content is longer than ' . $content_maxlength[0] . ' bytes'] = $content_maxlength_check;
        }

        $this->checks = $checks;
    }

    static function fromEnv(): self {
        $arguments = [];
        $environment_variables = getenv(null);
        foreach ((new \ReflectionMethod(__CLASS__, '__construct'))->getParameters() as $parameter) {
            $parameter_name = $parameter->getName();
            $env_var_name = 'LIMIT_EVENT_' . strtoupper($parameter_name);
            if (isset($environment_variables[$env_var_name]) === false) {
                continue;
            }

            if (str_contains($parameter->getType(), 'array')) {
                $arguments[$parameter_name] = explode(',', $environment_variables[$env_var_name]);
            } else {
                $arguments[$parameter_name] = $environment_variables[$env_var_name];
            }
        }
        return new self(...$arguments);
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
