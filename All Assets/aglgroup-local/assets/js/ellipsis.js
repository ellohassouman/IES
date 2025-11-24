(function ($) {
    $(function () {
        $('[data-ellipsis-target]').each(function () {
            $(this).html(function () {
                return $('<i>', { class: 'fa fa-chevron-circle-down' });
            });

            var target = $(this).data('ellipsis-target');
            var $content = $('#' + target);

            $(this).addClass('hidden-sm hidden-md hidden-lg');
            $content.addClass('hidden-xs');
        });
    });

    $('[data-ellipsis-target]').click(function () {
        var target = $(this).data('ellipsis-target');

        $(this).find('i').toggleClass('fa-chevron-circle-down fa-chevron-circle-up');

        $('#' + target).toggleClass('hidden-xs');
    });
})(jQuery);