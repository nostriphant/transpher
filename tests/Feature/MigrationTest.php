<?php

use nostriphant\NIP01Tests\Functions as NIP01TestFunctions;
use nostriphant\TranspherTests\AcceptanceCase;
use nostriphant\NIP19\Bech32;

it('migrates old events to new structure', function (): void {
    
    $event = call_user_func(new nostriphant\NIP59\Rumor(
                    created_at: time(),
                    pubkey: NIP01TestFunctions::pubkey_recipient(),
                    kind: 3,
                    content: '',
                    tags: [
                        ["p", NIP01TestFunctions::pubkey_sender(), \nostriphant\TranspherTests\AcceptanceCase::relay_url(port:'8089'), "bob"],
                    ]
            ), NIP01TestFunctions::key_recipient());
    
    $data_dir = AcceptanceCase::data_dir('8089');
    $events_dir = $data_dir . '/events';
    is_dir($events_dir) || mkdir($events_dir);
    $event_file = $events_dir . DIRECTORY_SEPARATOR . $event->id . '.php';
    file_put_contents($event_file, '<?php return ' . var_export($event, true) . ';');
    
    $relay = AcceptanceCase::bootRelay(AcceptanceCase::relay_url('tcp://', '8089'), [
        'AGENT_NSEC' => (string) 'nsec1ffqhqzhulzesndu4npay9rn85kvwyfn8qaww9vsz689pyf5sfz7smpc6mn',
        'RELAY_URL' => AcceptanceCase::relay_url(port:'8089'),
        'RELAY_OWNER_NPUB' => (string) Bech32::npub(NIP01TestFunctions::pubkey_recipient()),
        'RELAY_NAME' => 'Really relay',
        'RELAY_DESCRIPTION' => 'This is my dev relay',
        'RELAY_CONTACT' => 'transpher@nostriphant.dev',
        'RELAY_DATA' => $data_dir,
        'RELAY_LOG_LEVEL' => 'DEBUG',
        'LIMIT_EVENT_CREATED_AT_LOWER_DELTA' => 60 * 60 * 72, // to accept NIP17 pdm created_at randomness
    ]);
    
    $relay();
    
    expect($event_file)->not()->toBeFile();
    rmdir($data_dir . '/events/');
});
