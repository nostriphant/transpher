<?php

namespace nostriphant\Transpher\Stores;

readonly class ConditionFactory {

    public function __construct(private string $target) {
        
    }

    public function __invoke(string $filter_field, mixed $expected_value) {
        return $this->target::$filter_field($expected_value);
    }
}
