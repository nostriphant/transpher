<?php

namespace nostriphant\Transpher;

use nostriphant\NIP01\Event;

class Directory implements Relay\Store {

    const NIP01_EVENT_SPLITOFF_TIME = 1732116770;

    use Relay\Store\Memory {
        offsetSet as eventsOffsetSet;
        offsetUnset as eventsOffsetUnset;
        __construct as eventsConstructor;
    }

    public function __construct(private string $store) {
        $events = [];
        foreach (glob($store . DIRECTORY_SEPARATOR . '*.php') as $event_file) {
            if (filectime($event_file) < self::NIP01_EVENT_SPLITOFF_TIME) {
                $event_file_contents = file_get_contents($event_file);
                file_put_contents($event_file, str_replace('return \\nostriphant\\Transpher\\Nostr\\Event::', 'return \\nostriphant\\NIP01\\Event::', $event_file_contents));
            }
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
