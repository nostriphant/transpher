<?php

namespace nostriphant\Transpher\Stores\Engine;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Stores\Results;
use nostriphant\Transpher\Stores\Engine;
use nostriphant\Transpher\Stores\Housekeeper;
use nostriphant\Transpher\Stores\Conditions;

readonly class SQLite implements Engine {

    use MemoryWrapper {
        __construct As MW_Construct;
        offsetSet As MW_offsetSet;
        offsetUnset As MW_offsetUnset;
    }

    public function __construct(public \SQLite3 $database) {
        $structure = new SQLite\Structure();
        $structure($database);

        $this->MW_Construct(iterator_to_array(\nostriphant\Transpher\Stores\Store::query($this, [])));
    }

    #[\Override]
    static function housekeeper(Engine $engine): Housekeeper {
        return new SQLite\Housekeeper($engine);
    }

    public function query(\nostriphant\Transpher\Stores\Conditions $conditionsFactory, ?int $limit, string ...$fields): SQLite\Statement {
        $conditionFactory = new \nostriphant\Transpher\Stores\ConditionFactory(SQLite\Condition\Test::class);

        $wheres = [];
        $parameters = [];
        foreach ($conditionsFactory($conditionFactory) as $query_conditions) {
            $where = array_reduce($query_conditions, fn(array $where, SQLite\Condition\Test $condition) => $condition($where), [
                'where' => [],
                'param' => []
            ]);

            if (empty($where['where']) === false) {
                $wheres[] = implode(' AND ', $where['where']);
                $parameters = array_merge($parameters, $where['param']);
            }
        }

        $query = "SELECT " . implode(',', $fields) . " FROM event "
                . "LEFT JOIN tag ON tag.event_id = event.id "
                . "LEFT JOIN tag_value ON tag.id = tag_value.tag_id "
                . (empty($wheres) ? '' : "WHERE (" . implode(') OR (', $wheres) . ") ")
                . 'GROUP BY event.id ';

        if (isset($limit)) {
            $query .= 'ORDER BY event.created_at DESC, event.id ASC '
                    . 'LIMIT ' . $limit;
        }

        return new SQLite\Statement($query, $parameters);
    }

    private function queryEvent(string $event_id): Results {
        return \nostriphant\Transpher\Stores\Store::query($this, [
                    'ids' => [$event_id],
                    'limit' => 1
        ]);
    }

    #[\Override]
    public function __invoke(Conditions $filter_conditions, ?int $limit): Results {
        return $this->query($filter_conditions, $limit, "event.id", "pubkey", "created_at", "kind", "content", "sig", "tags_json")($this->database);
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        return $this->offsetGet($offset) !== null;
    }

    #[\Override]
    public function offsetGet(mixed $offset): ?Event {
        $events = iterator_to_array($this->queryEvent($offset));
        return $events[0] ?? null;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $event): void {
        if (!$event instanceof Event) {
            return;
        }

        $query = $this->database->prepare("INSERT INTO event (id, pubkey, created_at, kind, content, sig) VALUES ("
                . ":id,"
                . ":pubkey,"
                . ":created_at,"
                . ":kind,"
                . ":content,"
                . ":sig"
                . ")");
        $tags = [];
        foreach (get_object_vars($event) as $property => $value) {
            if ($property === 'tags') {
                foreach ($value as $event_tag) {
                    $tag = [
                        'query' => $this->database->prepare("INSERT INTO tag (event_id, name) VALUES (:event_id, :name)"),
                        'values' => []
                    ];
                    $tag['query']->bindValue('event_id', $event->id);
                    $tag['query']->bindValue('name', array_shift($event_tag));

                    foreach ($event_tag as $position => $event_tag_value) {
                        $tag_value_query = $this->database->prepare("INSERT INTO tag_value (tag_id, position, value) VALUES (:tag_id, :position, :value)");
                        $tag_value_query->bindValue('position', $position + 1);
                        $tag_value_query->bindValue('value', $event_tag_value);
                        $tag['values'][] = $tag_value_query;
                    }

                    $tags[] = $tag;
                }
                
            } else {
                $query->bindValue($property, $value);
            }
        }

        if ($query->execute() !== false) {
            foreach ($tags as $tag) {
                if ($tag['query']->execute() !== false) {
                    $tag_id = $this->database->lastInsertRowID();
                    foreach ($tag['values'] as $value) {
                        $value->bindValue('tag_id', $tag_id);
                        $value->execute();
                    }
                }
            }
        }

        $update = $this->database->prepare("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE event.id = ?");
        $update->bindValue(1, $event->id);
        $update->execute();
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        $query = $this->database->prepare("DELETE FROM event WHERE id = :event_id");
        $query->bindValue('event_id', $offset);
        $query->execute();
    }
}
