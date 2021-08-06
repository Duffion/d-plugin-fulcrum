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
        add_action('wp_ajax_nopriv_pcs_add_job', [$this, 'ajax__add_job']);
        add_action('wp_ajax_pcs_add_job', [$this, 'ajax__add_job']);

        add_action('wp_ajax_nopriv_pcs_remove_job', [$this, 'ajax__remove_job']);
        add_action('wp_ajax_pcs_remove_job', [$this, 'ajax__remove_job']);

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

        $this->actions = [
            // 'ajax__add_job' => [
            //     'hook' => 'ajax__add_job',
            //     'function' => 'wp_ajax_pcs_add_job'
            // ],
            // 'nopriv_ajax__add_job' => [
            //     'hook' => 'ajax__add_job',
            //     'function' => 'wp_ajax_nopriv_pcs_add_job'
            // ]
        ];

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
        $args = array(
            'taxonomy'   => "product_cat",
            'hide_empty' => false,
        );
        $product_categories = get_terms($args);


        // pull the terms that the user has already put into the system //
        $jobs = get_option('fulcrum__pcr_jobs');

        $this->partial('modules', 'product-category-scraper', ['cats' => $product_categories, 'jobs' => $jobs]);
    }

    function ajax__add_job()
    {
        $response = [];
        $p = $_POST;

        // wpp($p) . die;
        if (isset($p['target-text']) && isset($p['target-categories'])) {
            $search = sanitize_text_field($p['target-text']);
            $create_cat = isset($p['create-category-toggle']) ? (bool) $p['create-category-toggle'] : false;
            $categories = $p['target-categories'];
            $id = sanitize_title($search);

            $current = get_option('fulcrum__pcr_jobs');
            if (!$current)
                $current = [];

            $current[$id] = [
                'search' => $search,
                'create_cat' => $create_cat,
                'categories' => $categories,
                'logs' => [],
                'last_run' => 0,
                'created' => time()
            ];

            update_option('fulcrum__pcr_jobs', $current);

            $check = get_option('fulcrum__pcr_jobs');
            if ($check) {
                wp_send_json($check, 200);
            } else {
                wp_send_json(['message' => 'failed'], 403);
            }
        }
    }

    function ajax__remove_job()
    {
        $response = ['status' => 'failed'];
        $code = 403;
        $jobs = get_option('fulcrum__pcr_jobs');
        $p = $_POST;
        if (isset($p['id'])) {
            if (isset($jobs[$p['id']])) {
                unset($jobs[$p['id']]);
                $response['status'] = 'success';
                $code = 200;
                update_option('fulcrum__pcr_jobs', $jobs);
            }
        }
        wp_send_json($response, $code);
    }

    function run_jobs()
    {
        $jobs = get_option('fulcrum__pcr_jobs');
        $job = [];

        if ($jobs && count($jobs) > 0) {
            foreach ($jobs as $id => $job) {
            }
        }
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
