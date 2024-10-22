<?php
use rikmeijer\TranspherTests\Unit\Functions;

describe('event storing', function () {

    it('stores all regular events', function () {
        $events = new class([]) implements rikmeijer\Transpher\Relay\Store {

            use \rikmeijer\Transpher\Nostr\EventsStore;
        };
        $incoming = new \rikmeijer\Transpher\Relay\Incoming\Event(Functions::event(['kind' => 1, 'id' => 'my-event']));
        expect($events)->toHaveCount(0);
        expect(isset($events['my-event']))->toBeFalse();
        foreach ($incoming(Functions::context(['events' => $events])) as $message) {
            
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
        expect($events)->toHaveCount(1);
        expect(isset($events['my-original-event']))->toBeTrue();
        expect(isset($events['my-event']))->toBeFalse();
        foreach ($incoming(Functions::context(['events' => $events])) as $message) {
            
        }
        expect($events)->toHaveCount(1);
        expect(isset($events['my-original-event']))->toBeFalse();
        expect(isset($events['my-event']))->toBeTrue();
    });

    it('replaces replaceble events, keeping the first one in case of same timestamp (based on pubkey & kind)', function () {
        $events = new class([]) implements rikmeijer\Transpher\Relay\Store {

            use \rikmeijer\Transpher\Nostr\EventsStore;
        };

        $events['a'] = Functions::event(['kind' => 0, 'pubkey' => 'my-pubkey', 'id' => 'a']);
        $replacing_event = Functions::event(['kind' => 0, 'pubkey' => 'my-pubkey', 'id' => 'b']);
        $incoming = new \rikmeijer\Transpher\Relay\Incoming\Event($replacing_event);
        expect($events)->toHaveCount(1);
        expect(isset($events['a']))->toBeTrue();
        expect(isset($events['b']))->toBeFalse();
        foreach ($incoming(Functions::context(['events' => $events])) as $message) {
            
        }
        expect($events)->toHaveCount(1);
        expect(isset($events['a']))->toBeTrue();
        expect(isset($events['b']))->toBeFalse();
    });

    it('stores no ephemeral events', function () {
        $events = new class([]) implements rikmeijer\Transpher\Relay\Store {

            use \rikmeijer\Transpher\Nostr\EventsStore;
        };
        $incoming = new \rikmeijer\Transpher\Relay\Incoming\Event(Functions::event(['kind' => 20000, 'id' => 'my-event']));
        expect($events)->toHaveCount(0);
        expect($events)->not()->toHaveKey('my-event');
        foreach ($incoming(Functions::context(['events' => $events])) as $message) {
            
        }
        expect($events)->toHaveCount(0);
    });

    it('replaces addressable events, keeping only the last one (based on pubkey, kind and d)', function () {
        $events = new class([]) implements rikmeijer\Transpher\Relay\Store {

            use \rikmeijer\Transpher\Nostr\EventsStore;
        };

        $events['my-original-event'] = Functions::event(['kind' => 30000, 'pubkey' => 'my-pubkey', 'tags' => [['d', 'my-d-tag-value']], 'id' => 'my-original-event']);
        $replacing_event = Functions::event(['kind' => 30000, 'pubkey' => 'my-pubkey', 'tags' => [['d', 'my-d-tag-value']], 'id' => 'my-event']);
        $incoming = new \rikmeijer\Transpher\Relay\Incoming\Event($replacing_event);
        expect($events)->toHaveCount(1);
        expect(isset($events['my-original-event']))->toBeTrue();
        expect(isset($events['my-event']))->toBeFalse();
        foreach ($incoming(Functions::context(['events' => $events])) as $message) {
            
        }
        expect($events)->toHaveCount(1);
        expect(isset($events['my-original-event']))->toBeFalse();
        expect(isset($events['my-event']))->toBeTrue();
    });

    it('yields a notice for undefined event kinds', function () {
        $events = Mockery::mock(rikmeijer\Transpher\Relay\Store::class);
        $incoming = new \rikmeijer\Transpher\Relay\Incoming\Event(Functions::event(['kind' => -1, 'id' => 'undefined-event-kind']));
        foreach ($incoming(Functions::context(['events' => $events])) as $message) {
            expect($message()[0])->toBe('NOTICE');
            expect($message()[1])->toBe('Undefined event kind -1');
        }
    });
});

