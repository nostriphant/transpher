<?php

namespace nostriphant\Transpher\Relay\Incoming\Event\Accepted\Regular;

use nostriphant\NIP01\Event;
use nostriphant\Functional\Alternate;
use nostriphant\Transpher\Relay\Files;

class Kind1063 implements Kind {

    #[\Override]
    public function __construct(private \nostriphant\Stores\Store $store, private Files $files) {
        
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
        $x = Event::extractTagValues($event, 'x');
        ($this->files)($x[0][0])($event->id, $urls[0][0]);
    }
}
