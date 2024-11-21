<?php

namespace nostriphant\Transpher\Relay;

readonly class Conditions {

    public function __construct(private string $directory = __DIR__ . '/Condition') {
        
    }

    public function __invoke(array $filter_prototype): array {
        $conditions = [];
        foreach ($filter_prototype as $filter_field => $expected_value) {
            if (is_file($this->directory . '/' . $filter_field . '.php')) {
                $condition = require $this->directory . '/' . $filter_field . '.php';
            } else {
                $condition = (require $this->directory . '/tags.php')(ltrim($filter_field, '#'));
            }
            $conditions[$filter_field] = $condition($expected_value);
        }
        return $conditions;
    }
}
