<?php

namespace Transpher\Nostr\Relay;

/**
 * Description of Filter
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
interface Filter {
    public function __construct(array $filter_prototype);
    public function __invoke(array $event) : bool;
}
