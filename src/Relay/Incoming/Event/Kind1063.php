<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Relay\Incoming\Constraint;

class Kind1063 implements Kind {

    #[\Override]
    public function __construct(private \nostriphant\Transpher\Relay\Store $store, private string $files) {

    }

    #[\Override]
    static function validate(Event $event): Constraint {
        if (Event::hasTag($event, 'url') === false) {
            return Constraint::reject('missing url-tag');
        } elseif (Event::hasTag($event, 'x') === false) {
            return Constraint::reject('missing x-tag');
        } elseif (Event::hasTag($event, 'ox') === false) {
            return Constraint::reject('missing ox-tag');
        }
        return Constraint::accept();
    }

    #[\Override]
    public function __invoke(Event $event): void {
        $urls = Event::extractTagValues($event, 'url');
        if (empty($urls)) {
            throw new \InvalidArgumentException('missing url-tag');
        }
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
