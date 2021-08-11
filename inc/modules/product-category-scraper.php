<?php
/*
    Module: Product Category Scraper - Fulcrum
    Goal: Create a targeted list of Categories (product_category) that have special terms within them that will be compaired against PUBLISHED products and specific fields of those products in order to copy the matching term into its own category (or subcategory of that products primary category {opt in from admin setting}) or into a product tag. This newly created category / subcat / tag will allow for a frontend developer / content creator to split out the inventory organization on the frontend

    Author: Christopher "Duffs" Crevling
    Company: Duffion LLC


    */

use  D\FULCRUM\TRAITS\PRIME as D_PRIME;
use  D\FULCRUM\TRAITS\TEMPLATES as D_TEMPLATES;
use D\FULCRUM\CRONS\d_crons as CRON;

class fulcrum_pcs
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
        add_action('wp_ajax_nopriv_pcs_add_job', [$this, 'ajax__add_job']);
        add_action('wp_ajax_pcs_add_job', [$this, 'ajax__add_job']);

        add_action('wp_ajax_nopriv_pcs_remove_job', [$this, 'ajax__remove_job']);
        add_action('wp_ajax_pcs_remove_job', [$this, 'ajax__remove_job']);

        add_action('wp_ajax_nopriv_pcs_run_jobs', [$this, 'ajax__run_jobs']);
        add_action('wp_ajax_pcs_run_jobs', [$this, 'ajax__run_jobs']);


        $this->_actions($this->actions);
        $this->_filters($this->filters);

        // we need to now register our cronjobs
        $this->register_cron();
    }

    function register_cron()
    {
        add_action('pcs__run_jobs_hook', [$this, 'run_jobs']);
        // wpp($this->cron) . die;
        // $this->cron->print_tasks();
        $this->cron->schedule('pcs__run_jobs_hook', 'three_minutes');
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
        $jobs = get_option('fulcrum__pcs_jobs');

        $this->partial('modules', 'product-category-scraper', ['cats' => $product_categories, 'jobs' => $jobs]);
    }

    function ajax__add_job()
    {
        $response = [];
        $p = $_POST;

        if (isset($p['target-text']) && isset($p['target-categories']) && is_admin()) {
            $search = sanitize_text_field($p['target-text']);
            $create_cat = isset($p['create-category-toggle']) ? (bool) $p['create-category-toggle'] : false;
            $categories = $p['target-categories'];
            $id = sanitize_title($search);

            $current = get_option('fulcrum__pcs_jobs');
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

            update_option('fulcrum__pcs_jobs', $current);

            $check = get_option('fulcrum__pcs_jobs');
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
        $jobs = get_option('fulcrum__pcs_jobs');
        $p = $_POST;
        if (isset($p['id']) && is_admin()) {
            if (isset($jobs[$p['id']])) {
                unset($jobs[$p['id']]);
                $response['status'] = 'success';
                $code = 200;
                update_option('fulcrum__pcs_jobs', $jobs);
            }
        }
        wp_send_json($response, $code);
    }

    function ajax__run_jobs()
    {
        $response = ['status' => 'failed'];
        $code = 403;
        $p = $_POST;
        if (isset($p['time']) && $p['time'] > 0) {
            // lets run our job action //
            $run = $this->run_jobs();
            if ($run['success']) {
                $response['status'] = 'success';
                $response['results'] = $run;
                $code = 200;
            }
        }

        wp_send_json($response, $code);
    }

    function compare_by_last_run($a, $b)
    {
        return $a['last_run'] - $b['last_run'];
    }

    function update_job($job, $results)
    {
        $jobs = get_option('fulcrum__pcs_jobs');
        foreach ($jobs as $k => $j) {
            if ($k === sanitize_title($job['search']) && $j['search'] === $job['search']) {
                // its our target. lets update the last run //
                $jobs[$k]['last_run'] = time();

                $jobs[$k]['logs'][] = [
                    'status' => 'success',
                    'processed' => ($results) ? count($results) : 0,
                    'time_run' => time()
                ];
            }
        }
        $update = update_option('fulcrum__pcs_jobs', $jobs);

        return ($update);
    }

    function run_jobs()
    {
        $jobs = get_option('fulcrum__pcs_jobs');
        $job = [];
        $time = time();

        // wpp($jobs) . die;
        if ($jobs && count($jobs) > 0) {
            usort($jobs, [$this, 'compare_by_last_run']);
            // get th etop job of this sorted joblist
            $job = (isset($jobs[0]) && count($jobs) > 0) ? $jobs[0] : false;

            if ($job) {
                // we need to run the rules of this job properly //
                $processed = $this->process_job($job);
                // we need to add the log to the job history as well update the options //
                $updated = $this->update_job($job, $processed);

                if ($processed) {
                    return ['status' => 'Successfully ran the job', 'success' => true, 'processed' => $processed, 'updated' => $updated, 'job' => $job];
                } else {
                    return ['status' => 'unchanged', 'success' => true, 'updated' => $updated, 'job' => $job];
                }
            } else {
                return ['status' => 'failed to run job', 'success' => false];
            }
        }
    }

    function process_job($job)
    {
        $return = false;

        $query = [
            'post_type' => 'product',
            'post_per_page' => 35,
        ];
        $meta_query = $tax_query = [
            'relation' => 'OR',
        ];

        if ($job && isset($job['search']) && $job['search'] !== '') {
            $categories = $job['categories'];
            $taxonomy = 'product_cat';
            $s = sanitize_title($job['search']);

            $meta_key = 'pcs-sync__' . $s;
            $query['s'] = strtolower($job['search']);

            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $categories,
            ];

            $meta_query[] = [
                'key' => $meta_key,
                'compare' => 'NOT EXISTS'
            ];

            $query['meta_query'] = $meta_query;
            $query['tax_query'] = $tax_query;

            $results = new WP_Query($query);

            $return = [];
            if ($results->found_posts > 0) {
                // we have a series of posts we now need to update the posts to add the new category / terms //
                foreach ($results->posts as $product) {
                    $cat_id = $cat = $exists = false;
                    $term_id = term_exists($job['search'], 'product_tag');
                    $has_term = has_term($s, 'product_tag', $product->ID);
                    // Add the search term to the tags by default //
                    if (!$term_id || !$has_term) {
                        $return[$product->ID]['product_tag'] = wp_set_post_terms($product->ID, $job['search'], 'product_tag', true);
                    }

                    // if we need to create a category we can do so here //
                    if (isset($job['create_cat']) && $job['create_cat']) {
                        $exists = term_exists($job['search'], 'product_cat');

                        if (!$exists) {
                            // lets create the category //
                            $return[$product->ID]['inserted_cat'] = $cat = wp_insert_term($job['search'], 'product_cat', []);

                            if (isset($cat[0]) && $cat[0] > 0) {
                                $cat_id = $cat[0];
                            } else {
                                $cat_id = $cat['term_id'];
                            }
                        } else {
                            $cat_id = $exists['term_id'];
                        }

                        $has_term = has_term($job['search'], 'product_cat', $product->ID);

                        if (!$has_term && $cat_id) {
                            $return[$product->ID]['product_cat'] = wp_set_object_terms($product->ID, [(int) $cat_id], 'product_cat', true);
                        }
                    }

                    $return[$product->ID]['post_id'] = $product->ID;
                    $return[$product->ID]['name'] = $product->post_name;
                    // set the post meta to be ignored for this set //
                    $return[$product->ID]['meta_update'] = update_post_meta($product->ID, $meta_key, [
                        'time' => time(),
                        'results' => $return,
                        'success' => true
                    ]);
                }
            }
        }

        return $return;
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
