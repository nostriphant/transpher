<?php

namespace nostriphant\Transpher\Amp;

interface MessageHandler {
    function __invoke(string $json) : \Traversable;
}
