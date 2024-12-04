<?php

namespace nostriphant\Transpher\Stores\SQLite;

use nostriphant\NIP01\Event;

readonly class Statement {

    private bool $is_select;

    public function __construct(private string $query, private array $arguments) {
        $this->is_select = str_starts_with(strtoupper(ltrim($query)), 'SELECT');
    }

    public function __invoke(\SQLite3 $database, \Psr\Log\LoggerInterface $log): \Generator|int {
        $statement = $database->prepare($this->query);
        if ($statement === false) {
            $log->error('Query failed: ' . $database->lastErrorMsg());
            if ($this->is_select) {
                yield from [];
            } else {
                return 0;
            }
        } else {
            $arguments = $this->arguments;
            array_walk($arguments, fn(mixed $argument, int $position) => $statement->bindValue($position + 1, $argument));
            $result = $statement->execute();
            if ($result === false) {
                $this->log->error('Query failed: ' . $statement->getSQL(true));
                if ($this->is_select) {
                    yield from [];
                } else {
                    return 0;
                }
            } elseif ($this->is_select) {
                while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
                    $data['tags'] = json_decode('[' . $data['tags_json'] . ']') ?? [];
                    array_walk($data['tags'], fn(array &$tag) => array_unshift($tag, array_pop($tag)));
                    unset($data['tags_json']);
                    yield new Event(...$data);
                }
            } else {
                return $database->changes();
            }
            $result->finalize();
            $statement->close();

        }
    }

    static function nest(string $query_prefix, self $statement, string $query_postfix): self {
        return new self($query_prefix . $statement->query . $query_postfix, $statement->arguments);
    }
}
