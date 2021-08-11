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
use D\FULCRUM\CRONS\d_crons as CRON;

class fulcrum_ic
{

    use D_PRIME, D_TEMPLATES;

    var $menu_item = [];

    function __construct()
    {
        $this->cron = new CRON;
    }

    function init()
    {
        $this->_define();
        $this->_actions($this->actions);
        $this->_filters($this->filters);

        $this->register_cron();
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

    function register_cron()
    {
        add_action('ic__run_cleaner', [$this, 'run_cleaner']);
        $this->cron->schedule('ic__run_cleaner', 'three_minutes');
    }

    function view_ic()
    {
        // $this->run_cleaner();
        $logs = get_option('fulcrum_ic_logs');
        // var_dump($logs);
        $this->partial('modules', 'image-cleaner', ['logs' => $logs]);
    }

    function check_name($name)
    {
        $x = explode('-', $name);
        if (count($x) > 1) {
            $check = is_numeric($x[count($x) - 1]);

            if ($check) {
                return ['name' => $x[0], 'number' => $x[count($x) - 1]];
            }
        }
        return false;
    }

    function find_origional($s)
    {
        $query = [
            'post_type' => 'attachment',
            'post_status'    => 'inherit',
            's' => $s,
            'orderby' => 'post_name',
            'order' => 'ASC'
        ];
        $result = new WP_Query($query);

        if ($result->found_posts > 0) {
            $origional = $result->post;
            if (!$this->check_name($origional->post_name)) {
                return $origional;
            }
        }

        return false;
    }

    function run_cleaner()
    {
        // First lets get all the products that have a thumbnail id as well as hasn't been altered by teh system yet //
        $query = [
            'post_type' => 'product',
            'posts_per_page' => 35,
            'orderby' => 'post_title',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'ic-altered',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => 'ic-origional',
                    'compare' => 'NOT EXISTS'
                ]
            ],
        ];

        $products = new WP_Query($query);
        $logs = get_option('fulcrum_ic_logs');
        if (!$logs) $logs = [];

        $logs['last_run'] = time();
        // wpp($products);
        if ($products->found_posts > 0) {
            $return = [];
            foreach ($products->posts as $product) {
                // for each product we walk through we need to check its attachment and force it to use the origional photo and then remove the duplicate photo / attachment. This can be done by looking for a - in the name and a number following that //
                if (!isset($logs[$product->ID]))
                    $logs[$product->ID] = [];

                $featured = get_post(get_post_thumbnail_id($product->ID));
                $namer = $this->check_name($featured->post_name);
                if ($namer) {
                    // we have a duplicate imate lets find the origional
                    $origional = $this->find_origional($namer['name']);

                    // now we need to delete the old post thumbnail (duplicate) //
                    $logs[$product->ID]['deleted'] = $return['deleted_attachment'] = $deleted = wp_delete_attachment($featured->ID, true);
                    // now we should update / set the new post thumbnail to use this origional image //
                    $logs[$product->ID]['set_new_thumb'] = $return['set_new_thumb'] = $setting_new = set_post_thumbnail($product->ID, $origional->ID);

                    $altered = [];
                    if ($deleted) {
                        $altered['deleted_old'] = ['id' => $featured->ID, 'time' => time()];
                    }

                    if ($setting_new) {
                        $altered['setting_new'] = ['id' => $origional->ID, 'time' => time()];
                    }

                    update_post_meta($product->ID, 'ic-logs', $altered);
                    update_post_meta($product->ID, 'ic-altered', true);
                } else {
                    // is origional
                    update_post_meta($product->ID, 'ic-origional', true);
                }
            }
        }
        update_option('fulcrum_ic_logs', $logs);
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
