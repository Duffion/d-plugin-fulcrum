console.log('PCR Loaded');
let $ = jQuery;
let PCR = {};
PCR.targets = {};
PCR.register = function () {

    var actions = $('[data-pcr-action]');
    if (actions) {
        actions.on('click', PCR.handle.actions);
    }

}

PCR.handle = {
    actions: function () {
        let action = $(this).data('pcr-action'),
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
                cb = PCR.handle.add_result;
                break;

            case 'edit-job':
                // we need to push the data into the form to edit //
                var job = $(this).data('pcr-job');

                if (job) {
                    var form = $('#pcr-add-modal form');
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
                var job_id = $(this).data('pcr-job'),
                    auri = uri + 'pcs_remove_job';

                cb = PCR.handle.remove_job;
                data.id = job_id;
                break;

            case 'run-jobs':
                var auri = uri + 'pcs_run_jobs';

                cb = PCR.handle.run_jobs;
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
    PCR.register();

});