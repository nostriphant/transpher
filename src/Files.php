<?php

namespace nostriphant\Transpher;

readonly class Files {

    public function __construct(private string $path) {
        
    }

    public function __invoke(string $hash): object {
        return new class($this->path . DIRECTORY_SEPARATOR . $hash) {

            public function __construct(private string $path) {

            }

            public function __invoke(): ?string {
                if (func_num_args() === 0) {
                    return file_get_contents($this->path);
                }

                list($event_id, $remote_file) = func_get_args();
                $remote_handle = fopen($remote_file, 'r');
                $local_handle = fopen($this->path, 'w');
                while ($buffer = fread($remote_handle, 512)) {
                    fwrite($local_handle, $buffer);
                }
                fclose($remote_handle);
                fclose($local_handle);

                $events_path = $this->path . '.events';
                is_dir($events_path) || mkdir($events_path);
                touch($events_path . '/' . $event_id);
                return null;
            }
        };
    }
}
