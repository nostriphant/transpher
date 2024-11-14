<?php

namespace nostriphant\Transpher;

readonly class Files {

    public function __construct(private string $path) {
        
    }

    public function __invoke(string $hash): object {
        return new class($this->path . DIRECTORY_SEPARATOR . $hash) {

            public function __construct(private string $path) {

            }

            public function __invoke(string $event_id, string $remote_file): void {
                $remote_handle = fopen($remote_file, 'r');
                $local_handle = fopen($this->path, 'w');
                while ($buffer = fread($remote_handle, 512)) {
                    fwrite($local_handle, $buffer);
                }
                fclose($remote_handle);
                fclose($local_handle);

                mkdir($this->path . '.events');
                touch($this->path . '.events/' . $event_id);
            }
        };
    }
}
