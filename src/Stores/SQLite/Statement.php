<?php

namespace nostriphant\Transpher\Stores\SQLite;

use nostriphant\NIP01\Event;

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
        } else {
            $arguments = $this->arguments;
            array_walk($arguments, fn(mixed $argument, int $position) => $statement->bindValue($position + 1, $argument));
            $result = $statement->execute();
            if ($result === false) {
                trigger_error('Query failed: ' . $statement->getSQL(true), E_USER_WARNING);
                return new Results();
            } elseif ($this->is_select) {
                return Results::fromSQLite3Result($result, function (array $data): Event {
                            $data['tags'] = json_decode('[' . $data['tags_json'] . ']') ?? [];
                            array_walk($data['tags'], fn(array &$tag) => array_unshift($tag, array_pop($tag)));
                            unset($data['tags_json']);
                            return new Event(...$data);
                        });
            } else {
                return new Results(affected_rows: $database->changes());
            }
            $result->finalize();
            $statement->close();
        }
    }

    static function nest(string $query_prefix, self $statement, string $query_postfix): self {
        return new self($query_prefix . $statement->query . $query_postfix, $statement->arguments);
    }
}
