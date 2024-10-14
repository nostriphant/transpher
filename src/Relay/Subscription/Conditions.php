<?php

namespace rikmeijer\Transpher\Relay\Subscription;
use function \Functional\map;

/**
 * Description of Filter
 *
 * @author hello@rikmeijer.nl
 */
readonly class Conditions {
    
    private array $available_conditions;
    
    public function __construct() {
        $available_conditons = [];
        foreach (glob(__DIR__ . '/Conditions/*.php') as $available_filter_file) {
            $available_conditons[basename($available_filter_file, '.php')] = require $available_filter_file;
        }
        $this->available_conditions = $available_conditons;
    }
    
    public function __invoke(array $filter_prototype) {
        return map(array_intersect_key($filter_prototype, $this->available_conditions), fn($condition, $filter_field) => $this->available_conditions[$filter_field]($condition));
    }
}
