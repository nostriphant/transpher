<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite\Condition;

readonly class Since {

    public function __construct(private int $expected_value) {
        
    }

    
    public function __invoke(): array {
        return [
            'where' => ["event.created_at >= ?"],
            'param' => [$this->expected_value]
        ];
    }
}
