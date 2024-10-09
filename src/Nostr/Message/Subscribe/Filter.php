<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace rikmeijer\Transpher\Nostr\Message\Subscribe;

/**
 * Description of Filter
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class Filter implements Chain {
    
    const POSSIBLE_FILTERS = ["ids", "authors", "kinds", "tags", "since", "until", "limit"];
    
    private array $conditions;
    
    public function __construct(private Chain $previous, mixed ...$conditions) {
        $this->conditions = $conditions;
    }
    
    #[\Override]
    public function __invoke() : array {
        $filtered_conditions = array_filter($this->conditions, fn(string $key) => in_array($key, self::POSSIBLE_FILTERS), ARRAY_FILTER_USE_KEY);
        if (count($filtered_conditions) === 0) {
            return ($this->previous)();
        }
        if (array_key_exists('tags', $filtered_conditions)) {
            $tags = $filtered_conditions['tags'];
            unset($filtered_conditions['tags']);
            $filtered_conditions = array_merge($filtered_conditions, $tags);
        }
        return array_merge(($this->previous)(), [$filtered_conditions]);
    }
}
