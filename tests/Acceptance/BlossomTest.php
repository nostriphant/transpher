<?php

use nostriphant\TranspherTests\AcceptanceCase;

describe('blossom', function() {

    it('is enabled and functional, simple GET request to an prewritten blob', function () {
        $data_dir = AcceptanceCase::data_dir('8091');

        $files_dir = $data_dir . '/files';
        is_dir($files_dir) || mkdir($files_dir);
        
        $hash = nostriphant\Blossom\writeFile($files_dir, 'Hello world!');
        expect($files_dir . '/' . $hash)->toBeFile();
        
        $sender_key = nostriphant\NIP01\Key::fromHex('a71a415936f2dd70b777e5204c57e0df9a6dffef91b3c78c1aa24e54772e33c3');
//        unset($authorization['key']);
//        $sender_pubkey = $authorization['pubkey'] ?? ; // 15b7c080c36d1823acc5b27b155edbf35558ef15665a6e003144700fc8efdb4f
        
        $transpher = AcceptanceCase::start_transpher('8091', $data_dir, $sender_key, []);
        
        list($protocol, $code, $headers, $body) = nostriphant\Blossom\request('GET', AcceptanceCase::relay_url('http://', '8091') . '/' . $hash, authorization: ['t' => 'get', 'x' => $hash]);
        expect($code)->toBe('200');
        expect($body)->toBe('Hello world!');
        
        $transpher();
    });
    
    
});
