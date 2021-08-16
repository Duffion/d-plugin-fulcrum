<?php
if (!isset($args['cats'])) return;
/* Fulcrum module template - PCS - Product Category Scraper */
$cats = $args['cats'];
$nonce = (isset($args['nonce']) ? $args['nonce'] : false);
?>

<div class="d-fulcrum__module d-module-container d-pcs container px-4">
    <div class="d-fulcrum__module--inner">
        <div class="row">
            <div class="col-12-sm gy-5">
                <h1 class="display-2">Auto Publish Products</h1>
                <?php
                if (isset($_REQUEST['response']) && $_REQUEST['response'] == 'success' && isset($_REQUEST['count'])) {
                ?>
                    <p class="alert alert-warning">Successfully updated the categories to be auto-published and published <?= $_REQUEST['count']; ?> products in the selected categories.</p>
                <?php
                }
                ?>
                <p class="">Please toggle on and off your primary categories for auto publishing of the products attached</p>
            </div>
        </div>
        <?php
        if (current_user_can('edit_posts')) {
            $global_cats = get_option('fulcrum_adp_categories');
        ?>
            <div class="d-fulcrum__module--list d-fulcrum__form toggle">
                <form id="global-category-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
                    <input type="hidden" name="action" value="adp_category_response" />
                    <?php if ($nonce) { ?>
                        <input type="hidden" name="fulcrum_nonce" value="<?php echo $fulcrum_nonce; ?>" />
                    <?php } ?>
                    <div class="col-12 pcr-options gy-5">
                        <div class="btn-group">
                            <button class="btn btn-primary" type="button" data-pcr-action="adp-select-all">+ Select All</button>
                        </div>
                    </div>
                    <ul class="list-group flex-wrap">
                        <?php
                        if ($cats && is_array($cats) && !empty($cats)) {
                            foreach ($cats as $cat) {
                                $checked = (in_array($cat->term_id, $global_cats) ? ' checked' : '');
                        ?>
                                <li class="list-group-item col-md-6">
                                    <label class="form-check-label d-flex justify-content-between align-items-center" for="d--toggle-<?= $cat->term_id; ?>">
                                        <div class="form-check form-switch px-5">
                                            <input class="form-check-input" type="checkbox" id="d--toggle-<?= $cat->term_id; ?>" name="d--toggle-<?= $cat->term_id; ?>" <?= $checked; ?>>
                                        </div>
                                        <div class="mb-3 flex-grow-1">
                                            <div class="fw-bold">
                                                <?= $cat->name; ?>
                                            </div>
                                        </div>
                                    </label>
                                </li>
                        <?php
                            }
                        }
                        ?>
                    </ul>
                    <div class="col-12 pcr-options gy-5">
                        <div class="btn-group">
                            <input type="submit" name="submit" class="btn btn-success" id="submit" value="Submit">
                        </div>
                    </div>

                </form>
            </div>
            <?php
            $published = get_option('fulcrum_adp_published');
            if ($published) {
                $published = array_reverse($published);
            ?>
                <div class="col-12 adp-history">
                    <h2>Recently Published Posts</h2>
                    <div class="results adp-jobs gy-5 d-inline-flex flex-wrap">
                        <?php
                        $i = 0;
                        foreach ($published as $post) {
                            if ($i <= 14) {
                        ?>
                                <div class="card col-4 adp-jobs-job">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="<?= get_permalink($post); ?>">
                                                <strong>
                                                    <?= $post['post_title']; ?>
                                                </strong>
                                            </a>
                                        </h5>
                                        <p class="card-text">
                                            Time Job Ran - <strong><?= ($post['time'] ? date('m-d-Y h:i a', $post['time']) : false) ?></strong>
                                        </p>
                                    </div>

                                </div>
                        <?php
                            }
                            $i++;
                        }
                        ?>
                    </div>
                </div>
            <?php
            }
            ?>

        <?php
        }
        ?>
    </div>
</div>