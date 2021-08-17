
console.log('ADP Loaded');
let $ = jQuery;
let ADP = {};
ADP.targets = {};

ADP.init = function () {
    let targets = {};
    targets.module = $('.d-adp');
    if (targets.module.length > 0) {
        targets.checkboxes = targets.module.find('.list-group-item');
    }
    ADP.targets = targets;

    this.register();
};

ADP.register = function () {
    if (this.targets.checkboxes) {
        this.targets.checkboxes.on('click', ADP.handle.toggle);
    }
};


ADP.handle = {
    toggle: function () {
        var t = $(this),
            input = t.find('input');
        if (input.prop('checked')) {
            t.addClass('selected');
        } else {
            t.removeClass('selected');
        }
    }
};

$(function () {
    ADP.init();

});