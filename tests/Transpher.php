<?php

namespace nostriphant\TranspherTests;

use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;

readonly class Transpher {
    private Relay $relay;
    private Agent $agent;
    public string $data_directory;
    public string $url;
    public string $ws;

    public function __construct(string $port, \nostriphant\NIP01\Key $owner, ?array $whitelisted_npubs) {
        $this->data_directory = ROOT_DIR . '/data/relay_' . $port;
        is_dir($this->data_directory) || mkdir($this->data_directory);

        $this->url = 'http://127.0.0.1:' . $port;
        $this->ws = 'ws://127.0.0.1:' . $port;

        is_file($this->data_directory . '/transpher.sqlite') && unlink($this->data_directory . '/transpher.sqlite');
        expect($this->data_directory . '/transpher.sqlite')->not()->toBeFile();

        $relay_env = [
            'AGENT_NSEC' => (string) 'nsec1ffqhqzhulzesndu4npay9rn85kvwyfn8qaww9vsz689pyf5sfz7smpc6mn',
            'RELAY_URL' => $this->ws,
            'RELAY_OWNER_NPUB' => (string) Bech32::npub(Key::derivePublicKey($owner)),
            'RELAY_NAME' => 'Really relay',
            'RELAY_DESCRIPTION' => 'This is my dev relay',
            'RELAY_CONTACT' => 'transpher@nostriphant.dev',
            'RELAY_DATA' => $this->data_directory,
            'RELAY_LOG_LEVEL' => 'DEBUG',
            'LIMIT_EVENT_CREATED_AT_LOWER_DELTA' => 60 * 60 * 72, // to accept NIP17 pdm created_at randomness
            'BLOSSOM_SERVER_KEY' => 'ae89403ee4f95cac13c9984f588ad92cee48c202f52c6f96d4d5c053d8332c85',
        ];
        if (isset($whitelisted_npubs)) {
            $relay_env['RELAY_WHITELISTED_AUTHORS_ONLY'] = 1;
            $relay_env['RELAY_WHITELISTED_AUTHORS'] = implode(',', $whitelisted_npubs);

        }

        $this->relay = new Relay('tcp://127.0.0.1:' . $port, $relay_env);

        $this->agent = new Agent($port, [
            'RELAY_OWNER_NPUB' => (string) Bech32::npub(Key::derivePublicKey($owner)),
            'AGENT_NSEC' => (string) 'nsec1ffqhqzhulzesndu4npay9rn85kvwyfn8qaww9vsz689pyf5sfz7smpc6mn',
            'RELAY_URL' => $relay_env['RELAY_URL'],
            'AGENT_LOG_LEVEL' => 'DEBUG',
        ]);
        sleep(1);
    }

    public function __invoke() {
        call_user_func($this->relay);
        call_user_func($this->agent);
    }
}
