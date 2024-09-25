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
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["file", $output_file, "w"],  // stdout is a pipe that the child will write to
            2 => ["file", $cwd . "/logs/{$process_id}-errors.log", "w"] // stderr is a file to write to
        ];

        $this->process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);
        while ($runtest(file_get_contents($output_file)) === false) {
            // wait till process is ready
        }
        
        
        if ($this->process === false) {
        } elseif (is_resource($this->process)) {
            fclose($pipes[0]);
        }   
    }
    
    public function __invoke(int $signal = 15) : array {
        $status = proc_get_status($this->process);

        proc_terminate($this->process, $signal);
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
}
