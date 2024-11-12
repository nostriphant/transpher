<?php

namespace nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Key;

/**
 * Description of InformationDocument
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class InformationDocument {
    static function generate(string $name, string $description, string $npub, string $contact) {
        return [
            "name" => $name,
            "description" => $description,
            "pubkey" => \nostriphant\Transpher\Nostr\Bech32::fromNpub($npub),
            "contact" => $contact,
            "supported_nips" => [1, 2, 9, 11, 12, 13, 16, 20, 22, 33, 45, 94],
            "software" => 'Transpher',
            "version" => 'dev'
        ];
    }
}
