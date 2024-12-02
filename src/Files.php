<?php

namespace nostriphant\Transpher;

readonly class Files {

    public function __construct(private string $path, private Relay\Store $store) {
        is_dir($path) || mkdir($path);

        foreach (glob($path . DIRECTORY_SEPARATOR . '*') as $file) {
            if (is_file($file) === false) {
                continue;
            }

            $events_path = self::makeEventsPath($file);
            if (is_dir($events_path) === false) {
                unlink($file);
                continue;
            }
            $event_files = glob($events_path . DIRECTORY_SEPARATOR . '*');
            if (count($event_files) === 0) {
                rmdir($events_path);
                unlink($file);
                continue;
            }

            foreach ($event_files as $event_file) {
                $event_id = basename($event_file);
                if (isset($this->store[$event_id]) === false) {
                    unlink($event_file);
                }
            }

            if (count(glob($events_path . DIRECTORY_SEPARATOR . '*')) === 0) {
                rmdir($events_path);
                unlink($file);
                continue;
            }
        }
    }


    static function makeEventsPath(string $file_path) {
        return $file_path . '.events';
    }

    public function __invoke(string $hash): object {
        return new class($this->path . DIRECTORY_SEPARATOR . $hash, $this->store) {

            public function __construct(private string $path, private Relay\Store $store) {
                
            }

            public function __invoke(): ?string {
                if (func_num_args() === 0) {
                    return file_get_contents($this->path);
                }

                list($event_id, $remote_file) = func_get_args();
                if (isset($this->store[$event_id]) === false) {
                    return null;
                }

                $remote_handle = fopen($remote_file, 'r');
                $local_handle = fopen($this->path, 'w');
                while ($buffer = fread($remote_handle, 512)) {
                    fwrite($local_handle, $buffer);
                }
                fclose($remote_handle);
                fclose($local_handle);

                $events_path = Files::makeEventsPath($this->path);
                is_dir($events_path) || mkdir($events_path);
                touch($events_path . '/' . $event_id);
                return null;
            }
        };
    }
}
