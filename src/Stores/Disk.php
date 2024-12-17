<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Relay\Engine;

class Disk implements Engine {

    use MemoryWrapper {
        __construct As MW_Construct;
        offsetSet As MW_offsetSet;
        offsetUnset As MW_offsetUnset;
    }

    const NIP01_EVENT_SPLITOFF_TIME = 1732125327;

    public function __construct(public string $store) {
        $events = [];
        is_dir($store) || mkdir($store);
        self::walk_store($store, function (Event $event) use (&$events) {
            $events[$event->id] = $event;
        });

        $this->MW_Construct($events);
    }

    static function walk_store(string $store, callable $callback): int {
        $count = 0;
        foreach (glob($store . DIRECTORY_SEPARATOR . '*.php') as $event_file) {
            if (filectime($event_file) < self::NIP01_EVENT_SPLITOFF_TIME) {
                $event_file_contents = file_get_contents($event_file);
                $event_file_contents = str_replace('return \\nostriphant\\Transpher\\Nostr\\Event::', 'return \\nostriphant\\NIP01\\Event::', $event_file_contents);
                $event_file_contents = str_replace('return \\nostriphant\\NP01\\Event::', 'return \\nostriphant\\NIP01\\Event::', $event_file_contents);
                file_put_contents($event_file, $event_file_contents);
            }

            $event_data = include $event_file;
            if ($callback(is_array($event_data) ? Event::__set_state($event_data) : $event_data)) {
                $count++;
            }
        }
        return $count;
    }

    static function write(string $path, Event $event): void {
        is_dir($path) || mkdir($path);
        file_put_contents(self::file($path, $event->id), '<?php return ' . var_export($event, true) . ';');
    }

    static function file(string $store, string $event_id) {
        return $store . DIRECTORY_SEPARATOR . $event_id . '.php';
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $event): void {
        $this->MW_offsetSet($offset, $event);
        self::write($this->store, $event);
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        unlink(self::file($this->store, $offset));
        $this->MW_offsetUnset($offset);
    }

}
