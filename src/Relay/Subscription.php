<?php
namespace rikmeijer\Transpher\Relay;

use function Functional\some, \Functional\map, \Functional\true;
use \rikmeijer\Transpher\Nostr\Event;
use rikmeijer\Transpher\Relay\Subscription\Conditions;

/**
 * Description of Filters
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Subscription {
    
    private array $possible_filters;
    
    public function __construct(array ...$filter_prototypes) {
        $this->possible_filters = Conditions::map($filter_prototypes);
    }
    
    public function __invoke(Event $event) : bool {
        return some(map($this->possible_filters, fn(array $possible_filter) => true(map($possible_filter, fn(callable $subscription_filter) => $subscription_filter($event)))));
    }
}
