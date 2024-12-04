<?php

namespace nostriphant\Transpher\Relay;

readonly class Blossom implements \Amp\Http\Server\RequestHandler {

    private function __construct(private \nostriphant\Transpher\Files $files) {
        
    }

    static function connect(
            \nostriphant\Transpher\Files $files,
            \Amp\Http\Server\Router $router
    ) {
        $router->addRoute('HEAD', '/{file:\w+}', new self($files));
        $router->addRoute('GET', '/{file:\w+}', new self($files));
    }

    #[\Override]
    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Http\Server\Response {
        $args = $request->getAttribute(\Amp\Http\Server\Router::class);
        $file = ($this->files)($args['file']);
        $headers = ['Content-Type' => 'text/plain', 'Content-Length' => filesize($file->path)];
        return new \Amp\Http\Server\Response(headers: $headers, body: $file());
    }
}
