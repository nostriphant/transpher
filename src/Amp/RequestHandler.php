<?php

namespace nostriphant\Transpher\Amp;

use Amp\Http\HttpStatus;
use Amp\Websocket\Server\Websocket;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler as IRequestHandler;
use Amp\Http\Server\Response;
use \nostriphant\Transpher\Relay\InformationDocument;

class RequestHandler implements IRequestHandler {
    public function __construct(private Websocket $websocket) {
    }
    public function __call(string $name, array $arguments): mixed {
        return $this->websocket->$name(...$arguments);
    }
    
    #[\Override]
    public function handleRequest(Request $request): Response {
        $response =  $this->websocket->handleRequest($request);
        if ($response->getStatus() === HttpStatus::UPGRADE_REQUIRED) {
            return new Response(
                headers: ['Content-Type' => 'application/json'],
                body: json_encode(InformationDocument::generate(
                    $_SERVER['RELAY_NAME'],
                    $_SERVER['RELAY_DESCRIPTION'],
                    (new \nostriphant\NIP19\Bech32($_SERVER['RELAY_OWNER_NPUB']))(),
                                    $_SERVER['RELAY_CONTACT']
                            ))
            );
        }
        
        return $response;
    }
}
