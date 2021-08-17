console.log('Module Core Loaded');
let $ = jQuery;
let MODULES = {};
MODULES.targets = {};
MODULES.register = function () {

    var actions = $('[data-fulcrum-action]');
    if (actions) {
        actions.on('click', MODULES.handle.actions);
    }

}

MODULES.template = {
    triggers: {
        validation: function (field = false) {
            if (!field) return false;

            var $field = $('.d-fulcrum__form [name="' + field + '"]');
            console.log('field', $field);
            if ($field.length > 0) {
                $field.addClass('invalid');
            }
        },
        reset_validation: function () {
            var fields = $('.d-fulcrum__form .required');
            if (fields.length > 0) {
                fields.removeClass('invalid');
            }
            // fields.each(function(i, el) {
            //     $(this).removeClass
            // });
        }
    }
};

MODULES.handle = {
    actions: function () {
        let action = $(this).data('fulcrum-action'),
            module = $('body').data('fulcrum-module'),
            uri = window.location.origin + '/wp-admin/admin-ajax.php?action=',
            data = {},
            cb = false;

        // TODO - Check if a manual callback is set from our module data value if it exists //
        if (module && module !== '') {

        }
        switch (action) {
            case 'select-all':
                let $t = $(this);
                var target = $($t.data('fulcrum-target')),
                    option = $t.data('fulcrum-option');
                console.log('target', target);
                if (target.length > 0) {
                    option = $t.data('fulcrum-option');
                    target.each(function (k, v) {
                        if (!$(this).hasClass('selected')) {
                            $(this).addClass('selected').find('input').prop('checked', true);
                            if (option !== 'deselect') {
                                $t.data('fulcrum-option', 'deselect').html('- Deselect All');
                            }
                        } else if (option === 'deselect') {
                            // deselect all //
                            $(this).removeClass('selected').find('input').prop('checked', false);
                            if (option == 'deselect') {
                                $t.data('fulcrum-option', 'select').html('+ Select All');
                            }
                        }
                    });
                }
                break;
            case 'save-job':
                // gather form data and send to ajax function to save job
                console.log('saving job');
                var form = $(this).parent().parent().find('form.d-fulcrum__form'),
                    auri = uri + 'pcs_add_job';

                if (form.length > 0) {
                    data = form.serialize() + '&action=pcs_add_job';
                }
                cb = MODULES.handle.add_result;
                break;

            case 'expand-list':
                var target = $(this).parent().parent();

                if (target.hasClass('small')) {
                    target.removeClass('small');
                    $(this).html('Collapse');
                } else {
                    target.addClass('small');
                    $(this).html('Expand');
                }
                break;

            case 'edit-job':
                // we need to push the data into the form to edit //
                var job = $(this).data('fulcrum-job');

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
                    var job_id = $(this).data('fulcrum-job'),
                        auri = uri + 'pcs_remove_job';

                    cb = MODULES.handle.remove_job;
                    data.id = job_id;
                } else {
                    return;
                }
                break;

            case 'run-jobs':
                var auri = uri + 'pcs_run_jobs';

                cb = MODULES.handle.run_jobs;
                var d = new Date();

                data = { time: d.getTime() };
                break;
        }
        if (typeof auri !== 'undefined')
            $.post(auri, data, cb).fail(
                MODULES.handle.failed
            );
    },
    failed: function (xhr, status, error) {
        var response = (typeof xhr.responseJSON !== 'undefined') ? xhr.responseJSON : false,
            message = (typeof response.message !== 'undefined') ? response.message : 'Failed: No Response given... Contact support.';

        console.log('failed', response, response.field, error);
        MODULES.template.triggers.reset_validation();
        // TODO: If the request failed on validation -> apply frontend triggers to turn on missing required field notifications //
        message = 'Failed to Add Job - Reason: ' + message;
        if (typeof response.field !== 'undefined' && response.field !== '') {
            MODULES.template.triggers.validation(response.field);
            message += '\n Field Failed: ' + response.field;
        } else if (typeof response.rule !== 'undefined' && response.rule !== '')
            message += '\n Rule Failed: ' + response.rule;

        alert('Failed to Add Job - Reason: ' + message);
    },
    add_result: function (response) {
        if (!response || typeof response === 'undefined')
            alert('Failed. No given reason. Check server logs');

        alert('Successfully created / updated Job!');
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
    MODULES.register();

});