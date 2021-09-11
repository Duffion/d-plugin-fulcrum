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
                    <h4>Last Run: <?= date('m-d-Y H:i:s a', $logs['last_run']); ?></h4>
                    <p>Total Posts Updated: <strong><?= $logs['posts_updated']; ?></strong></p>
                    <p>Media Deleted: <strong><?= $logs['amount_deleted']; ?></strong></p>
                    <p>Origional Media Found: <strong><?= $logs['origionals_found']; ?></strong></p>
                    <h5>Total Current Media: <?= $count; ?></h5>
                <?php } else { ?>
                    <h4>Never ran sync</h4>
                <?php } ?>
            </div>

            <div class="col-6 pcr-scraper-rows">

                <h2>History of updated posts</h2>
                <div class="results adp-jobs gy-5 d-inline-flex flex-wrap">
                    <?php
                    if (count($logs['posts']) > 0) {
                        $posts = array_reverse($logs['posts']);
                        $i = 0;
                        foreach ($posts as $id) {
                            if ($i < 15) {
                                $post = get_post($id);
                    ?>
                                <div class="card col-4 adp-jobs-job">
                                    <div class="card-body">
                                        <h5 class="card-title"><a href="<?= get_permalink($post); ?>"><?= $post->post_title; ?></a></h5>
                                    </div>
                                </div>
                    <?php
                            }
                            $i++;
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>