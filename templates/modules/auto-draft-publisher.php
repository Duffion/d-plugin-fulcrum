<?php
if (!isset($args['cats'])) return;
/* Fulcrum module template - PCS - Product Category Scraper */
$cats = $args['cats'];
?>

<div class="d-fulcrum__module d-module-container d-pcs container px-4">
    <div class="d-fulcrum__module--inner">
        <div class="row">
            <div class="col-12-sm gy-5">
                <h1 class="display-2">Auto Publish Products</h1>
                <?php
                if ( isset( $_REQUEST['response'] ) && $_REQUEST['response'] == 'success' && isset( $_REQUEST['count'] ) ) {
                ?>
                <p class="alert alert-warning">Successfully updated the categories to be auto-published and published <?= $_REQUEST['count']; ?> products in the selected categories.</p>
                <?php
                }
                ?>
                <p class="">Please toggle on and off your primary categories for auto publishing of the products attached</p>
            </div>
        </div>
        <?php
        if ( current_user_can( 'edit_posts' ) ) {
            $adp_nonce = wp_create_nonce( 'adp_category_nonce' );
            $global_cats = get_option( 'fulcrum_adp_categories' );
        ?>
            <div class="d-fulcrum__module--list d-fulcrum__form toggle">
                <form id="global-category-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
                    <input type="hidden" name="action" value="adp_category_response" />
                    <input type="hidden" name="adp_category_nonce" value="<?php echo $adp_nonce; ?>" />
                    <ul class="list-group flex-wrap">
                        <?php
                        if ($cats && is_array($cats) && !empty($cats)) {
                            foreach ($cats as $cat) {
                                $checked = ( in_array( $cat->term_id, $global_cats ) ? ' checked' : '' );
                        ?>
                                <li class="list-group-item col-md-6">
                                    <label class="form-check-label d-flex justify-content-between align-items-center" for="d--toggle-<?= $cat->term_id; ?>">
                                        <div class="form-check form-switch px-5">
                                            <input class="form-check-input" type="checkbox" id="d--toggle-<?= $cat->term_id; ?>" name="d--toggle-<?= $cat->term_id; ?>"<?= $checked; ?>>
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
                    <input type="submit" name="submit" id="submit" value="Submit">
                </form>
            </div>
        <?php
        }
        ?>
    </div>
</div>