<?php

namespace nostriphant\TranspherTests\Feature;

readonly class Process {
    
    private mixed $process;
    
    public function __construct(string $process_id, array $cmd, array $env, callable $runtest) {
        $cwd = getcwd();
        $output_file = $cwd . "/logs/{$process_id}-output.log";
        $error_file = $cwd . "/logs/{$process_id}-errors.log";
        
        $meta = fopen($cwd . "/logs/{$process_id}-meta.log", 'w');
        fwrite($meta, 'Environment for ' . implode(' ', $cmd) . ': '.PHP_EOL);
        foreach ($env as $env_key => $env_value) {
            fwrite($meta, $env_key.'='.$env_value.PHP_EOL);
        }
        fwrite($meta, str_repeat('=', 50) . PHP_EOL);
        fwrite($meta, 'Running command... ');
        
        $descriptorspec = [
            0 => ["pipe", "r"],  
            1 => ["file", $output_file, "w"],  
            2 => ["file", $error_file, "w"]
        ];

        $this->process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);
        if ($this->process === false) {
            fwrite($meta, 'FAIL' . PHP_EOL);
            
        }
        fwrite($meta, 'OK' . PHP_EOL);
        
        
        $output = fopen($output_file, 'r');
        $error = fopen($error_file, 'r');
        
        do {
            $line = fread($output, 512);
            $errline = fread($error, 512);
            
            $result = $runtest($line) || $runtest($errline);
            if (!empty($line) || !empty($errline)) {
                fwrite($meta, 'SCANNING FOR  RUNNING EVIDENCE IN OUTPUT>> `' . $line . '` -- `'.$errline.'` >> ' . var_export($result, true) . PHP_EOL);
            }
            
        } while ($result === false);
        fwrite($meta, 'FOUND RUNNING EVIDENCE IN OUPUT>> `' . $line . '` -- `'.$errline.'`' . PHP_EOL);
        fclose($output);
        fclose($error);
        fclose($meta);
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
    
    public function __destruct() {
        $this->__invoke();
    }
    
    static function gracefulExit() {
        pcntl_signal(SIGTERM, function(int $sig, array $info) {
            printf("Received INT signal, exiting gracefully\n");
            exit(0);
        }, false );
        pcntl_async_signals(true);
    }
}
