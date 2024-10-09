<?php
namespace rikmeijer\Transpher\Nostr\Message;

/**
 * Description of Subscribe
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Subscribe implements Subscribe\Chain {
    
    private string $subscriptionId;
    
    public function __construct() {
        $this->subscriptionId = bin2hex(random_bytes(32));
    }
    
    #[\Override]
    public function __invoke() : array {
        return ['REQ', $this->subscriptionId];
    }
}
