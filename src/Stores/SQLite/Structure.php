<?php

namespace nostriphant\Transpher\Stores\SQLite;

readonly class Structure {

    const VERSION = '20241202';

    public function __construct(private \Psr\Log\LoggerInterface $log) {
        ;
    }

    public function __invoke(\SQLite3 $database): void {
        $database->exec("PRAGMA foreign_keys = ON");
        $this->log->debug('Enabled foreign keys in database');

        $database->exec("CREATE TABLE IF NOT EXISTS event ("
                . "id TEXT PRIMARY KEY ASC,"
                . "pubkey TEXT,"
                . "created_at INTEGER,"
                . "kind INTEGER,"
                . "content TEXT,"
                . "sig TEXT"
                . ")");
        $this->log->debug('Table event created (if it did not already exist)');
        if (self::retrieveVersion($database) < self::VERSION) {
            $database->exec('ALTER TABLE event ADD COLUMN tags_json TEXT');
        }

        $database->exec("CREATE TABLE IF NOT EXISTS tag ("
                . "id INTEGER PRIMARY KEY AUTOINCREMENT,"
                . "event_id TEXT REFERENCES event (id) ON DELETE CASCADE ON UPDATE CASCADE,"
                . "name TEXT"
                . ")");
        $this->log->debug('Table tag created (if it did not already exist)');

        $database->exec("CREATE TABLE IF NOT EXISTS tag_value ("
                . "id INTEGER PRIMARY KEY AUTOINCREMENT,"
                . "position INTEGER,"
                . "tag_id INTEGER REFERENCES tag (id) ON DELETE CASCADE ON UPDATE CASCADE,"
                . "value TEXT,"
                . "UNIQUE (tag_id, position) ON CONFLICT FAIL"
                . ")");
        $this->log->debug('Table tag_value created (if it did not already exist)');

        $database->exec("CREATE TRIGGER IF NOT EXISTS auto_increment_position_trigger "
                . "AFTER INSERT ON tag_value WHEN new.position IS NULL BEGIN"
                . "    UPDATE tag_value"
                . "    SET position = (SELECT IFNULL(MAX(position), 0) + 1 FROM tag_value WHERE tag_id = new.tag_id)"
                . "    WHERE id = new.id;"
                . "END;"
        );
        $this->log->debug('Trigger auto_increment_position_trigger created (if it did not already exist)');

        $database->exec("CREATE VIEW IF NOT EXISTS event_tag_json AS "
                . "SELECT "
                . "    tag.event_id, "
                . "    tag.name, "
                . "    json_insert(json_group_array(tag_value.value), '$[#]', tag.name) AS json"
                . " FROM tag "
                . " LEFT JOIN tag_value ON tag.id = tag_value.tag_id "
                . " GROUP BY tag.name"
                . " ORDER BY tag_value.position ASC"
        );
        $this->log->debug('View event_tag_json created (if it did not already exist)');

        $database->exec("UPDATE event SET tags_json = (SELECT GROUP_CONCAT(event_tag_json.json,', ') FROM event_tag_json WHERE event_tag_json.event_id = event.id) WHERE tags_json IS NULL");
        $this->log->debug('Updated missing tags_json values');

        $database->exec('PRAGMA user_version = "' . self::VERSION . '"');
    }

    static function retrieveVersion(\SQLite3 $database): string {
        return $database->querySingle('PRAGMA user_version');
    }
}
