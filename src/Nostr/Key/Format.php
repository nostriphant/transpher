<?php

namespace rikmeijer\Transpher\Nostr\Key;

/**
 * Description of Format
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
enum Format {
    case BINARY;
    case HEXIDECIMAL;
    case BECH32;
}
