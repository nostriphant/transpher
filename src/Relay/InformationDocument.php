<?php

namespace rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Key;

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
            "pubkey" => Key::convertBech32ToHex($npub),
            "contact" => $contact,
            "supported_nips" => [1, 11],
            "software" => 'Transpher',
            "version" => 'dev'
        ];
    }
}
