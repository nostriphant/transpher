<?php

namespace Transpher;

/**
 * Description of Redis
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Redis implements \ArrayAccess, \Iterator {

    private \Redis $redis;

    public function __construct(private string $store) {
        $url = parse_url($store);
        list($database, ) = explode('/', ltrim($url['path'], '/'), 2);

        $this->redis = new \Redis();
        //Connecting to Redis
        $this->redis->connect($url['host'] ?? 'localhost', $url['port'] ?? 6379, context: [
            $url['user'] ?? 'redis', $url['pass'] ?? 'redis'
        ]);

        $this->redis->select((int) $database);
        $iterator = null;
    }

    public function offsetExists(mixed $offset): bool {
        return $this->exists($key);
    }

    public function offsetGet(mixed $offset): array {
        return json_decode($this->redis->rawCommand('JSON.get', $offset, '$'), true)[0];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        if (is_null($offset)) {
            $offset = $value[1]['id'];
        }
        $this->redis->rawCommand('JSON.set', $offset, '$', json_encode($note_request[1]));
    }

    public function offsetUnset(mixed $offset): void {
        $this->redis->unlink($offset);
    }

    private ?int $iterator;
    private array|bool $bufferedKeys;

    public function current(): array {
        return $this->offsetGet($this->key());
    }

    public function key(): string {
        return \reset($this->bufferedKeys);
    }

    public function next(): void {
        array_shift($this->bufferedKeys);
        if (count($this->bufferedKeys) === 0) {
            $this->bufferedKeys = $this->redis->scan($this->iterator);
        }
    }

    public function rewind(): void {
        $this->iterator = null;
        $this->bufferedKeys = $this->redis->scan($this->iterator);
    }

    public function valid(): bool {
        return is_array($this->bufferedKeys);
    }
}
