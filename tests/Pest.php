<?php
namespace {
    /*
      |--------------------------------------------------------------------------
      | Test Case
      |--------------------------------------------------------------------------
      |
      | The closure you provide to your test functions is always bound to a specific PHPUnit test
      | case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
      | need to change it using the "uses()" function to bind a different classes or traits.
      |
     */

    // uses(Tests\TestCase::class)->in('Feature');
    // uses(\PHPUnit\Framework\TestCase::class)->in('Feature');

    /*
      |--------------------------------------------------------------------------
      | Expectations
      |--------------------------------------------------------------------------
      |
      | When you're writing tests, you often need to check that values meet certain conditions. The
      | "expect()" function gives you access to a set of "expectations" methods that you can use
      | to assert different things. Of course, you may extend the Expectation API at any time.
      |
     */

    expect()->extend('toBeOne', function () {
        return $this->toBe(1);
    });

    expect()->extend('toHaveReceived', function (array ...$expected_messages) {
        expect($this->value->messages)->toHaveCount(func_num_args());
        foreach ($this->value->messages as $message) {
            $expected_message = array_shift($expected_messages);
            foreach ($message() as $pos => $part) {
                if (count($expected_message) === 0) {
                    continue;
                }

                $expected_part = array_shift($expected_message);
                if ($pos > 0 && is_callable($expected_part)) {
                    $expected_part($part);
                } else {
                    expect($part)->toBe($expected_part, var_export($message(), true));
                }
            }
        }
        $this->value->messages = [];
    });

    expect()->extend('toHaveReceivedNothing', function () {
        expect($this->value->messages)->toHaveCount(0);
    });

    nostriphant\FunctionalAlternate\extend_pest('expect');
    nostriphant\NIP01\extend_pest('expect');
}

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
namespace Pest {

    use nostriphant\NIP01\Message;
    use nostriphant\Transpher\Relay\Incoming;
    use nostriphant\Transpher\Nostr\Transmission;


    function relay(): Transmission {
        return new class implements Transmission {

            public array $messages = [];

            #[\Override]
            public function __invoke(mixed $json): bool {
                $this->messages[] = $json;
                return true;
            }
        };
    }

    function subscriptions(?Transmission $relay = null) {
        return new \nostriphant\Transpher\Relay\Subscriptions($relay ?? relay());
    }

    function store(array $events = []) {
        return new \nostriphant\Stores\Store(new \nostriphant\Stores\Engine\Memory($events), []);
    }

    function files_path() {
        return ROOT_DIR . '/data/files';
    }

    function incoming(?\nostriphant\Stores\Store $store = null, string $files = ROOT_DIR . '/data/files') {
        $store = $store ?? store();
        return new Incoming($store, new \nostriphant\Transpher\Files($files, $store));
    }

    function rumor(?int $created_at = null, ?string $pubkey = '', ?int $kind = 0, ?string $content = '', ?array $tags = []): \nostriphant\NIP59\Rumor {
        return new \nostriphant\NIP59\Rumor(
                $created_at ?? time(),
                $pubkey,
                $kind,
                $content,
                $tags
        );
    }

    function handle(Message $message, ?Incoming $incoming = null, ?\nostriphant\Transpher\Relay\Subscriptions $subscriptions = null): Transmission {
        $to = new class implements \nostriphant\Transpher\Nostr\Transmission {

            public array $messages = [];

            #[\Override]
            public function __invoke(mixed $json): bool {
                $this->messages[] = $json;
                return true;
            }
        };

        foreach (($incoming ?? incoming())($subscriptions ?? subscriptions(), $message) as $reply) {
            $to($reply);
        }
        return $to;
    }

    function client(string $relay_url) {
        return function () use ($relay_url) {
            $expected_messages = [];
            $client = new \nostriphant\Transpher\Amp\Client(0, $relay_url);
            $send = $client->start(function (Message $message) use (&$expected_messages) {
                $expected_message = array_shift($expected_messages);
                expect($message->type)->toBe($expected_message[0], 'Message type checks out');
                $expected_message[1]($message->payload);
            });

            return function (Message $message, array ...$expected_replies) use ($send, &$expected_messages) {
                $send($message);
                $expected_messages = array_merge($expected_messages, $expected_replies);
            };
        };
    }

}