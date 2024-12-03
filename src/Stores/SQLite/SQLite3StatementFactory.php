<?php

namespace nostriphant\Transpher\Stores\SQLite;

class SQLite3StatementFactory {

    public function __construct(private string $query, private array $parameters) {

    }

    public function __invoke(\SQLite3 $database, \Psr\Log\LoggerInterface $log): \SQLite3Stmt {
        $statement = $database->prepare($this->query);
        if ($statement === false) {
            $log->error('Query failed: ' . $database->lastErrorMsg());
            return $database->prepare("SELECT * FROM event LIMIT 0");
        }
        array_walk($this->parameters, function (mixed $parameter, int $position) use ($statement) {
            $statement->bindValue($position + 1, $parameter);
        });
        return $statement;
    }
}
