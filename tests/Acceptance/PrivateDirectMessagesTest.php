<?php

use nostriphant\NIP01\Key;
use nostriphant\TranspherTests\Listener;
use nostriphant\TranspherTests\Transpher;
use nostriphant\TranspherTests\Factory;

use nostriphant\Client\Client;


it('starts relay and sends private direct messsage to relay owner', function (string $sender_hex, string $recipient_hex) {
    $sender = Key::fromHex($sender_hex);
    $recipient = Key::fromHex($recipient_hex);
    
    $transpher = new Transpher('8087', $recipient, null);
    
    try {
        
        $worker = Amp\Parallel\Worker\createWorker();

        $bob_message = (new nostriphant\NIP01\Rumor(
                            pubkey: $sender(Key::public()),
                                    created_at: time(),
                                    kind: 1,
                                    content: 'Hello!',
                                    tags: []
                            ))($sender);

        $executions = [
            // Alice
            $worker->submit(new nostriphant\TranspherTests\Acceptance\PrivateDirectMessagesTest\Alice($transpher->ws, $recipient_hex, $sender(Key::public()))),

            // Bob
            $worker->submit(new \nostriphant\TranspherTests\Acceptance\PrivateDirectMessagesTest\Bob($transpher->ws, $sender_hex, $bob_message))
        ];

        $responses = Amp\Future\await(array_map(
            fn (Amp\Parallel\Worker\Execution $e) => $e->getFuture(),
            $executions,
        ));
                

        $events = new nostriphant\Stores\Engine\SQLite(new SQLite3($transpher->data_directory . '/transpher.sqlite'), []);

        $notes_alice = iterator_to_array(nostriphant\Stores\Store::query($events, ['authors' => [$recipient(Key::public())], 'kinds' => [1]]));
        expect($notes_alice[0]->kind)->toBe(1);
        expect($notes_alice[0]->content)->toBe('Hello from Alice!');

        $notes_bob = iterator_to_array(nostriphant\Stores\Store::query($events, ['ids' => [$bob_message->id]]));
        expect($notes_bob)->toHaveLength(1);
        expect($notes_bob[0]->kind)->toBe(1);
        expect($notes_bob[0]->content)->toBe('Hello!');

        $pdms = iterator_to_array(nostriphant\Stores\Store::query($events, ['#p' => [$recipient(Key::public())]]));
        expect($pdms[0]->kind)->toBe(1059);

        expect(file_get_contents(ROOT_DIR . '/logs/relay-8087-output.log'))->not()->toContain('ERROR');
    } catch (\Exception $e) {
        $transpher();
        throw $e;
    }
    
    $transpher();
    
})->with([
    ['a71a415936f2dd70b777e5204c57e0df9a6dffef91b3c78c1aa24e54772e33c3', '6eeb5ad99e47115467d096e07c1c9b8b41768ab53465703f78017204adc5b0cc']
]);