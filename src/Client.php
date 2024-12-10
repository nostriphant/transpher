<?php

namespace nostriphant\Transpher;

use nostriphant\NIP01\Nostr;
use function Amp\Websocket\Client\connect;
use nostriphant\Transpher\Nostr\Message\Factory;
use nostriphant\NIP01\Key;
use nostriphant\NIP01\Message;

class Client {

    private bool $listening = false;
    
    public function __construct(readonly public string $url) {
        $this->onJson(fn() => null);
        $this->connection = connect($this->url);
    }
    
    public function privateDirectMessage(Key $sender, string $recipient_pubkey, string $message) {
        $note = Factory::privateDirect($sender, $recipient_pubkey, str_replace('{relay_url}', $this->url, $message));
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
    
    public function receive(int $timeout): ?\Amp\Websocket\WebsocketMessage {
        return $this->connection->receive($timeout > 0 ? new \Amp\TimeoutCancellation($timeout) : null);
    }
    
    
    public function start(int $timeout): void {
        $this->listening = true;
        while ($this->listening && ($message = $this->receive($timeout))) {
            $buffer = $message->buffer();
            $payload = Message::decode($buffer);
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
