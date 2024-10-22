<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace rikmeijer\Transpher\Nostr\Event;

/**
 * Description of KindClass
 *
 * @author rmeijer
 */
enum KindClass {
    case REGULAR;
    case REPLACEABLE;
    case EPHEMERAL;
    case ADDRESSABLE;
    case UNDEFINED;
}
