<?php

namespace Transpher\WebSocket;

use Amp\Websocket\WebsocketClient;
use Psr\Log\LoggerInterface;
use Transpher\Nostr\Relay\Sender;

/**
 * Description of Reply
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class SendNostr implements Sender {
    
    public function __construct(private string $action, private WebsocketClient $client, private LoggerInterface $log) {}
    
    #[\Override]
    public function __invoke(mixed $json) : bool {
        $text = \Transpher\Nostr::encode($json);
        $this->log->info($this->action . ' message ' . $text);
        $this->client->sendText($text);
        return true;
    }
    
}
