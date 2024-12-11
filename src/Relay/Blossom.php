<?php

namespace nostriphant\Transpher\Relay;

readonly class Blossom {

    public function __construct(private \nostriphant\Transpher\Files $files) {
        
    }

    public function __invoke(\Amp\Http\Server\Request $request): \Amp\Http\Server\Response {
        $args = $request->getAttribute(\Amp\Http\Server\Router::class);
        $file = ($this->files)($args['file']);
        $headers = ['Content-Type' => 'text/plain', 'Content-Length' => filesize($file->path)];
        return new \Amp\Http\Server\Response(headers: $headers, body: $file());
    }
}
