<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\Transpher\Nostr\Subscription;
use nostriphant\NIP01\Event;

readonly class SQLite implements \nostriphant\Transpher\Relay\Store {

    public function __construct(private \SQLite3 $database, private Subscription $whitelist) {
        $structure = new SQLite\Structure();
        $structure($database);
        $housekeeper = new SQLite\Housekeeper();
        $housekeeper($database, $whitelist);
    }

    private function queryEvents(Subscription $subscription): Results {
        $statement = SQLite\TransformSubscription::transformToSQL3StatementFactory($subscription, "event.id", "pubkey", "created_at", "kind", "content", "sig", "tags_json");
        return $statement($this->database);
    }

    private function queryEvent(string $event_id): Results {
        return $this->queryEvents(Subscription::make([
                            'ids' => [$event_id],
                            'limit' => 1
        ]));
    }

    #[\Override]
    public function __invoke(Subscription $subscription): Results {
        return $this->queryEvents($subscription);
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        return $this->offsetGet($offset) !== null;
    }

    #[\Override]
    public function offsetGet(mixed $offset): ?Event {
        $event = null;
        $this->queryEvent($offset)(function (Event $found_event) use (&$event) {
            $event = $found_event;
        });
        return $event;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void {
        if (!$value instanceof Event) {
            return;
        } elseif (call_user_func($this->whitelist, $value) === false) {
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
        $event = get_object_vars($value);
        $tags = [];
        foreach ($event as $property => $value) {
            if ($property === 'tags') {
                foreach ($value as $event_tag) {
                    $tag = [
                        'query' => $this->database->prepare("INSERT INTO tag (event_id, name) VALUES (:event_id, :name)"),
                        'values' => []
                    ];
                    $tag['query']->bindValue('event_id', $event['id']);
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
        $update->bindValue(1, $event['id']);
        $update->execute();
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        $query = $this->database->prepare("DELETE FROM event WHERE id = :event_id");
        $query->bindValue('event_id', $offset);
        $query->execute();
    }

    #[\Override]
    public function count(): int {
        return $this->database->querySingle("SELECT COUNT(id) FROM event");
    }
}
