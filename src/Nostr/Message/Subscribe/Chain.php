<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

namespace Transpher\Nostr\Message\Subscribe;

/**
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
interface Chain {
    public function __invoke() : array;
}
