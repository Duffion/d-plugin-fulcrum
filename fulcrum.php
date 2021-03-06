<?php

/**
 *
 * @package Fulcrum
 * @version 1.0
 */

namespace D\FULCRUM;


/*
 * Plugin Name: Fulcrum - Helper Tool
 * Plugin URI: https://duffion.com
 * Description: This is the custom built tool that allows for modular helper tools for Fulcrum Synced sites
 * Version: 1.0
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
    $d_modules = [];

    class D_FULCRUM
    {

        var $version = '1.0';

        public $settings = [];

        public $modules = [];

        private $updater = [];

        public $dirs = [
            'partials' => 'templates/partials',
            'modules' => 'inc/modules',
            'inc' => 'inc',
            'traits' => 'inc/traits',
            'vendors' => 'inc/vendors',
            'assets' => 'assets',
            'scripts' => 'assets/js',
            'styles' => 'assets/css',
            'templates' => 'templates',
            'modules' => 'inc/modules',
            'templates-modules' => 'templates/modules',
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

        function _git_updater()
        {
            require $this->dirs['plugin'] . '/' . $this->dirs['vendors'] . '/plugin-update-checkers/plugin-update-checker.php';

            $config = [
                'git' => 'https://github.com/Duffion/d-plugin-fulcrum/',
                'target_branch' => 'production'
            ];

            $this->updater = \Puc_v4_Factory::buildUpdateChecker(
                $config['git'],
                __FILE__,
                'fulcrum'
            );

            //Set the branch that contains the stable release.
            $this->updater->setBranch($config['target_branch']);
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
            $this->_define();

            require_once $this->dirs['plugin'] . '/' . $this->dirs['inc'] . '/util.php';
            require_once $this->dirs['plugin'] . '/' . $this->dirs['traits'] . '/d-primary.php';
            require_once $this->dirs['plugin'] . '/' . $this->dirs['traits'] . '/d-templates.php';
            require_once $this->dirs['plugin'] . '/' . $this->dirs['inc'] . '/d-crons.php';
            require_once $this->dirs['plugin'] . '/' . $this->dirs['inc'] . '/vendors.php';

            $this->_git_updater();

            // Lets now load in our other flies with the util loader //
            if ($this->_loading && count($this->_loading) > 0) {
                foreach ($this->_loading as $file => $dir_name) {
                    $file_loc = (isset($this->dirs[$dir_name]) ? $this->dirs['plugin'] . '/' . $this->dirs[$dir_name] . '/' . $file . '.php' : false);
                    if ($file_loc) d_req($file_loc);

                    $this->instance['loaded'] = $d_loaded;
                }
            }
        }

        /**
         * _define - []
         *
         */
        function _define($r = false)
        {
            global $d_plugin_dirs;

            $this->dirs['plugin'] = rtrim(plugin_dir_path(__FILE__), '/');

            $d_plugin_dirs = $this->dirs;
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

            // Do anything extra after we have loaded in the core //
            $this->register_plugin_hooks();
        }

        function register_plugin_hooks()
        {
            // on activate //
            register_activation_hook(__FILE__, 'activate_plugin');

            // on deactivate //
            register_deactivation_hook(__FILE__, 'deactivate_plugin');
        }

        function deactivate_plugin()
        {
            // we need to remove all cron jobs this plugin has registered //
            global $d__crons;
            $d__crons->unschedule_hooks();

            // remove all plugin option data //
            $options = [
                'fulcrum_adp_categories',
                'fulcrum_adp_published',
                'fulcrum_ic_logs',
                'fulcrum_ic_reset',
                'fulcrum__pcs_jobs',
                'fulcrum_cron_actions'
            ];

            foreach ($options as $option) {
                delete_option($option);
            }
        }

        function activate_plugin()
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
