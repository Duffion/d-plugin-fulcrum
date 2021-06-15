<?php

/**
 * Our primary CORE plugin handler
 */

use  D\FULCRUM\TRAITS\PRIME as D_PRIME;

class d_core
{
    use D_PRIME;

    var $actions = [];

    var $filters = [];

    private $auto_dirs = [];

    function __construct()
    {
        // Register our Actions and Filters //
        $this->_actions($this->actions);
        $this->_filters($this->filters);
    }

    // Action targets //
    function setup()
    {
        // Lets setup our autoloaders and build out our core plugin needs //

    }
}

if (!function_exists('d__init_core')) {
    function d__init_core()
    {
        global $d__core;

        return (!isset($d__core) ? new d_core() : $d__core);
    }
}

$d__core = d__init_core();
