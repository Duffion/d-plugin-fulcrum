console.log('PCS Loaded');
let $ = jQuery;
let PCS = {};
PCS.targets = {};
PCS.register = function () {

    var actions = $('[data-pcs-action]');
    if (actions) {
        actions.on('click', PCS.handle.actions);
    }

}

PCS.handle = {
    actions: function () {
        let action = $(this).data('pcs-action'),
            uri = window.location.origin + '/wp-admin/admin-ajax.php?action=',
            data = {},
            cb = false;

        console.log('action clicked', action);
        switch (action) {
            case 'save-job':
                // gather form data and send to ajax function to save job
                console.log('saving job');
                var form = $(this).parent().parent().find('form.d-fulcrum__form'),
                    auri = uri + 'pcs_add_job';

                if (form.length > 0) {
                    data = form.serialize() + '&action=pcs_add_job';
                }
                cb = PCS.handle.add_result;
                break;

            case 'edit-job':
                // we need to push the data into the form to edit //
                var job = $(this).data('pcs-job');

                if (job) {
                    var form = $('#pcs-add-modal form');
                    console.log('form', form);
                    if (form.length > 0) {
                        form.trigger('reset');
                        form.find('[name="target-text"]').val(job.search);
                        form.find('[name="create-category-toggle"]').prop('checked', job.create_cat);
                        form.find('select.categories').val(job.categories);
                    }
                }
                console.log('edit', job);
                break;

            case 'remove-job':
                if (confirm('By deleting the job you will NOT be deleting the categories / tags it has made, are you sure you wish to delete the job?')) {
                    var job_id = $(this).data('pcs-job'),
                        auri = uri + 'pcs_remove_job';

                    cb = PCS.handle.remove_job;
                    data.id = job_id;
                } else {
                    return;
                }
                break;

            case 'run-jobs':
                var auri = uri + 'pcs_run_jobs';

                cb = PCS.handle.run_jobs;
                var d = new Date();

                data = { time: d.getTime() };
                break;
        }
        if (typeof auri !== 'undefined')
            $.post(auri, data, cb);
    },
    add_result: function (response) {
        alert('Job has been added!');
        console.log('response', response);
        location.reload();
    },
    remove_job: function (response) {
        alert('Job has been deleted!');
        location.reload();
    },
    run_jobs: function (response) {
        alert('The system has run the next job in queue: ' + response.results.job.search);
        location.reload();
    }
}


$(function () {
    PCS.register();

});