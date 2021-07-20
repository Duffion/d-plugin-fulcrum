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
                <p class="">Please toggle on and off your primary categories for auto publishing of the products attached</p>
            </div>
        </div>
        <div class="d-fulcrum__module--list d-fulcrum__form toggle">
            <ul class="list-group flex-wrap">
                <?php
                if ($cats && is_array($cats) && !empty($cats)) {
                    foreach ($cats as $cat) {
                ?>
                        <li class="list-group-item col-md-6">
                            <label class="form-check-label d-flex justify-content-between align-items-center" for="d--toggle-<?= $cat->term_id; ?>">
                                <div class="form-check form-switch px-5">
                                    <input class="form-check-input" type="checkbox" id="d--toggle-<?= $cat->term_id; ?>">
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
        </div>
    </div>
</div>