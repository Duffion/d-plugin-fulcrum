
let $ = jQuery,
    MODALS = {};
MODALS.targets = {
    modals: false,
    triggers: false
};

MODALS.init = function () {
    this.define();
    this.register();

    console.log('Modals', this);
}

MODALS.define = function () {
    let modals = $('.modal'),
        triggers = $('[data-modal-trigger]');

    this.targets.backdrop = $('.modal-backdrop');
    if (modals.length > 0) {
        this.targets.modals = modals;
    }

    if (triggers.length > 0) {
        this.targets.triggers = triggers;
    }
}

MODALS.register = function () {
    if (this.targets.triggers) {
        this.targets.triggers.on('click', this.handle.open);
    }

    if (this.targets.backdrop && this.targets.backdrop.length > 0) {
        // this.targets.backdrop.on('click', this.handle.close);
        this.targets.backdrop.find('[data-bs-dismiss]').on('click', this.handle.close);
    }
}

MODALS.handle = {
    open: function () {
        // get the target //
        let target = $(this).data('modal-trigger');
        if (target && target !== '') {
            $('html, body').css('overflow', 'hidden');
            MODALS.targets.backdrop.addClass('opened').fadeIn(300, function () {
                $(target).slideDown(400);
            });
        }
    },
    close: function () {
        let backdrop = MODALS.targets.backdrop;
        if (backdrop.hasClass('opened')) {
            backdrop.find('.modal').slideUp(400, function () {
                var form = backdrop.find('.modal').find('form');
                if (form.length > 0)
                    form[0].reset();

                backdrop.removeClass('opened').fadeOut(300);
                $('html, body').css('overflow', 'inherit');
            });
        }
    }
}

$(function () {
    MODALS.init();
});