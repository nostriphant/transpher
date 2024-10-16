<?php
namespace rikmeijer\Transpher\Nostr\Message;
use function \Functional\map;

/**
 * Description of Subscribe
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Subscribe {

    private string $subscriptionId;
    private array $filters;

    public function __construct(Subscribe\Filter ...$filters) {
        $this->subscriptionId = bin2hex(random_bytes(32));
        $this->filters = $filters;
    }
    
    public function __invoke() : array {
        return array_merge(['REQ', $this->subscriptionId], map($this->filters, fn(Subscribe\Filter $filter) => $filter()));
    }
}
