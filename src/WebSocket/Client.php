<?php

namespace rikmeijer\Transpher\WebSocket;

use rikmeijer\Transpher\Nostr;
use function Amp\Websocket\Client\connect;


/**
 * Description of Client
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Client {

    private bool $listening = false;
    
    public function __construct(private string $url) {
        $this->onJson(fn() => null);
        $this->connection = connect($this->url);
    }
    
    public function json(mixed $json) : void {
        $this->connection->sendText(Nostr::encode($json));
    }
    public function onJson(callable $callback) {
        $this->onjson_callback = $callback;
    }
   
    private \Amp\Websocket\Client\WebsocketConnection $connection;
    private \Closure $onjson_callback;
    
    public function receive() : ?\Amp\Websocket\WebsocketMessage {
        return $this->connection->receive();
    }
    
    
    public function start() : void {
        $this->listening = true;
        while ($this->listening && ($message = $this->receive())) {
            $payload = $message->buffer();
            ($this->onjson_callback)([$this, 'ignore'], Nostr::decode($payload));
        }
    }
    
    public function ignore() : void {
        $this->listening = false;
    }
    
    public function stop() : void {
        $this->listening = false;
        $this->connection->close();
    }
    
}
