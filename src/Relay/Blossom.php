<?php

namespace nostriphant\Transpher\Relay;

readonly class Blossom implements \Amp\Http\Server\RequestHandler {

    private function __construct(private \nostriphant\Transpher\Files $files) {
        
    }

    static function connect(
            \nostriphant\Transpher\Files $files,
            \Amp\Http\Server\Router $router
    ) {
        $router->addRoute('GET', '/{file:\w+}', new self($files));
    }

    #[\Override]
    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Http\Server\Response {
        if (strcasecmp($request->getMethod(), 'HEAD') === 0) {
            return new \Amp\Http\Server\Response(headers: ['Content-Type' => 'text/plain'], body: '');
        } else {
            $args = $request->getAttribute(\Amp\Http\Server\Router::class);
            return new \Amp\Http\Server\Response(
                    headers: ['Content-Type' => 'text/plain'],
                    body: ($this->files)($args['file'])()
            );
        }
    }
}
