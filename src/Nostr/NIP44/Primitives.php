<?php

namespace rikmeijer\Transpher\Nostr\NIP44;

/**
 * Description of Primitives
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Primitives {

    public static function uInt8($i) {
        return is_int($i) ? pack("C", $i) : unpack("C", $i)[1];
    }

    public static function uInt16($i, $endianness = false) {
        $f = is_int($i) ? "pack" : "unpack";

        if ($endianness === true) {  // big-endian
            $i = $f("n", $i);
        } else if ($endianness === false) {  // little-endian
            $i = $f("v", $i);
        } else if ($endianness === null) {  // machine byte order
            $i = $f("S", $i);
        }

        return is_array($i) ? $i[1] : $i;
    }

}
