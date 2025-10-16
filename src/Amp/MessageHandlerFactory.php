<?php

namespace nostriphant\Transpher\Amp;


interface MessageHandlerFactory {
    function __invoke(\nostriphant\NIP01\Transmission $transmission) : MessageHandler;
    
}
