<?php

namespace nostriphant\Transpher\Nostr;

use nostriphant\Transpher\Nostr\NIP44\Primitives;
use nostriphant\Transpher\Nostr\NIP44\Padding;

/**
 * Description of NIP44
 * Based on: https://github.com/nbd-wtf/nostr-tools/blob/master/nip44.ts
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class NIP44 {

    static function encrypt(string $utf8_text, NIP44\MessageKeys $keys, string $salt): string {
        $padded = Padding::add($utf8_text);
        $encrypter = new NIP44\Encrypter($keys, $salt);
        return sodium_bin2base64(Primitives::uInt8(2) . $encrypter($padded), SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    static function decrypt(string $payload, NIP44\MessageKeys $keys): bool|string {
        if ($payload === '') {
            throw new \InvalidArgumentException('empty payload');
        } elseif ($payload[0] === '#') {
            throw new \InvalidArgumentException('encryption version not supported');
        }

        $decoded = base64_decode($payload);
        $version = Primitives::uInt8(substr($decoded, 0, 1));
        if ($version !== 2) {
            throw new \InvalidArgumentException('encryption version not supported');
        }

        $salt = substr($decoded, 1, 32);
        $decrypter = new NIP44\Decrypter($keys, $salt);
        return Padding::remove($decrypter($decoded));
    }
}
