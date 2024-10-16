<?php

namespace rikmeijer\Transpher\Nostr\Message\Subscribe;
use function \Functional\map;

/**
 * Description of Filter
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Filter implements Chain {
    
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
        $conditions = array_filter(get_defined_vars(), fn(string $key) => in_array($key, self::POSSIBLE_FILTERS), ARRAY_FILTER_USE_KEY);
        if (empty($tags) === false) {
            $conditions = array_merge($conditions, $tags);
        }
        $this->conditions = $conditions;
    }
    
    #[\Override]
    public function __invoke(): array {
        return $this->conditions;
    }
}
