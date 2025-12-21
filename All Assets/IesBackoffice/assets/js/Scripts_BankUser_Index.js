(function ($) {
    $(function () {
        var grdAppAdminsList = $('#grdAppAdminsList').DataTable({
            "oLanguage": currentLang,
            "aoColumnDefs": [{ "bSortable": false, "aTargets": [3, 4] },
                             { "sWidth": "28%", "aTargets": [0, 1, 2] }],
            layout: {
                topStart: null,
                topEnd: 'pageLength'
            },
            orderCellsTop: false,
            fixedHeader: false
        });

        $('#grdAppAdminsList thead tr:eq(0) .text-search').each(function (i) {
            var title = $(this).text();
            $(this).html('<input type="text" class="filter-input" placeholder="' + title + '" />');

            $('input', this).on('keyup change', function () {
                if (grdAppAdminsList.column(i).search() !== this.value) {
                    grdAppAdminsList
                        .column(i)
                        .search(this.value)
                        .draw();
                }
            });
        });
    });

    $('#btnNewUser, [data-button="edit"]').click(function () {
        $('#dialogContent').load(this.href, function () {
            $('#dialogDiv').modal({
                backdrop: 'static',
                keyboard: true
            }, 'show');
        });
        return false;
    });

    $('[data-button="delete"]').click(function () {
        $("#modalAppAdminDelete #confirmation").text($(this).data("confirmation"));
        $("#modalAppAdminDelete #id").val($(this).data("accountid"));
        $('#modalAppAdminDelete').modal({
            backdrop: 'static',
            keyboard: true
        }, 'show');

        $("#confirmationMessage").show();
    });

    $('#deleteForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function (data) {
                location.reload();
            },
            error: function (xhr, ajaxOptions, thrownError) {
                if (xhr.status === 403) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.isRedirect) {
                        window.location.href = response.redirectUrl;
                    }
                } 
            }
        });
    });
})(jQuery);