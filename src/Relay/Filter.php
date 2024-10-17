<?php
namespace rikmeijer\Transpher\Relay;

use function Functional\some,
             \Functional\map,
             \Functional\true,
             \Functional\partial_left;
use \rikmeijer\Transpher\Nostr\Event;
use rikmeijer\Transpher\Relay\Subscription\Conditions;

/**
 * Description of Filters
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Filter {
    
    private \Closure $possible_filters;

    public function __construct(array ...$filter_prototypes) {
        $this->possible_filters = partial_left('\Functional\map', Conditions::map($filter_prototypes));
    }
    
    public function __invoke(Event $event) : bool {
        return some(($this->possible_filters)(fn(array $possible_filter) => true(map($possible_filter, fn(callable $subscription_filter) => $subscription_filter($event)))));
    }

    static function make(array ...$filter_prototypes): self {
        return new self(...$filter_prototypes);
    }
}
