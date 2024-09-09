<?php

it('can generate a properly signed note', function() {
    $private_key = \Transpher\Key::private('435790f13406085d153b10bd9e00a9f977e637f10ce37db5ccfc5d3440c12d6c');

    $note = \Transpher\Message::event(1, 'Hello world!');
    $signed_note = $note($private_key);

    $event = $signed_note;
    expect($event[0])->toBe('EVENT');
    expect($event[1])->toBeArray();
    $event_scaffolded = array_merge([
        "id" => null,
        "pubkey" => null,
        "created_at" => null,
        "kind" => null,
        "tags" => null,
        "content" => null,
        "sig" => null
    ], $event[1]);
    array_walk($event_scaffolded, fn(mixed $value, string $key) => expect($value)->not()->toBeNull($key . ' not set'));
    
    expect($event[1]['kind'])->toBe(1);
    expect($event[1]['content'])->toBe('Hello world!');
    expect($event[1]['tags'])->toBe([]);
    expect($event[1]['created_at'])->toBeInt();
    expect($event[1]['pubkey'])->toBe('89ac55aeeb301252da33b51ca4d189cb1d665b8f00618f5ea72c2ec59ca555e9');

    $sign = new \Mdanter\Ecc\Crypto\Signature\SchnorrSignature();
    $reporting = set_error_handler(fn() => null);
    $verification = $sign->verify($event[1]['pubkey'], $event[1]['sig'], $event[1]['id']);
    set_error_handler($reporting);

    expect($verification)->toBeTrue();
});

it('can create a subscribe message with a kinds filter', function() {
    $subscription = Transpher\Message::subscribe();
    $message = Transpher\Message::filter($subscription, kinds:[1])();
    expect($message[0])->toBe('REQ');
    expect($message[1])->toBeString();
    expect(strlen($message[1]) <= 64)->toBeTrue();
    expect(str_contains($message[1],' '))->toBeFalse();
    expect($message[2]['kinds'])->toBe([1]);
});
it('can create a subscribe message with multiple filters', function() {
    $subscription = Transpher\Message::subscribe();
    $filter1 = Transpher\Message::filter($subscription, kinds:[1]);
    $filter2 = Transpher\Message::filter($filter1, since:1724755392);
    
    $message = $filter2();
    expect($message[0])->toBe('REQ');
    expect($message[1])->toBeString();
    expect(strlen($message[1]) <= 64)->toBeTrue();
    expect(str_contains($message[1],' '))->toBeFalse();
    expect($message[2]['kinds'])->toBe([1]);
    expect($message[3]['since'])->toBe(1724755392);
});

it('can create a subscribe message with a different filter-conditions', function() {
    $conditions = [
        "ids" => ["7356b35d-a428-4d51-bc32-ba26e45803c6", "7aa26f57-2162-4543-9aa5-b4dc0cfd73e4"],
        "authors" => ["5ab2a1fc-40b2-4ae1-85a4-4d207330d3c1", "b618d576-bf3c-4f5a-9334-d9c860b142b4"],
        "kinds" => [1,2,4,6],
        //"#<single-letter (a-zA-Z)>" => <a list of tag values, for #e — a list of event ids, for #p — a list of pubkeys, etc.>,
        "since" => 1724755392,
        "until" => 1756284192,
        "limit" => 25
    ];
    
    $subscription = Transpher\Message::subscribe();
    $message = Transpher\Message::filter($subscription, ...$conditions)();
    expect($message[0])->toBe('REQ');
    expect($message[1])->toBeString();
    expect(strlen($message[1]) <= 64)->toBeTrue();
    expect(str_contains($message[1],' '))->toBeFalse();
    expect($message[2]['ids'])->toBe(["7356b35d-a428-4d51-bc32-ba26e45803c6", "7aa26f57-2162-4543-9aa5-b4dc0cfd73e4"]);
    expect($message[2]['authors'])->toBe(["5ab2a1fc-40b2-4ae1-85a4-4d207330d3c1", "b618d576-bf3c-4f5a-9334-d9c860b142b4"]);
    expect($message[2]['kinds'])->toBe([1,2,4,6]);
    expect($message[2]['since'])->toBe(1724755392);
    expect($message[2]['until'])->toBe(1756284192);
    expect($message[2]['limit'])->toBe(25);
});

it('does not allow for unknown filters', function() {
    
    $subscription = Transpher\Message::subscribe();
    $filter1 = Transpher\Message::filter($subscription, kinds:[1]);
    $filter2 = Transpher\Message::filter($filter1, unknown:1724755392);
    
    $message = $filter2();
    expect($message)->toHaveLength(3);
});


it('does not allow for unknown filters, merge tags', function() {
    
    $subscription = Transpher\Message::subscribe();
    $filter1 = Transpher\Message::filter($subscription, kinds:[1]);
    $filter2 = Transpher\Message::filter($filter1, tags:['#e'=>["7356b35d-a428-4d51-bc32-ba26e45803c6", "7aa26f57-2162-4543-9aa5-b4dc0cfd73e4"]]);
    
    $message = $filter2();
    expect($message)->toHaveLength(4);
    expect($message[3]['#e'])->toBe(["7356b35d-a428-4d51-bc32-ba26e45803c6", "7aa26f57-2162-4543-9aa5-b4dc0cfd73e4"]);
    
});