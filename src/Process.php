<?php

namespace Transpher;

/**
 * Description of Process
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Process {
    
    private mixed $process;
    
    public function __construct(string $process_id, array $cmd, array $env, callable $runtest) {
        $cwd = getcwd();
        $output_file = $cwd . "/logs/{$process_id}-output.log";
        $error_file = $cwd . "/logs/{$process_id}-errors.log";
        $descriptorspec = [
            0 => ["pipe", "r"],  
            1 => ["file", $output_file, "w"],  
            2 => ["file", $error_file, "w"]
        ];

        $this->process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);
        while ($runtest(file_get_contents($output_file))  === false && (is_file($error_file) === false || $runtest(file_get_contents($error_file)) === false)) {
            // wait till process is ready
        }
    }
    
    public function __destruct() {
        $this();
    }
    
    public function __invoke(int $signal = 15) : array {
        if ($this->process === false) {
            return [];
        } elseif (is_resource($this->process) === false) {
            return [];
        }
        
        $status = proc_get_status($this->process);

        proc_terminate($this->process, 15);
        while ($status['running']) {
            $status = proc_get_status($this->process);
        }

        proc_close($this->process);
        return $status;
    }
    
    static function start(string $process_id, array $cmd, array $env, callable $runtest, callable $running) : void {
        $process = new static($process_id, $cmd, $env, $runtest);
        $running($process);
    }
    
    static function gracefulExit() {
        pcntl_signal(SIGTERM, function(int $sig, array $info) {
            printf("Received INT signal, exiting gracefully\n");
            exit(0);
        }, false );
        pcntl_async_signals(true);
    }
}
