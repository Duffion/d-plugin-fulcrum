<?php
/*
    Module: Product Category Scraper - Fulcrum
    Goal: Create a targeted list of Categories (product_category) that have special terms within them that will be compaired against PUBLISHED products and specific fields of those products in order to copy the matching term into its own category (or subcategory of that products primary category {opt in from admin setting}) or into a product tag. This newly created category / subcat / tag will allow for a frontend developer / content creator to split out the inventory organization on the frontend

    Author: Christopher "Duffs" Crevling
    Company: Duffion LLC


    */

use  D\FULCRUM\TRAITS\PRIME as D_PRIME;
use  D\FULCRUM\TRAITS\TEMPLATES as D_TEMPLATES;

class fulcrum_pcs
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
            'page_title' => 'Fulcrum Module - Product Category Scraper',
            'menu_title' => 'Product Category Scraper',
            'capability' => 'manage_options',
            'menu_slug' => 'module-pcs',
            'function' => [&$this, 'view_pcs'],
            'position' => 4
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

    function view_pcs()
    {
        echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Fulcrum Module - Product Category Scraper</h2>';
        echo '</div>';
    }
}


if (!function_exists('d__start_PCS')) {
    function d__start_PCS()
    {
        global $d_modules;

        return (!isset($d_modules['PCS']) ? new fulcrum_pcs() : $d_modules['PCS']);
    }
}

if (!isset($d_modules['PCS'])) $d_modules['PCS'] = [];

$d_modules['PCS'] = d__start_PCS();
$d_modules['PCS']->init();
