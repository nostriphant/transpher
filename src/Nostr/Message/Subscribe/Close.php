<?php

namespace rikmeijer\Transpher\Nostr\Message\Subscribe;

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

    public function __toString(): string {
        return \rikmeijer\Transpher\Nostr::encode($this());
    }
}
