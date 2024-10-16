<?php

namespace rikmeijer\Transpher\Nostr\Message\Subscribe;

/**
 * Description of Filter
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Filter {

    const POSSIBLE_FILTERS = ["ids", "authors", "kinds", "since", "until", "limit"];

    private array $conditions;

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
            $conditions = array_merge($conditions, $tags);
        }
        $this->conditions = $conditions;
    }
    
    public function __invoke(): array {
        return $this->conditions;
    }
}
