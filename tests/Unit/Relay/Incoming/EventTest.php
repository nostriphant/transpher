<?php
use rikmeijer\TranspherTests\Unit\Functions;

describe('event storing', function () {

    it('stores all regular events', function () {
        $events = new class([]) implements rikmeijer\Transpher\Relay\Store {

            use \rikmeijer\Transpher\Nostr\EventsStore;
        };
        $incoming = new \rikmeijer\Transpher\Relay\Incoming\Event(Functions::event(['kind' => 1, 'id' => 'my-event']));
        $event = $incoming();
        expect($events)->toHaveCount(0);
        expect($events)->not()->toHaveKey('my-event');
        foreach ($event($events) as $message) {

        }
        expect($events)->toHaveCount(1);
        expect(isset($events['my-event']))->toBeTrue();
    });
});

