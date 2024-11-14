<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Relay\Incoming\Alternate;

class Kind1063 implements Kind {

    #[\Override]
    public function __construct(private \nostriphant\Transpher\Relay\Store $store, private string $files) {

    }

    #[\Override]
    static function validate(Event $event): Alternate {
        if (Event::hasTag($event, 'url') === false) {
            return Alternate::rejected('missing url-tag');
        } elseif (Event::hasTag($event, 'x') === false) {
            return Alternate::rejected('missing x-tag');
        } elseif (Event::hasTag($event, 'ox') === false) {
            return Alternate::rejected('missing ox-tag');
        }
        return Alternate::accepted($event);
    }

    #[\Override]
    public function __invoke(Event $event): void {
        $urls = Event::extractTagValues($event, 'url');
        $remote_handle = fopen($urls[0], 'r');

        $x = Event::extractTagValues($event, 'x');
        $local_file = $this->files . '/' . $x[0];
        $local_handle = fopen($local_file, 'w');
        while ($buffer = fread($remote_handle, 512)) {
            fwrite($local_handle, $buffer);
        }
        fclose($remote_handle);
        fclose($local_handle);

        mkdir($local_file . '.events');
        touch($local_file . '.events/' . $event->id);
    }
}
