<?php

function vectors(string $name): object {
    return json_decode(file_get_contents(__DIR__ . '/vectors/' . $name . '.json'), false);
}
