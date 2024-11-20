<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\NIP01\Event;
use nostriphant\FunctionalAlternate\Alternate;

class Kind1 implements Kind {

    #[\Override]
    public function __construct(private \nostriphant\Transpher\Relay\Store $store, private \nostriphant\Transpher\Files $files) {
        
    }

    #[\Override]
    static function validate(Event $event): Alternate {
        return Alternate::accepted($event);
    }

    #[\Override]
    public function __invoke(Event $event): void {
        if (Event::hasTag($event, 'imeta') === false) {
            return;
        }

        foreach (Event::extractTagValues($event, 'imeta') as $imeta) {
            foreach ($imeta as $imeta_value) {
                list($property, $value) = explode(' ', $imeta_value);
                switch ($property) {
                    case 'url':
                        $remote_file = $value;
                        break;

                    case 'x':
                        $hash = $value;
                        break;
                }
            }

            if (isset($remote_file) === false) {
                return;
            } elseif (isset($hash) === false) {
                return;
            }

            ($this->files)($hash)($event->id, $remote_file);
        }
    }
}
