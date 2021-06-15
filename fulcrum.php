<?php

namespace D\FULCRUM;

/*
 * Plugin Name: Fulcrum - Helper Tool
 * Plugin URI: https://duffion.com
 * Description: This is the custom built tool that allows for modular helper tools for Fulcrum Synced sites
 * Version: 0.0.1
 * Author: Chris "Duffs" Crevling
 * Text Domain: fulcrum-pos
 * Author URI: https://duffion.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('D_FULCRUM')) :

    // Load in global vars //
    $d_plugin_dirs = [];

    class D_FULCRUM
    {

        var $version = '1.0.1';

        public $settings = [];

        public $modules = [];

        public $dirs = [
            'templates' => 'templates',
            'templates-modules' => 'templates/modules',
            'partials' => 'templates/partials',
            'modules' => 'modules',
            'inc' => 'inc',
            'traits' => 'inc/traits',
            'vendors' => 'inc/vendors',
            'api' => 'inc/api',
            'assets' => 'assets',
            'scripts' => 'assets/js',
            'styles' => 'assets/css'
        ];

        // [ 'filename without php' => 'name of dir from above config' ] //
        private $_loading = [
            'core' => 'inc',
            'enqueue' => 'inc'
        ];

        private $instance = [];

        /**
         * __construct - []
         *
         */
        function __construct()
        {
            $this->_define();
        }

        /**
         * _load - []
         * We need to load in all the required core files / traits
         */
        function _load()
        {
            global $d_instance, $d_loaded;
            // Lets create a global instance to make sure we only load items not already loaded //
            $d_loaded = [];
            $d_instance = (!isset($d_instance) ? [] : $d_instance);

            require_once $this->dirs['plugin'] . '/' . $this->dirs['inc'] . '/util.php';
            require_once $this->dirs['plugin'] . '/' . $this->dirs['traits'] . '/d-primary.php';

            // Lets now load in our other flies with the util loader //
            if ($this->_loading && count($this->_loading) > 0) {
                foreach ($this->_loading as $file => $dir_name) {
                    $file_loc = (isset($this->dirs[$dir_name]) ? $this->dirs['plugin'] . '/' . $this->dirs[$dir_name] . '/' . $file . '.php' : false);
                    if ($file_loc) d_req($file_loc);

                    $this->instance['loaded'] = $d_loaded;
                }
            }


            // wpp($this);
        }

        /**
         * _define - []
         *
         */
        function _define($r = false)
        {
            global $d_plugin_dirs;

            $this->dirs['plugin'] = ABSPATH . 'wp-content/plugins/fulcrum';

            $d_plugin_dirs = $this->dirs;
        }

        /**
         * _reset - []
         *
         */
        function _reset()
        {
        }

        /**
         * init - []
         *
         */
        function init()
        {
            // Load in any needed configs or passable globals here so loaded items can use properly //

            // Lets manually load in our starting files //
            $this->_load();
        }

        /**
         * loader - []
         *
         */
        function loader()
        {
        }
    }

    /**
     * Global Functionset - D_FULCRUM() - only run once []
     *
     */
    function D_FULCRUM()
    {
        global $d_fulcrum;

        if (!isset($d_fulcrum)) {
            $d_fulcrum = new d_fulcrum();
            $d_fulcrum->init();
        }

        return $d_fulcrum;
    }

    // Instantiate
    D_FULCRUM();

endif;
