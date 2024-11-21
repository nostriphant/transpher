<?php

namespace nostriphant\Transpher\Nostr\Subscription;

use nostriphant\Transpher\Relay\Condition;
use function Functional\true;
use nostriphant\NIP01\Event;

readonly class Filter {

    public array $conditions;

    public function __construct(
            ?Condition $ids = null,
            ?Condition $authors = null,
            ?Condition $kinds = null,
            ?Condition $since = null,
            ?Condition $until = null,
            ?Condition $limit = null,
            ?array $tags = null
    ) {
        $conditions = array_filter(get_defined_vars());
        if (empty($tags) === false) {
            $conditions = \array_merge($conditions, $tags);
        }
        unset($conditions['tags']);
        $this->conditions = $conditions;
    }

    public function __invoke(Event $event): bool {
        return true(array_map(fn(callable $subscription_filter) => $subscription_filter($event), $this->conditions));
    }

    static function fromPrototype(Condition ...$conditions) {
        $tags = array_diff_key($conditions, [
            'ids' => null,
            'authors' => null,
            'kinds' => null,
            'since' => null,
            'until' => null,
            'limit' => null
        ]);
        return new self(...array_merge(array_diff_key($conditions, $tags), ['tags' => $tags]));
    }
}
