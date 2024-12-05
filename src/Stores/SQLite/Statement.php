<?php

namespace nostriphant\Transpher\Stores\SQLite;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Stores\Results;

readonly class Statement {

    private bool $is_select;

    public function __construct(private string $query, private array $arguments) {
        $this->is_select = str_starts_with(strtoupper(ltrim($query)), 'SELECT');
    }

    public function __invoke(\SQLite3 $database): Results {
        $statement = $database->prepare($this->query);
        if ($statement === false) {
            trigger_error('Query failed: ' . $database->lastErrorMsg(), E_USER_WARNING);
            return new Results();
        }
        $arguments = $this->arguments;
        array_walk($arguments, fn(mixed $argument, int $position) => $statement->bindValue($position + 1, $argument));
        $result = $statement->execute();
        if ($result === false) {
            trigger_error('Query failed: ' . $statement->getSQL(true), E_USER_WARNING);
            return new Results();
        }
        return new Results($database->changes(), call_user_func(function () use ($result, $statement) {
                    while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
                        $data['tags'] = json_decode('[' . $data['tags_json'] . ']') ?? [];
                        array_walk($data['tags'], fn(array &$tag) => array_unshift($tag, array_pop($tag)));
                        unset($data['tags_json']);
                        yield new Event(...$data);
                    }
                    $result->finalize();
                    $statement->close();
                }));
    }

    static function nest(string $query_prefix, self $statement, string $query_postfix): self {
        return new self($query_prefix . $statement->query . $query_postfix, $statement->arguments);
    }
}
