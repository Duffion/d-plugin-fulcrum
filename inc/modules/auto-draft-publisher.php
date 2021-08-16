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
use D\FULCRUM\CRONS\d_crons as CRON;

class fulcrum_adp
{

    use D_PRIME, D_TEMPLATES;

    var $menu_item = [], $validations = [];

    private $meta_boxes = [];

    function __construct()
    {
        // Add categories wp_option if it doesn't exist
        if (!get_option('fulcrum_adp_categories')) {
            add_option('fulcrum_adp_categories', []);
        }
        $this->cron = new CRON;
    }

    function init()
    {
        $this->_define();
        $this->_actions($this->actions, $this);
        $this->_filters($this->filters);

        $this->register_cron();
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

        $this->actions = [
            'admin_post_adp_category_response' => [
                'function' =>  'handle_category_form'
            ]
        ];

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
        add_action('adp__run_job', [$this, 'adp_cron']);
        $this->cron->schedule('adp__run_job', 'five_minutes');
    }

    function get_product_cats()
    {
        // Get all product categories //
        $args = array(
            'taxonomy'   => "product_cat",
            'hide_empty' => false,
        );
        return get_terms($args);
    }

    function view_adp()
    {
        // We need to include the partial template using our template trait and output it here //
        $this->partial('modules', 'auto-draft-publisher', ['cats' => $this->get_product_cats(), 'nonce' => $this->create_nonce()]);
    }

    function adp_cron()
    {
        // Pull wp_option for selected global categories
        $cats = get_option('fulcrum_adp_categories');

        // Create tax_query with all the selected IDs from wp_option
        $args = array(
            'post_type' => 'product',
            'post_status' => 'draft',
            'posts_per_page' => 35,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $cats
                )
            )
        );
        $query = new \WP_Query($args);

        // Publish each draft found in selected categories
        $published = get_option('fulcrum_adp_published');
        if (!$published) $published = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $published[$post->ID] = [
                    'id' => $post->ID,
                    'post_title' => $post->post_title,
                    'time' => time()
                ];

                wp_publish_post($post);
            }
            update_option('fulcrum_adp_published', $published);
        }
    }

    public function handle_category_form()
    {
        if (isset($_POST['adp_category_nonce']) && wp_verify_nonce($_POST['adp_category_nonce'], 'adp_category_nonce')) {
            $updated_cats = [];

            $product_cats = $this->get_product_cats();
            foreach ($product_cats as $cat) {
                $id = 'd--toggle-' . $cat->term_id;
                if (isset($_POST[$id]) && $_POST[$id] == 'on') {
                    $updated_cats[] = $cat->term_id;
                }
            }

            // Update the categories option in the database
            update_option('fulcrum_adp_categories', $updated_cats);

            // Redirect back to the module page with a success message
            wp_redirect(admin_url('admin.php?page=module-adp&response=success'));
        }
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
