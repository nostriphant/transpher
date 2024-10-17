<?php

namespace rikmeijer\Transpher;
use function \Functional\map, \Functional\filter, \Functional\partial_left;
use rikmeijer\Transpher\Relay\Filters;
use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\Nostr\Event;

/**
 * Description of Directory
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Directory implements Relay\Store, \Iterator {

    private array $events = [];
        
    public function __construct(private string $store) {
        foreach (glob($store . DIRECTORY_SEPARATOR . '*.php') as $event_file) {
            $event = include $event_file;
            $this->events[$event->id] = $event;
        }   
    }
    
    public function __invoke(Filters $subscription) : callable {
        return fn(string $subscriptionId) => map(filter($this->events, $subscription), partial_left([Factory::class, 'requestedEvent'], $subscriptionId));
    }
    
    private function file(Event $event) {
        return $this->store . DIRECTORY_SEPARATOR . $event->id . '.php';
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        return array_key_exists($offset, $this->events);
    }

    #[\Override]
    public function offsetGet(mixed $offset): Event {
        return $this->events[$offset];
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $event): void {
        if (is_null($offset)) {
            $offset = $event->id;
        }
        $this->events[$offset] = $event;
        file_put_contents($this->file($event), '<?php return ' . var_export($event, true) . ';');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        unlink($this->file($this->events[$offset]));
        unset($this->events[$offset]);
    }
    
    #[\Override]
    public function current(): array {
        return \current($this->events);
    }

    #[\Override]
    public function key(): string {
        return \key($this->events);
    }

    #[\Override]
    public function next(): void {
        \next($this->events);
    }

    #[\Override]
    public function rewind(): void {
        \reset($this->events);
    }

    #[\Override]
    public function valid(): bool {
        return \current($this->events) !== false;
    }
}
