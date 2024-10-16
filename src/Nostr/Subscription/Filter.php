<?php

namespace rikmeijer\Transpher\Nostr\Subscription;

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
        $this->conditions = $conditions;
    }
}
