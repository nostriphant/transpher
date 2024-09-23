<?php

use Transpher\Nostr\Relay\Agent;
use \Transpher\Key;

$agent_key = Key::generate();
$main_key = Key::generate();
$main_agent;
beforeAll(function () use (&$main_agent, $agent_key, $main_key) {
    Agent::boot(8084, $main_key(Key::public()), ['AGENT_KEY' => $agent_key(fn() => func_get_arg(0))], function (callable $agent) use (&$main_agent) {
        $main_agent = $agent;
    });
});
afterAll(function () use (&$main_agent) {
    $status = $main_agent(Key::public());
    expect($status)->toBeArray();
    expect($status['running'])->toBeFalse();
});

describe('agent', function () use ($main_key) {

    $alice = \TranspherTests\Client::client(8084);
    $subscription = Transpher\Message::subscribe();

    it('starts relay and seeks connection with client', function () use ($alice, $subscription, $main_key) {
        $request = Transpher\Message::filter($subscription, tags: [['#p' => [$main_key(Key::public())]]])();
        $alice->expectNostrPrivateDirectMessage($subscription()[1], $main_key, 'Hello, I am Agent!');
        $alice->json($request);
        $alice->start();
    });
});
