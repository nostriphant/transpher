<?php

it('should check for expected amount of leading zeros for an event-id', function () {
    $event = nostriphant\Transpher\Nostr\Event::__set_state(json_decode('{
        "id": "000006d8c378af1779d2feebc7603a125d99eca0ccf1085959b307f64e5dd358",
        "pubkey": "a48380f4cfcc1ad5378294fcac36439770f9c878dd880ffa94bb74ea54a6f243",
        "created_at": 1651794653,
        "kind": 1,
        "tags": [
          ["nonce", "776797", "20"]
        ],
        "content": "It\'s just me mining my own business",
        "sig": "284622fc0a3f4f1303455d5175f7ba962a3300d136085b9566801bc2e0699de0c7e31e44c81fb40ad9049173742e904713c3594a1da0fc5d2382a25c11aba977"
      }', true));

    $limits = \nostriphant\Transpher\Relay\Incoming\Event\Limits::construct(eventid_min_leading_zeros: 5);
    expect($limits($event)->result)->toBe(\nostriphant\Transpher\Relay\Incoming\Constraint\Result::ACCEPTED);

    $limits = \nostriphant\Transpher\Relay\Incoming\Event\Limits::construct(eventid_min_leading_zeros: 6);
    expect($limits($event)->result)->toBe(\nostriphant\Transpher\Relay\Incoming\Constraint\Result::REJECTED);
    expect($limits($event)->reason)->toBe('not enough leading zeros (6) for event id');

    $limits = \nostriphant\Transpher\Relay\Incoming\Event\Limits::construct(eventid_min_leading_zeros: 5);
    expect($limits($event)->result)->toBe(\nostriphant\Transpher\Relay\Incoming\Constraint\Result::ACCEPTED);

    $limits = \nostriphant\Transpher\Relay\Incoming\Event\Limits::construct(eventid_min_leading_zeros: 7);
    expect($limits($event)->result)->toBe(\nostriphant\Transpher\Relay\Incoming\Constraint\Result::REJECTED);
    expect($limits($event)->reason)->toBe('not enough leading zeros (7) for event id');
});
