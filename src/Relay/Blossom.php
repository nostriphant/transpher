<?php

namespace nostriphant\Transpher\Relay;

readonly class Blossom {

    const ROUTES = [
        'HEAD' => '/{hash:\w+}',
        'GET' => '/{hash:\w+}'
    ];

    public function __construct(private Files $files) {
        
    }

    public function __invoke(string $hash): array {
        $file = ($this->files)($hash);
        return [
            'headers' => [
                'Content-Type' => 'text/plain',
                'Content-Length' => filesize($file->path)
            ],
            'body' => $file()
        ];
    }
}
