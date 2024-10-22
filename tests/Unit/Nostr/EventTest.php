<?php
use rikmeijer\Transpher\Nostr\Event;
use rikmeijer\Transpher\Nostr\Event\KindClass;
use rikmeijer\TranspherTests\Unit\Functions;

describe('KindClass', function () {
    it('identifies regular Kind classes', function () {
        expect(Event::determineClass(Functions::event(['kind' => 1])))->toBe(KindClass::REGULAR);
        expect(Event::determineClass(Functions::event(['kind' => 2])))->toBe(KindClass::REGULAR);
        expect(Event::determineClass(Functions::event(['kind' => 4])))->toBe(KindClass::REGULAR);
        expect(Event::determineClass(Functions::event(['kind' => 44])))->toBe(KindClass::REGULAR);
        expect(Event::determineClass(Functions::event(['kind' => 1000])))->toBe(KindClass::REGULAR);
        expect(Event::determineClass(Functions::event(['kind' => 9999])))->toBe(KindClass::REGULAR);
    });
    it('identifies replaceable Kind classes', function () {
        expect(Event::determineClass(Functions::event(['kind' => 0])))->toBe(KindClass::REPLACEABLE);
        expect(Event::determineClass(Functions::event(['kind' => 3])))->toBe(KindClass::REPLACEABLE);
        expect(Event::determineClass(Functions::event(['kind' => 10000])))->toBe(KindClass::REPLACEABLE);
        expect(Event::determineClass(Functions::event(['kind' => 19999])))->toBe(KindClass::REPLACEABLE);
    });
    it('identifies ephemeral Kind classes', function () {
        expect(Event::determineClass(Functions::event(['kind' => 20000])))->toBe(KindClass::EPHEMERAL);
        expect(Event::determineClass(Functions::event(['kind' => 29999])))->toBe(KindClass::EPHEMERAL);
    });
    it('identifies addressable Kind classes', function () {
        expect(Event::determineClass(Functions::event(['kind' => 30000])))->toBe(KindClass::ADDRESSABLE);
        expect(Event::determineClass(Functions::event(['kind' => 39999])))->toBe(KindClass::ADDRESSABLE);
    });
    it('identifies undefined Kind classes', function () {
        expect(Event::determineClass(Functions::event(['kind' => -1])))->toBe(KindClass::UNDEFINED);
        expect(Event::determineClass(Functions::event(['kind' => 40000])))->toBe(KindClass::UNDEFINED);
    });
});

