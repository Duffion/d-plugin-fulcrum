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
        let action = $(this).data('pcr-action');

        console.log('action clicked', action);
        switch (action) {
            case 'add-new':
                // open the add new form //

                break;
            case 'save-job':
                // gather form data and send to ajax function to save job
                console.log('saving job');
                var form = $(this).parent().parent().find('form.d-fulcrum__form'),
                    data = {},
                    auri = window.location.origin + '/wp-admin/admin-ajax.php?action=pcs_add_job';

                if (form.length > 0) {
                    data = form.serialize() + '&action=pcs_add_job';
                }
                console.log('data', auri, data);
                $.post(auri, data, PCR.handle.add_result);

                break;

            case 'remove-job':
                var job_id = $(this).data('pcr-job'),
                    auri = window.location.origin + '/wp-admin/admin-ajax.php?action=pcs_remove_job';
                console.log(job_id);
                $.post(auri, { id: job_id }, PCR.handle.remove_job);
                break;
        }
    },
    add_result: function (response) {
        console.log('response', response);
        location.reload();
    },
    remove_job: function (response) {
        location.reload();
    }
}

PCR.ajax = {
    add: function () {

    }
}

$(function () {
    PCR.register();

});