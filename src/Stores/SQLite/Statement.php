<?php

namespace nostriphant\Transpher\Stores\SQLite;

use nostriphant\NIP01\Event;

readonly class Statement {

    public function __construct(private \SQLite3Stmt $statement, array $arguments) {
        array_walk($arguments, function (mixed $argument, int $position) {
            $this->statement->bindValue($position + 1, $argument);
        });
    }

    public function __invoke(): \Generator {
        $result = $this->statement->execute();
        if ($result === false) {
            $this->log->error('Query failed: ' . $this->statement->getSQL(true));
            yield from [];
        } else {
            while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
                $data['tags'] = json_decode('[' . $data['tags_json'] . ']') ?? [];
                array_walk($data['tags'], fn(array &$tag) => array_unshift($tag, array_pop($tag)));
                unset($data['tags_json']);
                yield new Event(...$data);
            }
        }
        $result->finalize();
        $this->statement->close();
    }

    public function __toString(): string {
        return $this->statement->getSQL(true);
    }
}
