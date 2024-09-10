<?php

namespace Transpher;

/**
 * Description of Process
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Process {
    static function start(string $process_id, array $cmd, array $env, callable $runtest, callable $running) : callable {
        $cwd = getcwd();
        
        $output_file = $cwd . "/logs/{$process_id}-errors.log";
        
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["file", $cwd . "/logs/{$process_id}-output.log", "w"],  // stdout is a pipe that the child will write to
            2 => ["file", $cwd . "/logs/{$process_id}-errors.log", "w"] // stderr is a file to write to
        ];

        $process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);
        sleep(1);
        
        if ($process === false) {
            return fn() => [];
        } elseif (is_resource($process)) {
            fclose($pipes[0]);
            
            $running($killProcess = function(int $signal = 15) use ($process_id, $process, $pipes, $output_file) : array {
                $status = proc_get_status($process);
                
                proc_terminate($process, $signal);
                pcntl_waitpid($status['pid'], $pcntl_status, WUNTRACED);
                while ($status['running']) {
                    $status = proc_get_status($process);
                }

                proc_close($process);
                return $status;
            });
            
            return $killProcess;
        }
        return fn() => [];
    }
}
