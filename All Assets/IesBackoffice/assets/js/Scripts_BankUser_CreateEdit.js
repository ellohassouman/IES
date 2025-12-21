(function ($) {
    $('#bankUserForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function (result) {
                if (result.success) {
                    $('#dialogDiv').modal('hide');
                    location.reload();
                }
                else {
                    $('#dialogContent').html(result);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                if (xhr.status === 403) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.isRedirect) {
                        window.location.href = response.redirectUrl;
                    }
                } else {
                    $('#dialogContent').html(xhr.responseText);
                }
            }
        });
    });
})(jQuery);