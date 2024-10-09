<?php

namespace Transpher;
use function \Functional\map, \Functional\filter, \Functional\partial_left;
use Transpher\Nostr\Relay\Subscriptions;
use Transpher\Filters;
use Transpher\Nostr\Message;
use Transpher\Nostr\Event\Signed;

/**
 * Description of Directory
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Directory implements \ArrayAccess {

    private array $events = [];
        
    public function __construct(private string $store) {
        foreach (glob($store . DIRECTORY_SEPARATOR . '*.php') as $event_file) {
            $event = include $event_file;
            $this->events[$event->id] = $event;
        }   
    }
    
    public function __invoke(string $subscriptionId, array $subscriptionPrototype, callable $relay) : \Generator {
        $subscription = new Filters($subscriptionPrototype);
        Subscriptions::subscribe($subscriptionId, $subscription, $relay);
        
        yield from map(filter($this->events, $subscription), partial_left([Message::class, 'requestedEvent'], $subscriptionId));
        yield Message::eose($subscriptionId);
    }
    
    private function file(Signed $event) {
        return $this->store . DIRECTORY_SEPARATOR . $event->id . '.php';
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        return array_key_exists($offset, $this->events);
    }

    #[\Override]
    public function offsetGet(mixed $offset): Signed {
        return $this->events[$offset];
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $event): void {
        if (is_null($offset)) {
            $offset = $event->id;
            Subscriptions::apply($event);
        }
        $this->events[$offset] = $event;
        file_put_contents($this->file($event), '<?php return ' . var_export($event, true) . ';');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        unlink($this->file($this->events[$offset]));
        unset($this->events[$offset]);
    }
}
