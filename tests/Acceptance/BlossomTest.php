<?php

use nostriphant\TranspherTests\AcceptanceCase;

describe('blossom', function() {

    it('is enabled and functional, simple GET request to an prewritten blob', function () {
        $data_dir = AcceptanceCase::data_dir('8091');

        $files_dir = $data_dir . '/files';
        is_dir($files_dir) || mkdir($files_dir);
        
        $hash = nostriphant\Blossom\writeFile($files_dir, 'Hello world!');
        expect($files_dir . '/' . $hash)->toBeFile();
        
        $transpher = AcceptanceCase::start_transpher('8091', $data_dir, []);
        
        list($protocol, $code, $headers, $body) = nostriphant\Blossom\request('GET', AcceptanceCase::relay_url('http://', '8091') . '/' . $hash);
        expect($code)->toBe('200');
        expect($body)->toBe('Hello world!');
        
        $transpher();
    });
    
    
});
