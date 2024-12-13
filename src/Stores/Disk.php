<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\Transpher\Stores\Memory;
use nostriphant\Transpher\Relay\Store;

class Disk implements Store {

    const NIP01_EVENT_SPLITOFF_TIME = 1732125327;

    private Memory $memory;
    private Subscription $whitelist;

    public function __construct(private string $store, array $whitelist_prototypes) {
        $events = [];
        is_dir($store) || mkdir($store);
        $this->whitelist = Subscription::make($whitelist_prototypes, \nostriphant\Transpher\Relay\Condition::class);
        self::walk_store($store, function (Event $event) use (&$events) {
            if (call_user_func($this->whitelist, $event) === false) {
                unlink(self::file($this->store, $event->id));
                return false;
            } else {
                $events[$event->id] = $event;
                return true;
            }
        });

        $this->memory = new Memory($events, $whitelist_prototypes);
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
        if (call_user_func($this->whitelist, $event) !== false) {
            $this->memory[$offset] = $event;
            self::write($this->store, $event);
        }
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        unlink(self::file($this->store, $offset));
        unset($this->memory[$offset]);
    }

    #[\Override]
    public function __invoke(array ...$filter_prototypes): Results {
        return call_user_func_array($this->memory, $filter_prototypes);
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        return isset($this->memory[$offset]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): ?Event {
        return $this->memory[$offset];
    }

    #[\Override]
    public function count(): int {
        return count($this->memory);
    }
}
