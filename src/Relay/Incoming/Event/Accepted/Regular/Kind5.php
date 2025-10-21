<?php

namespace nostriphant\Transpher\Relay\Incoming\Event\Accepted\Regular;

use nostriphant\Functional\Alternate;
use nostriphant\NIP01\Event;
use nostriphant\Transpher\Relay\Files;

readonly class Kind5 implements Kind {

    #[\Override]
    public function __construct(private \nostriphant\Stores\Store $store, private Files $files) {
        
    }

    #[\Override]
    static function validate(Event $event): Alternate {
        return Alternate::accepted($event);
    }

    #[\Override]
    public function __invoke(Event $event): void {
        $prototypes = [];
        $possible_references = [
            'e' => 'ids',
            'k' => 'kinds',
            'a' => fn(array $tags): array => array_map(fn(array $address) => [
                'kinds' => [$address[0]],
                'authors' => [$address[1]],
                'until' => $event->created_at,
                '#d' => [$address[2]]
                    ], array_map(fn(array $tag) => explode(':', $tag[1]), $tags))
        ];
        foreach ($possible_references as $possible_reference => $possible_filter_field) {
            $tags = array_filter($event->tags, fn(array $tag) => $tag[0] === $possible_reference);
            if (empty($tags)) {
                continue;
            }

            if (is_string($possible_filter_field)) {
                $prototypes[] = [
                    'authors' => [$event->pubkey],
                    $possible_filter_field => array_map(fn(array $tag) => $tag[1], $tags)
                ];
            } elseif (is_callable($possible_filter_field)) {
                $prototypes = array_merge($prototypes, $possible_filter_field($tags));
            }
        }

        if (empty($prototypes)) {
            return;
        } elseif (count(array_filter($prototypes, fn(array $prototype) => $prototype['authors'][0] !== $event->pubkey)) > 0) {
            return;
        }

        foreach (($this->store)(...$prototypes) as $removable_event) {
            unset($this->store[$removable_event->id]);
        }
    }
}
