<?php

namespace D\FULCRUM\MODULES;

/*
    Module: Auto Draft Publisher - Fulcrum
    Goal: Allow for an admin to save a selection of categories within their shop to auto publish DRAFT posts (via primary Publishing methods WP uses) into PUBLISHED products

    Author: Christopher "Duffs" Crevling
    Company: Duffion LLC


    */

use  D\FULCRUM\TRAITS\PRIME as D_PRIME;
use  D\FULCRUM\TRAITS\TEMPLATES as D_TEMPLATES;

class fulcrum_adp
{

    use D_PRIME, D_TEMPLATES;

    var $menu_item = [];

    function __construct()
    {
    }

    function init()
    {
        $this->_define();
        $this->_actions($this->actions);
        $this->_filters($this->filters);
    }

    function _define()
    {
        $this->menu_item = [
            'parent_slug' => 'fulcrum',
            'page_title' => 'Fulcrum Module - Auto Draft Publisher',
            'menu_title' => 'Auto Draft Pub.',
            'capability' => 'manage_options',
            'menu_slug' => 'module-adp',
            'function' => [&$this, 'view_adp'],
            'position' => 2
        ];

        $this->actions = [];

        $this->filters = [
            'add_subpage' => [
                'hook' => 'd-add-subpages--primary',
                'function' => 'add_submenu',
                'args' => 1
            ]
        ];
    }

    function view_adp()
    {
        echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Fulcrum Module - Auto Draft Publisher</h2>';
        echo '</div>';
    }
}


if (!function_exists('d__init_settings')) {
    function d__start_ADP()
    {
        global $d_modules;

        return (!isset($d_modules['ADP']) ? new fulcrum_adp() : $d_modules['ADP']);
    }
}

if (!isset($d_modules['ADP'])) $d_modules['ADP'] = [];

$d_modules['ADP'] = d__start_ADP();
$d_modules['ADP']->init();
