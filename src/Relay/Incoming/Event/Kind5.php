<?php

namespace nostriphant\Transpher\Relay\Incoming\Event;

use nostriphant\Transpher\Relay\Condition;

readonly class Kind5 {

    public function __construct(private \nostriphant\Transpher\Nostr\Event $event) {
        
    }

    public function __invoke(\nostriphant\Transpher\Relay\Store $store): void {
        $prototypes = [];
        $possible_references = [
            'e' => 'ids',
            'k' => 'kinds',
            'a' => fn(array $tags): array => array_map(fn(array $address) => [
                'kinds' => [$address[0]],
                'authors' => [$address[1]],
                'until' => $this->event->created_at,
                '#d' => [$address[2]]
                    ], array_map(fn(array $tag) => explode(':', $tag[1]), $tags))
        ];
        foreach ($possible_references as $possible_reference => $possible_filter_field) {
            $tags = array_filter($this->event->tags, fn(array $tag) => $tag[0] === $possible_reference);
            if (empty($tags)) {
                continue;
            }

            if (is_string($possible_filter_field)) {
                $prototypes[] = [
                    'authors' => [$this->event->pubkey],
                    $possible_filter_field => array_map(fn(array $tag) => $tag[1], $tags)
                ];
            } elseif (is_callable($possible_filter_field)) {
                $prototypes = array_merge($prototypes, $possible_filter_field($tags));
            }
        }

        if (empty($prototypes)) {
            return;
        } elseif (count(array_filter($prototypes, fn(array $prototype) => $prototype['authors'][0] !== $this->event->pubkey)) > 0) {
            return;
        }

        $removable_events = $store(Condition::makeFiltersFromPrototypes(...$prototypes));
        foreach ($removable_events as $removable_event_id => $removable_event) {
            unset($store[$removable_event_id]);
        }
    }
}
