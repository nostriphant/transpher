<?php

namespace nostriphant\TranspherTests\Feature;

readonly class Process {
    
    private mixed $process;
    
    public function __construct(string $process_id, array $cmd, array $env, callable $runtest) {
        $cwd = getcwd();
        $output_file = $cwd . "/logs/{$process_id}-output.log";
        $error_file = $cwd . "/logs/{$process_id}-errors.log";
        
        $io = new \nostriphant\Functional\IO(
            fopen('php://temp', 'r'), 
            fopen($output_file, "w"), 
            fopen($error_file, "w")
        );
        
        $process = new \nostriphant\Functional\IO\Process($io, $cwd, $env);
        $this->process = $process(...$cmd);
        
        $output = fopen($output_file, 'r');
        $error = fopen($error_file, 'r');
        
        do {
            $line = fread($output, 512);
            $result = $runtest($line);
        } while ($result === false);
        fclose($output);
        fclose($error);
    }
    
    public function __invoke(int $signal = 15) : void {        
        call_user_func($this->process, $signal);
    }
}
