<?php

use nostriphant\Transpher\Relay\Incoming\Event\Limits;

$event_ids = [
    ['000006d8c378af1779d2feebc7603a125d99eca0ccf1085959b307f64e5dd358', 21],
    ['6bf5b4f434813c64b523d2b0e6efe18f3bd0cbbd0a5effd8ece9e00fd2531996', 1],
    ['00003479309ecdb46b1c04ce129d2709378518588bed6776e60474ebde3159ae', 18],
    ['01a76167d41add96be4959d9e618b7a35f26551d62c43c11e5e64094c6b53c83', 7],
    ['ac4f44bae06a45ebe88cfbd3c66358750159650a26c0d79e8ccaa92457fca4f6', 0],
    ['0000000000000000006cfbd3c66358750159650a26c0d79e8ccaa92457fca4f6', 73],
];

it('should check for expected amount of leading zeros for an event-id', function ($id, $difficulty) {
    $signer = \Pest\key_sender();
    $event = nostriphant\Transpher\Nostr\Event::__set_state(json_decode('{
        "id": "' . $id . '",
        "pubkey": "' . $signer(\nostriphant\Transpher\Nostr\Key::public()) . '",
        "created_at": 1651794653,
        "kind": 1,
        "tags": [
          ["nonce", "776797", "' . ($difficulty - 1) . '"]
        ],
        "content": "It\'s just me mining my own business",
        "sig": "' . $signer(\nostriphant\Transpher\Nostr\Key::signer($id)) . '"
      }', true));

    $limits = Limits::construct(eventid_min_leading_zeros: $difficulty, created_at_lower_delta: 0, created_at_upper_delta: 0);
    expect($limits($event))->toHaveState(accepted: '*', rejected: ['']);

    $limits = Limits::construct(eventid_min_leading_zeros: $difficulty + 1, created_at_lower_delta: 0, created_at_upper_delta: 0);
    expect($limits($event))->toHaveState(rejected: ['not enough leading zeros (' . ($difficulty + 1) . ') for event id']);
})->with($event_ids);

it('should check for expected amount of leading zeros for an event-id, configured through ENV-vars', function ($id, $difficulty) {
    $signer = \Pest\key_sender();
    $event = nostriphant\Transpher\Nostr\Event::__set_state(json_decode('{
        "id": "' . $id . '",
        "pubkey": "' . $signer(\nostriphant\Transpher\Nostr\Key::public()) . '",
        "created_at": 1651794653,
        "kind": 1,
        "tags": [
          ["nonce", "776797", "' . ($difficulty - 1) . '"]
        ],
        "content": "It\'s just me mining my own business",
        "sig": "' . $signer(\nostriphant\Transpher\Nostr\Key::signer($id)) . '"
      }', true));

    putenv('LIMIT_EVENT_CREATED_AT_LOWER_DELTA=0');
    putenv('LIMIT_EVENT_CREATED_ATUPPER_DELTA=0');
    putenv('LIMIT_EVENT_EVENTID_MIN_LEADING_ZEROS=' . $difficulty);
    $limits = Limits::fromEnv();
    expect($limits($event))->toHaveState(accepted: '*', rejected: ['']);

    putenv('LIMIT_EVENT_EVENTID_MIN_LEADING_ZEROS=' . $difficulty + 1);
    $limits = Limits::fromEnv();
    expect($limits($event))->toHaveState(rejected: ['not enough leading zeros (' . ($difficulty + 1) . ') for event id']);

    putenv('LIMIT_EVENT_EVENTID_MIN_LEADING_ZEROS');
    putenv('LIMIT_EVENT_CREATED_AT_LOWER_DELTA');
    putenv('LIMIT_EVENT_CREATED_ATUPPER_DELTA');
})->with($event_ids);

/**  UNABLE TO GET THIS WORKING WITHOUT PROPER TEST VECTORS
$keys = [
    ['d4b3cc4ca2f49c696dfe91a7b1e93db58b225ccf44aa343fbc0c5e85a5259afc', '0007ac8472e81020a1011db2e0d3c87ec41da34bfaf5203fac4712d6b1ad9964', 13],
    ['6f32b7120f1d56d1c95012b0f90f44c66247d73f97bb5e9bb8b91a04bd410a92', '00000e7cc77f6d626c74d5b4870b47828b6920b51e57a91f82cba8798a92bb83', 0]
];

it('should check for expected amount of leading zeros for a pubkey', function (string $privkey, string $pubkey, int $difficulty) {
    $signer = \nostriphant\Transpher\Nostr\Key::fromHex($privkey);

    $rumor = new \nostriphant\Transpher\Nostr\Rumor(time(), $pubkey, 1, "It's just me mining my own business", [["nonce", "776797", "" . ($difficulty - 1)]]);
    $event = $rumor($signer);

    expect($pubkey)->toBe($signer(\nostriphant\Transpher\Nostr\Key::public()));
    //expect($event->sig)->toBe($signer(\nostriphant\Transpher\Nostr\Key::signer($event->id)));
    expect(\nostriphant\Transpher\Nostr\Event::verify($event))->toBeTrue();

    $limits = Limits::construct(pubkey_min_leading_zeros: $difficulty);
    $constraint = $limits($event);
    expect($constraint)->toHaveState(accepted: '*');

    $limits = Limits::construct(pubkey_min_leading_zeros: $difficulty + 1);
    $constraint = $limits($event);
    expect($constraint)->toHaveState(rejected: ['not enough leading zeros (' . ($difficulty + 1) . ') for event id']);
})->with($keys);

it('should check for expected amount of leading zeros for a pubkey, configured through ENV-vars', function (string $privkey, string $pubkey, int $difficulty) {
    $signer = \nostriphant\Transpher\Nostr\Key::fromHex($privkey);

    expect($pubkey)->toBe($signer(\nostriphant\Transpher\Nostr\Key::public()));

    $rumor = new \nostriphant\Transpher\Nostr\Rumor(1651794653, $pubkey, 1, "It's just me mining my own business", ["nonce", "776797", "" . ($difficulty - 1)]);
    $event = $rumor($signer);

    putenv('LIMIT_EVENT_PUBKEY_MIN_LEADING_ZEROS=' . $difficulty);
    $limits = Limits::fromEnv();
    $constraint = $limits($event);
    expect($constraint)->toHaveState(accepted: '*');

    putenv('LIMIT_EVENT_PUBKEY_MIN_LEADING_ZEROS=' . $difficulty + 1);
    $limits = Limits::fromEnv();
    $constraint = $limits($event);
    expect($constraint)->toHaveState(rejected: ['not enough leading zeros (' . ($difficulty + 1) . ') for event id']);

    putenv('LIMIT_EVENT_PUBKEY_MIN_LEADING_ZEROS');
})->with($keys);
*/