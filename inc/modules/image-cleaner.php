<?php
/*
    Module: Image Cleaner - Fulcrum
    Goal: Allow for an admin to activate an image search for a selected uploaded image within their entire PUBLISHED product library. Match the image to the selected target(s) and then remove the extra duplicate image and point that post to use the "winning" compared image as the featured product image. Clean out that media library.
    -- Potential Extra: Settings in order to clean and process images to be more effecient and space saving.
    Author: Christopher "Duffs" Crevling
    Company: Duffion LLC


    */

use  D\FULCRUM\TRAITS\PRIME as D_PRIME;
use  D\FULCRUM\TRAITS\TEMPLATES as D_TEMPLATES;

class fulcrum_ic
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
            'page_title' => 'Fulcrum Module - Image Cleaner',
            'menu_title' => 'Image Cleaner',
            'capability' => 'manage_options',
            'menu_slug' => 'module-ic',
            'function' => [&$this, 'view_ic'],
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

    function view_ic()
    {
        echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Fulcrum Module - Image Cleaning Tool</h2>';
        echo '</div>';
    }
}


if (!function_exists('d__start_IC')) {
    function d__start_IC()
    {
        global $d_modules;

        return (!isset($d_modules['IC']) ? new fulcrum_ic() : $d_modules['IC']);
    }
}

if (!isset($d_modules['IC'])) $d_modules['IC'] = [];

$d_modules['IC'] = d__start_IC();
$d_modules['IC']->init();
