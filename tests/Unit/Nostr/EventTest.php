<?php
use rikmeijer\Transpher\Nostr\Event;
use rikmeijer\Transpher\Nostr\Event\KindClass;

function KindEvent(int $kind) {
    return new Event('', '', 0, $kind, '', '', []);
}

describe('KindClass', function () {

    it('identifies regular Kind classes', function () {
        expect(Event::determineClass(KindEvent(1)))->toBe(KindClass::REGULAR);
        expect(Event::determineClass(KindEvent(2)))->toBe(KindClass::REGULAR);
        expect(Event::determineClass(KindEvent(4)))->toBe(KindClass::REGULAR);
        expect(Event::determineClass(KindEvent(44)))->toBe(KindClass::REGULAR);
        expect(Event::determineClass(KindEvent(1000)))->toBe(KindClass::REGULAR);
        expect(Event::determineClass(KindEvent(9999)))->toBe(KindClass::REGULAR);
    });
    it('identifies replaceable Kind classes', function () {
        expect(Event::determineClass(KindEvent(0)))->toBe(KindClass::REPLACEABLE);
        expect(Event::determineClass(KindEvent(3)))->toBe(KindClass::REPLACEABLE);
        expect(Event::determineClass(KindEvent(10000)))->toBe(KindClass::REPLACEABLE);
        expect(Event::determineClass(KindEvent(19999)))->toBe(KindClass::REPLACEABLE);
    });
    it('identifies ephemeral Kind classes', function () {
        expect(Event::determineClass(KindEvent(20000)))->toBe(KindClass::EPHEMERAL);
        expect(Event::determineClass(KindEvent(29999)))->toBe(KindClass::EPHEMERAL);
    });
    it('identifies addressable Kind classes', function () {
        expect(Event::determineClass(KindEvent(30000)))->toBe(KindClass::ADDRESSABLE);
        expect(Event::determineClass(KindEvent(39999)))->toBe(KindClass::ADDRESSABLE);
    });
    it('identifies undefined Kind classes', function () {
        expect(Event::determineClass(KindEvent(-1)))->toBe(KindClass::UNDEFINED);
        expect(Event::determineClass(KindEvent(40000)))->toBe(KindClass::UNDEFINED);
    });
});

