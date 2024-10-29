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
        foreach ($this->value->messages as $message) {
            $expected_message = array_shift($expected_messages);
            foreach ($message() as $part) {
                if (count($expected_message) === 0) {
                    continue;
                }

                $expected_part = array_shift($expected_message);
                if (is_callable($expected_part)) {
                    $expected_part($part);
                } else {
                    expect($part)->toBe($expected_part);
                }
            }
        }
        expect($this->value->messages)->toHaveCount(func_num_args());
        $this->value->messages = [];
    });
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

    use nostriphant\Transpher\Relay\Incoming\Context;
    use nostriphant\Transpher\Nostr\Key;

    function key(string $nsec): Key {
        return Key::fromBech32($nsec);
    }

    function key_sender(): Key {
        return key('nsec15udyzkfk7twhpdmhu5syc4lqm7dxmll0jxeu0rq65f89gaewx0ps89derx');
    }

    function key_recipient(): Key {
        return key('nsec1dm444kv7gug4ge7sjms8c8ym3dqhdz44x3jhq0mcq9eqftw9krxqymj9qk');
    }

    function context(array $events = [], array &$subscriptions = []): Context {
        return new Context(
                subscriptions: new \nostriphant\Transpher\Relay\Subscriptions($subscriptions),
                events: new class($events) implements \nostriphant\Transpher\Relay\Store {

                    use \nostriphant\Transpher\Nostr\Store;
                },
                relay: new class implements \nostriphant\Transpher\Relay\Sender {

                    public array $messages = [];

                    #[\Override]
                    public function __invoke(mixed $json): bool {
                        $this->messages[] = $json;
                        return true;
                    }
                },
                reply: new class implements \nostriphant\Transpher\Relay\Sender {

                    public array $messages = [];

                    #[\Override]
                    public function __invoke(mixed $json): bool {
                        $this->messages[] = $json;
                        return true;
                    }
                }
        );
    }

    function vectors(string $name): object {
        return json_decode(file_get_contents(__DIR__ . '/vectors/' . $name . '.json'), false);
    }

    use nostriphant\Transpher\Nostr\Event;

    function event(array $event): Event {
        return new Event(...array_merge([
                    'id' => '',
                    'pubkey' => '',
                    'created_at' => time(),
                    'kind' => 1,
                    'content' => 'Hello World',
                    'sig' => '',
                    'tags' => []
                        ], $event));
    }

}