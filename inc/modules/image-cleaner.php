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

        $this->actions = [
            // 'run_cleaner' => [
            //     'hook' => 'wp_loaded',
            //     'function' => 'run_cleaner'
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

    function register_cron()
    {

        // lets force a log reset //
        $reset_logs = get_option('fulcrum_ic_reset');
        if (!$reset_logs || $reset_logs !== 'done')
            update_option('fulcrum_ic_reset', true);

        add_action('ic__run_cleaner', [$this, 'run_cleaner']);
        $this->cron->schedule('ic__run_cleaner', 'one_minute');
    }

    function view_ic()
    {
        $logs = get_option('fulcrum_ic_logs');
        $this->partial('modules', 'image-cleaner', ['logs' => $logs]);
    }

    function check_name($name)
    {
        $output = [
            'name' => $name,
            'number' => false,
            'ext' => false
        ];

        $x = explode('-', $name);
        $coming_soon = strpos($name, 'image-coming-soon');

        if (count($x) >= 1) {
            // We need to cycle through the explosion in order to see if this is an origional //
            foreach ($x as $i => $part) {
                if ($i === 0) {
                    if ($coming_soon === false) {
                        $output['name'] = $part;
                    } else {
                        $output['name'] = 'image-coming-soon-placeholder';
                    }
                } else {
                    if (in_array($part, ['jpg', 'gif', 'png', 'jpeg'])) {
                        $output['ext'] = $part;
                    } else {
                        if (is_numeric($part))
                            $output['number'] = $part;
                        else if (!is_numeric($part) && $coming_soon === false)
                            $output['is_origional'] = true;
                    }
                }
            }

            if ($coming_soon !== false && $output['number'] === false) {
                $output['is_origional'] = true;
            }

            if (count($x) === 2) {
                $output['is_origional'] = true;
            }
        }

        return $output;
    }

    function find_origional($namer)
    {
        $query = [
            'post_type' => 'attachment',
            'post_status'    => 'inherit',
            'name' => $namer['name'] . '.' . $namer['ext'],
            'orderby' => 'post_name',
            'order' => 'DESC'
        ];
        $result = new WP_Query($query);

        $origional = ($result->found_posts > 0) ? $result->post : false;

        return $origional;
    }

    function run_no_image_cleaner()
    {
    }

    function run_cleaner()
    {
        $reset_logs = get_option('fulcrum_ic_reset');


        $logs = get_option('fulcrum_ic_logs');
        if (!$logs || ($reset_logs && $reset_logs !== 'done')) $logs = [
            'last_run' => time(),
            'posts' => [],
            'amount_deleted' => 0,
            'origionals_found' => 0,
            'posts_updated' => 0
        ];

        if ($reset_logs !== 'done')
            update_option('fulcrum_ic_reset', 'done');

        $logs['last_run'] = time();

        $return = [];
        // First lets get all the products that have a thumbnail id as well as hasn't been altered by teh system yet //
        $query = [
            'post_type' => 'attachment',
            'posts_per_page' => 50,
            'orderby' => 'name',
            // 's' => 'image-coming-soon-placeholder',
            'order' => 'DESC',
            'post_status' => 'inherit',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'ic-processed-media',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];

        $attachments = new WP_Query($query);

        // lets go through each attachment and make if its a duplicate find its origional then update the parent post //
        if ($attachments && $attachments->found_posts > 0) {
            foreach ($attachments->posts as $attachment) {
                if (is_object($attachment) && isset($attachment->ID)) {
                    $origional = $delete = false;
                    $thumb_ids = [];
                    // $product = get_post_ancestors($attachment->ID);
                    // Find out if this is a duplicate or origional //
                    $namer = $this->check_name($attachment->post_name);
                    $thumb_ids[$attachment->ID] = $attachment->ID;

                    if (!isset($namer['is_origional']) && $namer['name'] !== '' && $namer['number'] !== false) {
                        // we have a duplicate lets pull its origional //
                        $origional = $this->find_origional($namer);

                        if ($origional && isset($origional->ID)) {

                            $logs['origionals_found'] += 1;
                        }
                    }

                    $logs['last_attachments'] = $thumb_ids;

                    // Lets get all of our thumbnail id parent posts //
                    $q = [
                        'post_type' => 'product',
                        'posts_per_page' => -1,
                        'orderby' => 'post_title',
                        'meta_query' => [
                            'relation' => 'OR',
                            [
                                'key' => '_thumbnail_id',
                                'value' => $thumb_ids,
                                'compare' => 'IN'
                            ]
                        ]
                    ];

                    $parent = new WP_Query($q);

                    if ($parent && $parent->found_posts > 0) {
                        // If we do not have a matching origional we need to treat it as an origional //
                        if (!$origional && isset($namer['is_origional']) && $namer['is_origional']) {
                            // if this attachment is an origional then we should just alter the meta as it will not be needing an update to the post thumbnail //
                            update_post_meta($attachment->ID, 'ic-is-origional', true);
                            update_post_meta($attachment->ID, 'ic-processed-media', true);
                            $logs['origionals_found'] += 1;
                            $return['is_origional'] = true;
                        } else {
                            // Cycle through each product that came up for the query and lets make sure it uses our origional image as its thumbnail //
                            foreach ($parent->posts as $post) {
                                $setting_new = false;
                                if ($origional && isset($origional->ID)) {
                                    $return['set_new_thumb'] = $setting_new = set_post_thumbnail($post->ID, $origional->ID);

                                    // now that we set the thumbnail lets add our meta data to image and post //
                                    update_post_meta($post->ID, 'ic-processed-media', true);
                                    if ($setting_new) {
                                        $logs['posts_updated'] += 1;
                                        $logs['posts'][time()] = $post->ID;
                                        $delete = true;
                                    }
                                }
                            }
                        }
                    } else if ($parent->found_posts === 0 && !$origional && (isset($namer['is_origional']) && $namer['is_origional'])) {
                        // we found no results for this request //
                        update_post_meta($attachment->ID, 'ic-processed-media', true);
                        update_post_meta($attachment->ID, 'ic-not-thumbnail', true);
                        $return['no-origional-no-thumb'] = $attachment;
                    } else if ($parent->found_posts === 0 && $origional) {
                        // we have an origional image for this attachment and there are no posts so lets just delete it //
                        // $delete = true;
                        // lets make sure our origional image has products //
                        $args = [
                            'post_type' => 'product',
                            'posts_per_page' => 1,
                            'meta_query' => [
                                [
                                    'key' => '_thumbnail_id',
                                    'value' => $origional->ID
                                ]
                            ]
                        ];

                        $org_prods = new WP_Query($args);
                        if ($org_prods && $org_prods->found_posts > 0) {
                            // we have products for our origional we can safely delete this clone //
                            $delete = true;
                        } else {
                            update_post_meta($attachment->ID, 'ic-processed-media', true);
                            update_post_meta($attachment->ID, 'ic-no-product-association', true);
                        }

                        $return['not-origional-no-parent-posts'] = true;
                    }

                    if ($delete) {
                        $return['deleted_attachment'] = $deleted = wp_delete_attachment($attachment->ID, true);
                        if ($deleted)
                            $logs['amount_deleted'] += 1;
                    }

                    $logs['processed-media'][$attachment->ID] = $return;
                }
            }

            // now lets get all the posts for both the origional and the attachment //
            // we need to update the image meta to add the attachments parent ids //
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
