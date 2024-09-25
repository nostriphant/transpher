<?php

namespace Transpher\Nostr\Message\Subscribe;

/**
 * Description of Close
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Close {
    public function __construct(private string $subscriptionId) {
    }
    public function __invoke() : array {
        return ['CLOSE', $this->subscriptionId];
    }
}
