<?php

/* Fulcrum module template - PCS - Product Category Scraper */
$logs = $args['logs'];
$indexed = $args['indexed'];
$finished = $args['finished'];
// wpp($finished['attachments']) . die;
// wpp($logs);
$counts = wp_count_attachments();
$count = 0;
if ($counts) {
    foreach ($counts as $key => $c) {
        $count += $c;
    }
}
// wpp($indexed['query']);
// die;
?>

<div class="d-fulcrum__module d-module-container d-ic">
    <div class="d-fulcrum__module--inner">
        <?php // have the module run here //
        ?>
        <div class="row">

            <div class="col-12-sm gy-5">
                <h1 class="display-2">Media / Image Cleaner</h1>
                <div class="log-box">
                    <h5>Total Current Media: <?= $count; ?></h5>
                </div>
                <div class="log-box indexed-box">
                    <h3>Indexed Media Logs</h3>
                    <?php if ($indexed && isset($indexed['last_run'])) { ?>
                        <div class="indexed-box-counts">
                            <p>Last Index Run: <strong><?= date('m-d-Y h:i:s a', $indexed['last_run']) ?></strong></p>
                            <p>Products waiting to be indexed: <strong><?= isset($indexed['total_posts']) ? $indexed['total_posts'] : 0; ?></strong></p>
                            <p>Total Indexed Media: <strong><?= isset($finished['attachments']) ? count($indexed['attachments']) : 0; ?></strong></p>
                            <p>Total Finished / Updated Media: <strong><?= isset($finished['attachments']) ? count($finished['attachments']) : 0; ?></strong></p>
                        </div>
                    <?php } else {
                        echo '<p>Not yet indexed.</p>';
                    } ?>
                </div>
                <div class='ic-actions button-bar'>
                    <a href='<?= admin_url('admin.php?page=module-ic&ic_action=reset'); ?>'>Reset Indexes</a>
                </div>

                <div class="log-box indexed-box">
                    <h3>Processed Media Logs</h3>
                    <?php if ($logs) { ?>
                        <h4>Last Run: <?= date('m-d-Y h:i:s a', $logs['last_run']); ?></h4>
                        <p>Total Posts Updated: <strong><?= (isset($logs['posts_updated']) ? $logs['posts_updated'] : 0); ?></strong></p>
                        <p>Media Deleted: <strong><?= (isset($logs['amount_deleted']) ? $logs['amount_deleted'] : 0); ?></strong></p>
                        <p>Origional Media Found: <strong><?= (isset($logs['origionals_found']) ? $logs['origionals_found'] : 0); ?></strong></p>
                    <?php } else { ?>
                        <p>Not yet ran our indexed jobs.</p>
                    <?php } ?>
                </div>
            </div>

            <div class="col-6 pcr-scraper-rows">

                <h2>History of updated posts</h2>
                <div class="results adp-jobs gy-5 d-inline-flex flex-wrap">
                    <?php
                    if ($logs && isset($logs['posts']) && count($logs['posts']) > 0 && $posts = $logs['posts']) {
                        arsort($posts);
                        $i = 0;
                        foreach ($posts as $id => $time) {
                            if ($i < 24) {
                                $post = get_post($id);
                    ?>
                                <div class="card col-4 adp-jobs-job">
                                    <div class="card-body">
                                        <h5 class="card-title"><a href="<?= get_edit_post_link($id); ?>"><?= $post->post_title; ?></a></h5>
                                        <p class="card-content">Time Processed: <strong><?= date('m-d-Y h:i:s a', $time); ?></strong></p>
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