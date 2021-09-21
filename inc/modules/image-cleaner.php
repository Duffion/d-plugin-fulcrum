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

    var $menu_item = [],
        $per_page_index = 75,
        $per_page = 25;

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
            // 'index_attachments' => [
            //     'hook' => 'wp_loaded',
            //     'function' => 'index_attachments'
            // ],
            // 'run_cleaner' => [
            //     'hook' => 'wp_loaded',
            //     'function' => 'run_cleaner'
            // ],
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
        if (!$reset_logs || $reset_logs !== 'done') {
            update_option('fulcrum_ic_reset', true);
        }

        add_action('ic__run_index', [$this, 'index_attachments']);
        $this->cron->schedule('ic__run_index', 'one_minute');

        add_action('ic__run_cleaner', [$this, 'run_cleaner']);
        $this->cron->schedule('ic__run_cleaner', 'five_minutes');
    }

    function view_ic()
    {
        $req = isset($_REQUEST['ic_action']) ? $_REQUEST['ic_action'] : false;

        if ($req && $req === 'reset') {
            $this->reset_indexes();
            wp_redirect('/wp-admin/admin.php?page=module-ic');
        }

        $args = [];
        $args['logs'] = $logs = get_option('fulcrum_ic_logs');
        $args['finished'] = $finished = get_transient('fulcrum_ic_finished');
        $args['indexed'] = $indexed = get_transient('fulcrum_ic_indexed');

        $this->partial('modules', 'image-cleaner', $args);
    }

    function find_origional($title)
    {
        $query = [
            'post_type' => 'attachment',
            'post_status'    => 'inherit',
            'name' => $title,
            'orderby' => 'post_name',
            'order' => 'DESC'
        ];

        $result = new WP_Query($query);

        $origional = ($result->found_posts > 0) ? $result->post : false;

        return $origional;
    }


    function generate_cooldown()
    {
        $time = strtotime("+ 1 day");

        return $time;
    }

    function process_attachment($attachment, $post)
    {
        $output = [
            'origional' => false,
            'name' => $attachment->post_name,
            'ext' => false,
            'id' => $attachment->ID,
            'is_clone' => false,
            'is_coming_soon' => false
        ];

        if ($attachment && isset($attachment->ID)) {
            $hash = get_post_meta($attachment->ID, 'mdd_hash', true);
            // first we need to check the name to see if this is a duplicate //
            $name = $attachment->post_name;
            $namer = explode('-', $name);
            $coming_soon = strpos($attachment->post_name, 'image-coming-soon-placeholder');
            $cloned = (isset($namer[count($namer) - 1]) ? $namer[count($namer) - 1] : false);

            foreach ($namer as $i => $part) {
                // check if we have an ext part //
                if (in_array($part, ['jpg', 'gif', 'png', 'jpeg'])) {
                    $output['ext'] = $part;
                }
            }

            if ($coming_soon !== FALSE) {
                $output['is_coming_soon'] = true;
                // we have a coming soon photo placeholder so lets treat it a bit differently //
                $output['origional'] = $this->find_origional('image-coming-soon-placeholder-jpg')->ID;
            } else {
                if (is_numeric($namer[0])) {

                    $org = $this->find_origional($namer[0] . '-' . $output['ext']);

                    // we have to see if the origional exists for the first part of the name //
                    $output['origional'] = $org->ID;
                }
                // last part of this string is a number or part of the clone //
                if ($cloned && is_numeric($cloned) && strlen($cloned) < 4) {
                    $output['is_clone'] = true;
                    // now that we know this is a clone lets pop off the end of the title //
                    // $title = str_replace('-' . $cloned, '', $attachment->post_name);
                    // lets find its origional based on our name //
                    // $output['origional'] = $this->find_origional($title)->ID;
                }
            }
        }

        return $output;
    }

    // lets get or setup our index transients //
    function __index($ns, $default = [])
    {
        // delete_transient($ns);
        $existing = get_transient($ns);
        if (!$existing) {
            // since it doesnt exist yet lets create it with default options //
            set_transient($ns, $default, WEEK_IN_SECONDS);
        }

        return get_transient($ns);
    }

    function generate_indexes()
    {
        $output = [];
        $transients = [
            'fulcrum_ic_indexed' => [
                'last_run' => 0,
                'total_posts' => 0,
                'attachments' => []
            ],
            'fulcrum_ic_finished' => [
                'last_run' => 0,
                'attachments' => []
            ]
        ];
        foreach ($transients as $trans => $rules) {
            $output[$trans] = $this->__index($trans, $rules);
        }

        return $output;
    }
    /*
    Steps for success:
        1. Get a list of products that have thumbnail IDs - make sure they have not been updated by the plugin previously
        2. Pull down the attachment and check to see if it is a duplicate by the name of that upload
        3. Create list of attachment ids that are in fact duplicates from all of our products
        4. Find the origional for the attachement (if origional can not be found then designate this as an origional)
        5. Update the product thumbnail to use the origional image (if one was found)
        6. Create a list of attachments that need to be deleted
        7. Delete cloned attachment list
    */

    // The cleaner will go through all of our logged and indexed attachments and update their post parent / delete the clones / update logs //
    function run_cleaner()
    {
        $indexes = $this->generate_indexes();
        $index = $indexes['fulcrum_ic_indexed'];
        // wpp($indexes) . die;
        $reset_logs = get_option('fulcrum_ic_reset');
        $logs = get_option('fulcrum_ic_logs');
        if (!$logs) $logs = [
            'last_run' => time(),
            'posts' => [],
            'amount_deleted' => 0,
            'origionals_found' => 0,
            'posts_updated' => 0
        ];

        $finished = [];
        if ($index && isset($index['attachments']) && $attachments = $index['attachments']) {
            $i = 0;
            foreach ($attachments as $post_id => $attach) {
                // end it if we have more than 15 processing //
                if ($i >= $this->per_page) break;

                $finished[$post_id]['ran'] = time();

                $origional = $attach['origional'];
                $logs['posts'][$post_id] = time();
                $is_origional = ($origional && $origional == $attach['id']);
                // if (!$attach['is_coming_soon'])
                //     wpp($attach) . var_dump($is_origional) . die;
                // we need to run through each attachment job and process it properly based on if its origional or not //
                if (($attach['is_coming_soon'] || $attach['is_clone']) && !$is_origional && $origional) {
                    // if we have a clone and the origional is present then we need to move the posts thumb to the origional and delete the old version //
                    $updated = set_post_thumbnail($post_id, $origional);
                    if ($updated) {
                        $logs['posts_updated'] += 1;
                        $finished[$post_id]['set_new'] = $updated;
                    }

                    $finished[$post_id]['deleted'] = $deleted = wp_delete_attachment($attach['id'], true);
                    if ($deleted)
                        $logs['amount_deleted'] += 1;
                } else {
                    // do nothing besides removing from our indexer as this item is already using an origional from our index logic //
                    $finished[$post_id]['is_origional'] = true;
                    $logs['origionals_found'] += 1;
                }
                delete_post_meta($post_id, 'ic_index');
                update_post_meta($post_id, 'ic_altered', time());
                // once a job has been processed lets remove it from our indexer //
                unset($indexes['fulcrum_ic_indexed']['attachments'][$post_id]);

                $i++;
            }
            // wpp($finished) . die;
            // update our transients //
            $indexes['fulcrum_ic_finished']['last_run'] = time();
            $indexes['fulcrum_ic_finished']['attachments'] += $finished;

            set_transient('fulcrum_ic_indexed', $indexes['fulcrum_ic_indexed']);
            set_transient('fulcrum_ic_finished', $indexes['fulcrum_ic_finished']);

            update_option('fulcrum_ic_logs', $logs);
        }

        // wpp($finished);
        // wpp(count($indexes['fulcrum_ic_indexed']['attachments']));
        // wpp($indexes) . die;
    }

    // we need to have the system index all the attachment jobs in the background based on the product posts //
    function index_attachments()
    {
        $index = get_transient('fulcrum_ic_indexed');
        if (!$index) $index = [
            'attachments' => []
        ];

        $index['last_run'] = time();

        $cooldown = $this->generate_cooldown();
        // remove_filter('posts_groupby');
        // Get all the products we need //
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $this->per_page_index,
            'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'],
            'orderby' => 'post_title',
            'order' => 'ASC',

            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'ic_index',
                    'compare' => 'NOT EXISTS'
                ],

                [
                    'relation' => 'OR',
                    [
                        'key' => 'ic_altered',
                        'value' => $cooldown,
                        'compare' => '<',
                    ],
                    [
                        'key' => 'ic_altered',
                        'compare' => 'NOT EXISTS'
                    ]
                ],

                [
                    'key' => '_stock_status',
                    'value' => ['outofstock', 'instock'],
                    'compare' => 'IN'
                ]

            ]
        ];

        $products = new WP_Query($args);
        // wpp($products) . die;
        if ($products && $products->found_posts > 0) {
            // we have our list of products, lets get their attachments and process those //
            $index['total_posts'] = $products->found_posts;
            // $index['query'] = $products;

            foreach ($products->posts as $i => $post) {
                $attachment = get_post(get_post_thumbnail_id($post->ID));

                $index['attachments'][$post->ID] = $this->process_attachment($attachment, $post);
                // now set this as indexed //
                $updated = update_post_meta($post->ID, 'ic_index', time());
            }

            // wpp($index) . die;
            // now lets update our transient //
            set_transient('fulcrum_ic_indexed', $index, 0);
            // wpp(count($index['attachments'])) . die;
        }
    }

    function reset_indexes()
    {
        $finished = get_transient('fulcrum_ic_finished');
        if (isset($finished['attachments']) && count($finished['attachments']) > 0) {
            foreach ($finished['attachments'] as $id => $attach) {
                // lets reset our index meta trigger
                update_post_meta($id, 'ic_altered', 0);
            }
        }


        $indexes = get_transient('fulcrum_ic_indexed');
        if (isset($indexes['attachments']) && count($indexes['attachments']) > 0) {
            foreach ($indexes['attachments'] as $id => $attach) {
                // lets reset our index meta trigger
                delete_post_meta($id, 'ic_index');
            }
        }
        delete_transient('fulcrum_ic_indexed');
        delete_transient('fulcrum_ic_finished');
        delete_option('fulcrum_ic_logs');
    }

    // function run_cleaner()
    // {
    //     $reset_logs = get_option('fulcrum_ic_reset');


    //     $logs = get_option('fulcrum_ic_logs');
    //     if (!$logs || ($reset_logs && $reset_logs !== 'done')) $logs = [
    //         'last_run' => time(),
    //         'posts' => [],
    //         'amount_deleted' => 0,
    //         'origionals_found' => 0,
    //         'posts_updated' => 0
    //     ];

    //     if ($reset_logs !== 'done')
    //         update_option('fulcrum_ic_reset', 'done');

    //     $logs['last_run'] = time();

    //     $return = [];
    //     // First lets get all the products that have a thumbnail id as well as hasn't been altered by teh system yet //
    //     $query = [
    //         'post_type' => 'attachment',
    //         'posts_per_page' => 50,
    //         'orderby' => 'name',
    //         // 's' => 'image-coming-soon-placeholder',
    //         'order' => 'DESC',
    //         'post_status' => 'inherit',
    //         'meta_query' => [
    //             'relation' => 'AND',
    //             [
    //                 'key' => 'ic-processed-media',
    //                 'compare' => 'NOT EXISTS'
    //             ]
    //         ]
    //     ];

    //     $attachments = new WP_Query($query);
    //     wpp($attachments) . die;
    //     // lets go through each attachment and make if its a duplicate find its origional then update the parent post //
    //     if ($attachments && $attachments->found_posts > 0) {
    //         foreach ($attachments->posts as $attachment) {
    //             if (is_object($attachment) && isset($attachment->ID)) {
    //                 $origional = $delete = false;
    //                 $thumb_ids = [];
    //                 // $product = get_post_ancestors($attachment->ID);
    //                 // Find out if this is a duplicate or origional //
    //                 $namer = $this->check_name($attachment->post_name);
    //                 $thumb_ids[$attachment->ID] = $attachment->ID;

    //                 if (!isset($namer['is_origional']) && $namer['name'] !== '' && $namer['number'] !== false) {
    //                     // we have a duplicate lets pull its origional //
    //                     $origional = $this->find_origional($namer);

    //                     if ($origional && isset($origional->ID)) {

    //                         $logs['origionals_found'] += 1;
    //                     }
    //                 }

    //                 $logs['last_attachments'] = $thumb_ids;

    //                 // Lets get all of our thumbnail id parent posts //
    //                 $q = [
    //                     'post_type' => 'product',
    //                     'posts_per_page' => -1,
    //                     'orderby' => 'post_title',
    //                     'meta_query' => [
    //                         'relation' => 'OR',
    //                         [
    //                             'key' => '_thumbnail_id',
    //                             'value' => $thumb_ids,
    //                             'compare' => 'IN'
    //                         ]
    //                     ]
    //                 ];

    //                 $parent = new WP_Query($q);

    //                 if ($parent && $parent->found_posts > 0) {
    //                     // If we do not have a matching origional we need to treat it as an origional //
    //                     if (!$origional && isset($namer['is_origional']) && $namer['is_origional']) {
    //                         // if this attachment is an origional then we should just alter the meta as it will not be needing an update to the post thumbnail //
    //                         update_post_meta($attachment->ID, 'ic-is-origional', true);
    //                         update_post_meta($attachment->ID, 'ic-processed-media', true);
    //                         $logs['origionals_found'] += 1;
    //                         $return['is_origional'] = true;
    //                     } else {
    //                         // Cycle through each product that came up for the query and lets make sure it uses our origional image as its thumbnail //
    //                         foreach ($parent->posts as $post) {
    //                             $setting_new = false;
    //                             if ($origional && isset($origional->ID)) {
    //                                 $return['set_new_thumb'] = $setting_new = set_post_thumbnail($post->ID, $origional->ID);

    //                                 // now that we set the thumbnail lets add our meta data to image and post //
    //                                 update_post_meta($post->ID, 'ic-processed-media', true);
    //                                 if ($setting_new) {
    //                                     $logs['posts_updated'] += 1;
    //                                     $logs['posts'][time()] = $post->ID;
    //                                     $delete = true;
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 } else if ($parent->found_posts === 0 && !$origional && (isset($namer['is_origional']) && $namer['is_origional'])) {
    //                     // we found no results for this request //
    //                     update_post_meta($attachment->ID, 'ic-processed-media', true);
    //                     update_post_meta($attachment->ID, 'ic-not-thumbnail', true);
    //                     $return['no-origional-no-thumb'] = $attachment;
    //                 } else if ($parent->found_posts === 0 && $origional) {
    //                     // we have an origional image for this attachment and there are no posts so lets just delete it //
    //                     // $delete = true;
    //                     // lets make sure our origional image has products //
    //                     $args = [
    //                         'post_type' => 'product',
    //                         'posts_per_page' => 1,
    //                         'meta_query' => [
    //                             [
    //                                 'key' => '_thumbnail_id',
    //                                 'value' => $origional->ID
    //                             ]
    //                         ]
    //                     ];

    //                     $org_prods = new WP_Query($args);
    //                     if ($org_prods && $org_prods->found_posts > 0) {
    //                         // we have products for our origional we can safely delete this clone //
    //                         $delete = true;
    //                     } else {
    //                         update_post_meta($attachment->ID, 'ic-processed-media', true);
    //                         update_post_meta($attachment->ID, 'ic-no-product-association', true);
    //                     }

    //                     $return['not-origional-no-parent-posts'] = true;
    //                 }

    //                 if ($delete) {
    //                     $return['deleted_attachment'] = $deleted = wp_delete_attachment($attachment->ID, true);
    //                     if ($deleted)
    //                         $logs['amount_deleted'] += 1;
    //                 }

    //                 $logs['processed-media'][$attachment->ID] = $return;
    //             }
    //         }

    //         // now lets get all the posts for both the origional and the attachment //
    //         // we need to update the image meta to add the attachments parent ids //
    //     }

    //     update_option('fulcrum_ic_logs', $logs);
    // }
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
