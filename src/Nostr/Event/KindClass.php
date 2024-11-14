<?php

namespace nostriphant\Transpher\Nostr\Event;

enum KindClass {
    case REGULAR;
    case REPLACEABLE;
    case EPHEMERAL;
    case ADDRESSABLE;
    case UNDEFINED;
}
