<?php

namespace rikmeijer\Transpher;

use rikmeijer\Transpher\Nostr;
use function Amp\Websocket\Client\connect;
use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\Nostr\Key;


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
    
    public function privateDirectMessage(Key $sender, string $recipient_npub, string $message) {
        $note = Factory::privateDirect($sender, Key::convertBech32ToHex($recipient_npub), str_replace('{relay_url}', $this->url, $message));
        $this->send($note);
    }
    
    public function json(mixed $json) : void {
        $this->send(Nostr::encode($json));
    }

    public function send(string $text): void {
        $this->connection->sendText($text);
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
            $buffer = $message->buffer();
            $payload = Nostr::decode($buffer);
            ($this->onjson_callback)([$this, 'ignore'], $payload);
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
