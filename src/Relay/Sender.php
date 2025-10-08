<?php

namespace nostriphant\Transpher\Relay;

class Sender {
    
    private function __construct(private string $action, private WebsocketClient $client) {   
    }
    
    static function __callStatic(string $name, array $arguments): mixed {
        return new self(ucwords($name), ...$arguments);
    }
    
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
