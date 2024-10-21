<?php

namespace rikmeijer\Transpher;
use function \Functional\map,
             \Functional\select,
             \Functional\partial_left;
use rikmeijer\Transpher\Nostr\Filters;
use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\Nostr\Event;

/**
 * Description of Directory
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Directory implements Relay\Store {

    use Nostr\EventsStore {
        offsetSet as eventsOffsetSet;
        offsetUnset as eventsOffsetUnset;
        __construct as eventsConstructor;
    }

    public function __construct(private string $store) {
        $events = [];
        foreach (glob($store . DIRECTORY_SEPARATOR . '*.php') as $event_file) {
            $event_data = include $event_file;
            $event = is_array($event_data) ? Event::__set_state($event_data) : $event_data;
            $events[$event->id] = $event;
        }
        $this->eventsConstructor($events);
    }

    private function file(string $event_id) {
        return $this->store . DIRECTORY_SEPARATOR . $event_id . '.php';
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $event): void {
        $this->eventsOffsetSet($offset, $event);
        file_put_contents($this->file($offset ?? $event->id), '<?php return ' . var_export($event, true) . ';');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        unlink($this->file($offset));
        $this->eventsOffsetUnset($offset);
    }
}
