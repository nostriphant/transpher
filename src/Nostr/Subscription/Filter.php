<?php

namespace rikmeijer\Transpher\Nostr\Subscription;
use function \Functional\map;
use rikmeijer\Transpher\Relay\Subscription\Condition;

readonly class Filter {

    public array $conditions;

    public function __construct(
            ?array $ids = null,
            ?array $authors = null,
            ?array $kinds = null,
            ?int $since = null,
            ?int $until = null,
            ?int $limit = null,
            ?array $tags = null
    ) {
        $conditions = array_filter(get_defined_vars());
        if (empty($tags) === false) {
            $conditions = \array_merge($conditions, $tags);
        }
        unset($conditions['tags']);
        $this->conditions = $conditions;
    }

    public function __invoke(callable $callback): array {
        return map($this->conditions, $callback);
    }

    static function fromPrototype(array $filter_prototype): array {
        $tags = array_diff_key($filter_prototype, [
            'ids' => null,
            'authors' => null,
            'kinds' => null,
            'since' => null,
            'until' => null,
            'limit' => null
        ]);
        $filter = (new self(...array_merge(array_diff_key($filter_prototype, $tags), ['tags' => $tags])));
        return $filter(Condition::map());
    }
}
