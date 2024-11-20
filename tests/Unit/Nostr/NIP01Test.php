<?php

use nostriphant\Transpher\Nostr\Event;
use function \Pest\event;

describe('KindClass', function () {
    it('identifies regular Kind classes', function () {
        expect(Event::alternateClass(event(['kind' => 1])))->toHaveState(regular: '*');
        expect(Event::alternateClass(event(['kind' => 2])))->toHaveState(regular: '*');
        expect(Event::alternateClass(event(['kind' => 4])))->toHaveState(regular: '*');
        expect(Event::alternateClass(event(['kind' => 44])))->toHaveState(regular: '*');
        expect(Event::alternateClass(event(['kind' => 1000])))->toHaveState(regular: '*');
        expect(Event::alternateClass(event(['kind' => 9999])))->toHaveState(regular: '*');
    });
    it('identifies replaceable Kind classes', function () {
        expect(Event::alternateClass(event(['kind' => 0])))->toHaveState(replaceable: '*');
        expect(Event::alternateClass(event(['kind' => 3])))->toHaveState(replaceable: '*');
        expect(Event::alternateClass(event(['kind' => 10000])))->toHaveState(replaceable: '*');
        expect(Event::alternateClass(event(['kind' => 19999])))->toHaveState(replaceable: '*');
    });
    it('identifies ephemeral Kind classes', function () {
        expect(Event::alternateClass(event(['kind' => 20000])))->toHaveState(ephemeral: '*');
        expect(Event::alternateClass(event(['kind' => 29999])))->toHaveState(ephemeral: '*');
    });
    it('identifies addressable Kind classes', function () {
        expect(Event::alternateClass(event(['kind' => 30000])))->toHaveState(addressable: '*');
        expect(Event::alternateClass(event(['kind' => 39999])))->toHaveState(addressable: '*');
    });
    it('identifies undefined Kind classes', function () {
        expect(Event::alternateClass(event(['kind' => -1])))->toHaveState(undefined: '*');
        expect(Event::alternateClass(event(['kind' => 40000])))->toHaveState(undefined: '*');
    });
});

