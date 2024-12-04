<?php

namespace nostriphant\Transpher\Stores\SQLite;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\Transpher\Relay\Conditions;

class TransformSubscription {

    static function transformToSQL3StatementFactory(Subscription $subscription, string ...$fields): callable {
        $to = new Conditions(Condition::class);
        $filters = array_map(fn(array $filter_prototype) => Filter::fromPrototype(...$to($filter_prototype)), $subscription->filter_prototypes);
        $query_prototype = array_reduce($filters, fn(array $query_prototype, Filter $filter) => $filter($query_prototype), [
            'where' => [],
            'limit' => null
        ]);

        list($where, $parameters) = array_reduce($query_prototype['where'], function (array $return, array $condition) {
            $return[0][] = array_shift($condition);
            $return[1] = array_merge($return[1], $condition);
            return $return;
        }, [[], []]);
        $query = "SELECT " . implode(',', $fields) . " FROM event "
                . "LEFT JOIN tag ON tag.event_id = event.id "
                . "LEFT JOIN tag_value ON tag.id = tag_value.tag_id "
                . "WHERE (" . implode(') AND (', $where) . ") "
                . 'GROUP BY event.id '
                . ($query_prototype['limit'] !== null ? "LIMIT " . $query_prototype['limit'] : "");
        return function (\SQLite3 $database, \Psr\Log\LoggerInterface $log) use ($query, $parameters) {
            $statement = $database->prepare($query);
            if ($statement === false) {
                $log->error('Query failed: ' . $database->lastErrorMsg());
                return new Statement($database->prepare("SELECT * FROM event LIMIT 0"), []);
            }
            return new Statement($statement, $parameters);
        };
    }
}
