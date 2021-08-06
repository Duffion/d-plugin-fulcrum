<?php

/* Fulcrum module template - PCS - Product Category Scraper */
$cats = $args['cats'];
$jobs = $args['jobs'];
// wpp($args['jobs']);
?>

<div class="d-fulcrum__module d-module-container d-pcs container px-4">
    <div class="d-fulcrum__module--inner">
        <?php // have the module run here //
        ?>
        <div class="row">

            <div class="col-12-sm gy-5">
                <h1 class="display-2">Product Category Scraper</h1>
                <p class=""></p>
            </div>

            <div class="col-12 pcr-scraper-rows">

                <div class="results pcr-jobs gy-5">
                    <?php if ($jobs && count($jobs) > 0) {
                        foreach ($jobs as $id => $job) {
                    ?>
                            <div class="card pcr-jobs-job">
                                <div class="card-body">
                                    <h5 class="card-title"><strong><?= $job['search']; ?></strong> Categories - <span><?= implode(' ,', $job['categories']); ?></span></h5>
                                    <p class="card-text">Last Time Job Ran - <strong><?= ($job['last_ran'] ? date('m-d-Y h:i a', $job['last_ran']) : false) ?></p>
                                </div>
                                <div class="card-options">
                                    <div class="btn-group">
                                        <button class="btn btn-warning" type="button" data-pcr-action="remove-job" data-pcr-job="<?= $id; ?>">- Delete Job</button>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                    } else {
                        ?>
                        <div class="no-results">
                            <h5>No Jobs Found</h5>
                            <p>There are no jobs listed... Please add a job to scrape for tags</p>
                        </div>
                    <?php
                    }
                    ?>
                </div>
                <div class="col-12 pcr-options gy-5">
                    <div class="btn-group">
                        <button class="btn btn-success" type="button" data-modal-trigger="#pcr-add-modal">+ Add New Job</button>
                        <button class="btn btn-secondary" type="button" data-pcr-action="run-jobs">Manually Run Jobs</button>
                        <button class="btn btn-light" type="button" data-pcr-action="export-jobs">Export Jobs</button>
                        <button class="btn btn-light" type="button" data-pcr-action="import-jobs">Import Jobs</button>
                    </div>
                </div>
            </div>
            <?php
            // This is how modals need to be formed. You will need to have them wrapped in the modal-backdrop //
            ?>
            <div class="modal-backdrop hidden">
                <div class="modal" id="pcr-add-modal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add new job</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form class="row g-3 d-fulcrum__form">
                                    <div class="col-auto">
                                        <label for="targetText" class="hidden">Search Text</label>
                                        <input type="text" name="target-text" class="form-control-plaintext" id="targetText" value="" placeholder="Search Text...">
                                    </div>
                                    <div class="col-auto">
                                        <div class="input-group mb-3">
                                            <div class="input-group-text col-auto">
                                                <label for="create-category">
                                                    <input class="form-check-input mt-0" name="create-category-toggle" id="create-category" type="checkbox" value="true" aria-label="Also create Category">
                                                    Also create as a Category
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 justify-content-center">
                                        <p class="notice" style="margin-left: 0; margin-right: 0;">Use CRTL + Click to add mutiple categories</p>
                                        <select class="form-select align-center" size="15" multiple aria-label="multiple select categories" name="target-categories[]">
                                            <?php
                                            if ($cats && is_array($cats) && !empty($cats)) {
                                                foreach ($cats as $cat) {
                                            ?>
                                                    <option value="<?= $cat->term_id; ?>"><?= $cat->name; ?></option>
                                            <?php
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" data-pcr-action="save-job">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>