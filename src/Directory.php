<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Transpher;

/**
 * Description of Directory
 *
 * @author Rik Meijer <rmeijer@wemanity.com>
 */
class Directory implements \ArrayAccess, \Iterator {

    private array $events;

    public function __construct(private string $store) {
        foreach (glob($store . DIRECTORY_SEPARATOR . '*.php') as $event_file) {
            $event = include $event_file;
            $this->events[$event['id']] = $event;
        }   
    }
    
    private function file(array $event) {
        return $this->store . DIRECTORY_SEPARATOR . $event['id'] . '.php';
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool {
        return array_key_exists($offset, $this->events);
    }

    #[\Override]
    public function offsetGet(mixed $offset): array {
        return $this->events[$offset];
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $event): void {
        if (is_null($offset)) {
            $offset = $event['id'];
        }
        $this->events[$offset] = $event;
        file_put_contents($this->file($event), '<?php return ' . var_export($event, true) . ';');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void {
        unlink($this->file($this->events[$offset]));
        unset($this->events[$offset]);
    }
    
    #[\Override]
    public function current(): array {
        return \current($this->events);
    }

    #[\Override]
    public function key(): string {
        return \key($this->events);
    }

    #[\Override]
    public function next(): void {
        \next($this->events);
    }

    #[\Override]
    public function rewind(): void {
        \reset($this->events);
    }

    #[\Override]
    public function valid(): bool {
        return \current($this->events) !== false;
    }
}
