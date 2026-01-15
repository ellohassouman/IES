(function ($) {
    $(function () {
        $('[data-toggle-row]').hide();

        $('[data-toggle-button]').click(function () {
            var id = $(this).data('toggle-id');            

            var row = $('[data-toggle-row][data-toggle-id="' + id + '"]');

            if (row.length === 0) {
                loadRow(id);
            }

            if (row.css('display') === 'none') {
                row.show();
            } else {
                row.hide();
            }
            $(this).toggleClass('rowActive')
                .find('.viewMoreBtn')
                .find('.fas')
                .toggleClass('fa-caret-up fa-caret-down');
        });
    });

    function loadRow(id) {
        $.blockScreen();

        $.get('Invoices', { cartId: id })
            .done(function (rows) {

                $('[data-toggle-button][data-toggle-id="' + id + '"]')
                    .closest('tr')
                    .after(rows);
            }).always(function () {
                $.unblockScreen();
            });
    }
})(jQuery);