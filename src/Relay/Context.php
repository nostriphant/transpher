<?php

namespace nostriphant\Transpher\Relay;


interface Context {

    function __invoke(Sender $client): Incoming;
}
