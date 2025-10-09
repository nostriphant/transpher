<?php

namespace nostriphant\Transpher\Amp;

use Amp\Websocket\WebsocketClient;
use nostriphant\Transpher\Relay\Sender;
use nostriphant\NIP01\Nostr;
use nostriphant\NIP01\Message;

readonly class SendNostr implements Sender {
    
    private function __construct(private string $action, private WebsocketClient $client) {
        
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
    
    static function __callStatic(string $name, array $arguments): mixed {
        return new self(ucwords($name), ...$arguments);
    }
    
}
