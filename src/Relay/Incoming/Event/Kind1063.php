<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Event;

class Kind1063 {

    public function __construct(private \nostriphant\Transpher\Relay\Store $store, private string $files) {

    }

    public function __invoke(Event $event): void {
        $urls = Event::extractTagValues($event, 'url');
        $remote_handle = fopen($urls[0], 'r');

        $x = Event::extractTagValues($event, 'x');
        $local_handle = fopen($this->files . '/' . $x[0], 'w');
        while ($buffer = fread($remote_handle, 512)) {
            fwrite($local_handle, $buffer);
        }
        fclose($remote_handle);
        fclose($local_handle);
    }
}
