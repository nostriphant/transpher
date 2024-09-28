<?php

require __DIR__.'/bootstrap.php';

use Amp\ByteStream;
use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\SocketHttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

// Note any PSR-3 logger may be used, Monolog is only an example.
$logHandler = new StreamHandler(ByteStream\getStdout());
$logHandler->pushProcessor(new PsrLogMessageProcessor());
$logHandler->setFormatter(new ConsoleFormatter());

$logger = new Logger('server');
$logger->pushHandler($logHandler);

$requestHandler = new class() implements RequestHandler {
    public function handleRequest(Request $request) : Response
    {
        return new Response(
            status: HttpStatus::OK,
            headers: ['Content-Type' => 'application/json+nostr'],
            body: json_encode([
                "name" => $_SERVER['RELAY_NAME'],
                "description" => $_SERVER['RELAY_DESCRIPTION'],
                "pubkey" => \Transpher\Key::convertBech32ToHex($_SERVER['RELAY_OWNER_NPUB']),
                "contact" => $_SERVER['RELAY_CONTACT'],
                "supported_nips" => [1, 11],
                "software" => 'Transpher',
                "version" => 'dev'
            ]),
        );
    }
};

$errorHandler = new DefaultErrorHandler();

$server = SocketHttpServer::createForDirectAccess($logger);
$server->expose($_SERVER['argv'][1]);
$server->start($requestHandler, $errorHandler);

// Serve requests until SIGINT or SIGTERM is received by the process.
Amp\trapSignal([SIGINT, SIGTERM]);

$server->stop();