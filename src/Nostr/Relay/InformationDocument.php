<?php

namespace Transpher\Nostr\Relay;

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
            "pubkey" => \Transpher\Key::convertBech32ToHex($npub),
            "contact" => $contact,
            "supported_nips" => [1, 11],
            "software" => 'Transpher',
            "version" => 'dev'
        ];
    }
}
