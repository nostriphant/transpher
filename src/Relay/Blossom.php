<?php

namespace nostriphant\Transpher\Relay;

class Blossom implements \Amp\Http\Server\RequestHandler {

    public function __construct(
            private \nostriphant\Transpher\Files $files,
            private \Amp\Http\Server\Router $router
    ) {
        $router->addRoute('GET', '/{file:\w+}', $this);
    }

    #[\Override]
    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Http\Server\Response {
        if (strcasecmp($request->getMethod(), 'HEAD') === 0) {
            return new \Amp\Http\Server\Response(headers: ['Content-Type' => 'text/plain'], body: '');
        } else {
            $args = $request->getAttribute(Router::class);
            return new \Amp\Http\Server\Response(
                    headers: ['Content-Type' => 'text/plain'],
                    body: ($this->files)($args['file'])()
            );
        }
    }
}
