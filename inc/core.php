<?php

/**
 * Our primary CORE plugin handler
 */

use  D\FULCRUM\TRAITS\PRIME as D_PRIME;
use  D\FULCRUM\TRAITS\TEMPLATES as D_TEMPLATES;

class d_core
{
    use D_PRIME, D_TEMPLATES;

    // [ 'directory-namespace' => 'directory folder' ]
    private $auto_dirs = [
        'modules', 'api'
    ];

    function __construct()
    {
        $this->_define();
        // Register our Actions and Filters //
        $this->_actions($this->actions);
        $this->_filters($this->filters);
    }

    function _define()
    {
        $this->actions = [
            'd-register-menu' => [
                'hook' => 'admin_menu',
                'function' => 'add_admin_menu'
            ]
        ];

        $this->filters = [];
    }

    // Action targets //
    function setup()
    {
        // Lets setup our autoloaders and build out our core plugin needs //
        $this->autoloader($this->auto_dirs);
    }

    function add_admin_menu()
    {
        // Register our Dashboard area //
        $menu = [
            'primary' => [
                'page_title' => __('Fulcrum Helper Modules - Dashboard', 'd-text'),
                'menu_title' => 'Fulcrum',
                'capability' => 'manage_options',
                'menu_slug' => 'fulcrum',
                'function' => 'view_dashboard',
                'icon_url' => '',
                'position' => 40,
                'subpages' => [
                    'settings' => [
                        'page_title' => __('Plugin Settings', 'd-text'),
                        'menu_title' => 'Settings',
                        'capability' => 'manage_options',
                        'menu_slug' => 'settings',
                        'function' => 'view_settings',
                        'position' => 9
                    ]
                ]
            ],
        ];

        // $subpages = ;
        // Lets add in a filter to allow us to append more sub pages via our modules //
        $this->register_settings($menu);
    }

    function view_dashboard()
    {
        echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Fulcrum Helper Dashboard</h2>';
        echo '<div class="notification"><b>Coming Soon!</b></div>';
        echo '</div>';
    }

    function view_settings()
    {
        // We need to load in the dashboard template //
        echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Fulcrum Plugin Settings</h2>';
        echo '<div class="notification"><b>Coming Soon!</b></div>';
        echo '</div>';
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
$d__core->setup();
