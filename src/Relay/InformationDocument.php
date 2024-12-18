<?php

namespace nostriphant\Transpher\Relay;

class InformationDocument {
    static function generate(string $name, string $description, string $pubkey, string $contact) {
        return [
            "name" => $name,
            "description" => $description,
            "pubkey" => $pubkey,
            "contact" => $contact,
            "supported_nips" => [1, 2, 9, 11, 12, 13, 16, 20, 22, 33, 45, 92, 94],
            "software" => 'Transpher',
            "version" => TRANSPHER_VERSION
        ];
    }
}
