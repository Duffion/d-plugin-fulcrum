<?php

/* Fulcrum module template - PCS - Product Category Scraper */
$logs = $args['logs'];
$args = [
    'post_type' => 'attachment',
    'post_status' => 'inherit',
    'posts_per_page' => -1
];

$count = new WP_Query($args);
if ($count->found_posts > 0) {
    $count = $count->post_count;
} else {
    $count = '0';
}
?>

<div class="d-fulcrum__module d-module-container d-ic">
    <div class="d-fulcrum__module--inner">
        <?php // have the module run here //
        ?>
        <div class="row">

            <div class="col-12-sm gy-5">
                <h1 class="display-2">Media / Image Cleaner</h1>
                <?php if ($logs) { ?>
                    <h4>Last Run: <?= date('m-d-Y H:i:s a', $logs['last_run']); ?> | Total Updated: <strong><?= count($logs) - 1; ?></strong> | Total Media: <?= $count; ?></h4>
                <?php } else { ?>
                    <h4>Never ran sync</h4>
                <?php } ?>
            </div>

            <div class="col-6 pcr-scraper-rows">


            </div>
        </div>
    </div>
</div>