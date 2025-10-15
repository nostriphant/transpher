<?php

namespace nostriphant\Transpher\Nostr;

use Amp\Websocket\WebsocketClient;
use nostriphant\NIP01\Nostr;
use nostriphant\NIP01\Message;

readonly class Send implements Transmission {
    
    public function __construct(private WebsocketClient $client) {
        
    }

    #[\Override]
    public function __invoke(mixed $json): bool {
        if ($json instanceof Message) {
            $text = $json;
        } else {
            $text = Nostr::encode($json);
        }
        $this->client->sendText($text);
        return true;
    }
    
}
