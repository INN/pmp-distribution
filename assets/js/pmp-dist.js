(function() {
  var $ = jQuery,
      pmpdistsubmit = $('#pmp_distributor_options_meta');

    var DistAsyncMenu = PMP.AsyncMenu.extend({
      action: 'pmp_dist_async_menu_options'
    });

    $(document).ready(function() {
        var menus = $('[data-pmp-dist-option-type]');

        if ($('#pmp-dist-options').length > 0) {
            menus.each(function(idx, el) {
                var type = $(el).data('pmp-dist-option-type');

                var menu = new DistAsyncMenu({
                    type: type,
                    el: $(el),
                    template: '#pmp-async-select-tmpl',
                    multiSelect: true
                });
            });
        }
    });

    pmpdistsubmit.find(':button, :submit').on('click.edit-post', function(event) {
        var $button = $(this);

        if ($button.hasClass('disabled')) {
            event.preventDefault();
            return;
        }

        if ($button.hasClass('submitdelete') || $button.is('#post-preview'))
            return;

        $('form#post').off('submit.edit-post').on('submit.edit-post', function(event) {
            if (event.isDefaultPrevented())
                return;

            // Stop autosave
            if (wp.autosave)
                wp.autosave.server.suspend();

            $(window).off('beforeunload.edit-post');
            $button.addClass('disabled');
        });
    });
})();
