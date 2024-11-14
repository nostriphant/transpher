<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Nostr\Event;
use nostriphant\Transpher\Alternate;

class Kind1 implements Kind {

    #[\Override]
    public function __construct(private \nostriphant\Transpher\Relay\Store $store, private string $files) {

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
                        $remote_handle = fopen($value, 'r');
                        break;

                    case 'x':
                        $local_file = $this->files . '/' . $value;
                        break;
                }
            }

            if (isset($remote_handle) === false) {
                return;
            } elseif (isset($local_file) === false) {
                return;
            }

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
}
