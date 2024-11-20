<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\NIP01\Event;

readonly class Limits {

    static function construct(
            int $created_at_lower_delta = (60 * 60 * 24),
            int $created_at_upper_delta = (60 * 15),
            ?array $kind_whitelist = null,
            ?array $kind_blacklist = null,
            null|int|array $content_maxlength = null,
            int $eventid_min_leading_zeros = 0,
            int $pubkey_min_leading_zeros = 0
    ): \nostriphant\Transpher\Relay\Limits {
        
        $checks = [
            'signature is wrong' => fn(Event $event): bool => Event::verify($event) === false
        ];

        if ($created_at_lower_delta > 0) {
            $checks['the event created_at field is out of the acceptable range (-' . self::secondsTohuman($created_at_lower_delta) . ') for this relay'] = fn(Event $event): bool => time() - $created_at_lower_delta > $event->created_at;
        }
        if ($created_at_upper_delta > 0) {
            $checks['the event created_at field is out of the acceptable range (+' . self::secondsTohuman($created_at_upper_delta) . ') for this relay'] = fn(Event $event): bool => $event->created_at > time() + $created_at_upper_delta;
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

        if ($eventid_min_leading_zeros > 0) {
            $checks['not enough leading zeros (' . $eventid_min_leading_zeros . ') for event id'] = fn(Event $event): bool => self::calculateLeadingZeros($event->id) < $eventid_min_leading_zeros;
        }
        if ($pubkey_min_leading_zeros > 0) {
            $checks['not enough leading zeros (' . $pubkey_min_leading_zeros . ') for pubkey'] = fn(Event $event): bool => self::calculateLeadingZeros($event->pubkey) < $pubkey_min_leading_zeros;
        }

        return new \nostriphant\Transpher\Relay\Limits($checks);
    }

    static function fromEnv(): \nostriphant\Transpher\Relay\Limits {
        return \nostriphant\Transpher\Relay\Limits::fromEnv('EVENT', __CLASS__);
    }

    static function calculateLeadingZeros(string $hex) {
        if (!ctype_xdigit($hex)) {
            throw new InvalidArgumentException("Not a hexidecimal number");
        }

        $binary = '';
        foreach (str_split($hex) as $hexChar) {
            $binary .= str_pad(base_convert($hexChar, 16, 2), 4, '0', STR_PAD_LEFT);
        }

        $leadingZeros = 0;
        for ($i = 0; $i < strlen($binary); $i++) {
            if ($binary[$i] === '0') {
                $leadingZeros++;
            } else {
                break;
            }
        }

        return $leadingZeros;
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
}
