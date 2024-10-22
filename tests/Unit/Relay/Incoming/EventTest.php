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
        expect(isset($events['my-event']))->toBeFalse();
        foreach ($event($events) as $message) {

        }
        expect($events)->toHaveCount(1);
        expect(isset($events['my-event']))->toBeTrue();
    });

    it('replaces replaceble events, keeping only the last one (based on pubkey & kind)', function () {
        $events = new class([]) implements rikmeijer\Transpher\Relay\Store {

            use \rikmeijer\Transpher\Nostr\EventsStore;
        };

        $events['my-original-event'] = Functions::event(['kind' => 0, 'pubkey' => 'my-pubkey', 'id' => 'my-original-event']);
        $replacing_event = Functions::event(['kind' => 0, 'pubkey' => 'my-pubkey', 'id' => 'my-event']);
        $incoming = new \rikmeijer\Transpher\Relay\Incoming\Event($replacing_event);
        $event = $incoming();
        expect($events)->toHaveCount(1);
        expect(isset($events['my-original-event']))->toBeTrue();
        expect(isset($events['my-event']))->toBeFalse();
        foreach ($event($events) as $message) {

        }
        expect($events)->toHaveCount(1);
        expect(isset($events['my-original-event']))->toBeFalse();
        expect(isset($events['my-event']))->toBeTrue();
    });

    it('stores no ephemeral events', function () {
        $events = new class([]) implements rikmeijer\Transpher\Relay\Store {

            use \rikmeijer\Transpher\Nostr\EventsStore;
        };
        $incoming = new \rikmeijer\Transpher\Relay\Incoming\Event(Functions::event(['kind' => 20000, 'id' => 'my-event']));
        $event = $incoming();
        expect($events)->toHaveCount(0);
        expect($events)->not()->toHaveKey('my-event');
        foreach ($event($events) as $message) {

        }
        expect($events)->toHaveCount(0);
    });
});

