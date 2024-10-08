<?php

namespace Transpher;
use function \Functional\map, \Functional\filter;
use Transpher\Nostr\Relay\Subscriptions;
/**
 * Description of Directory
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Directory implements \ArrayAccess, \Iterator {

    private array $events = [];
    private Subscriptions $subscriptions;

    public function __construct(private string $store) {
        $this->subscriptions = Subscriptions::makeStore();
        foreach (glob($store . DIRECTORY_SEPARATOR . '*.php') as $event_file) {
            $event = include $event_file;
            $this->events[$event['id']] = $event;
        }   
    }
    
    public function __invoke(callable $subscription) : array {
        return filter($this->events, $subscription);
    }
    
    private function file(array $event) {
        return $this->store . DIRECTORY_SEPARATOR . $event['id'] . '.php';
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        return array_key_exists($offset, $this->events);
    }

    #[\Override]
    public function offsetGet(mixed $offset): array {
        return $this->events[$offset];
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $event): void {
        if (is_null($offset)) {
            $offset = $event['id'];
            call_user_func($this->subscriptions, $event);
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
