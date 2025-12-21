var _formModule = (function ($) {

    var settings = {};

    function init(config) {
        $.extend(settings, config);

        if (settings.action === 'Edit') {
            $('#SiteCode').prop('readonly', true);

            setDisableButton();

            displayImageType(settings.imageSource, "banner");
            displayImageType(settings.imageLogoSource, "logo");
        }

        if (settings.action === 'Create') {
            filterPaymentMethods();
            $('#AllowClearingAgentMode').change(filterPaymentMethods);
        }

        configureInTouchAccountInformationVisibility();

        configureCinetPayAccountInformationVisibility();

        configureSquadAccountInformationVisibility();

        setPaymentMethodSelectionChangeEvent();

        setInTouchPaymentMethodChangeEvent();

        setCinetPayPaymentMethodChangeEvent();

        setSquadPaymentMethodChangeEvent();

        bindDeleteLogoButton();

        bindDeleteBannerButton();

        $("#imageUpload").change(function () {
            var isIE8or9 = (navigator.appVersion.indexOf("MSIE 8") != -1 || navigator.appVersion.indexOf("MSIE 9") != -1);
            $("#divError").hide();
            if (!isIE8or9) {
                if (this.files[0].size > 1048576) {
                    $("#divError").show();
                    $('[data-error]').hide();
                    $("#fileSizeExceededError").show();
                    $('#divImgPreview').css("background-image", "");
                    $("#imageUpload").val(null);
                } else {
                    readURL(this, "banner");
                }
            } else {
                readURL(this, "banner");
            }
        });

        $('#imageUploadLogo').change(function () {
            var isIE8or9 = (navigator.appVersion.indexOf("MSIE 8") != -1 || navigator.appVersion.indexOf("MSIE 9") != -1);
            $("#divError").hide();
            if (!isIE8or9) {
                if (this.files[0].size > 163840) {
                    $("#divError").show();
                    $('[data-error]').hide();
                    $("#fileLogoSizeExceededError").show();
                    $('#divImgPreviewLogo').css("background-image", "");
                    $("#imageUploadLogo").val(null);
                } else {
                    readURL(this, "logo");
                }
            } else {
                readURL(this, "logo");
            }
        });

        $('#Actions_6_').click(function () {
            $('#Dn2FaEnabled').attr('disabled', !this.checked)
            if (!$('#Actions_6_').is(':checked')) {
                $('#Dn2FaEnabled').prop('checked', false)
            }
        });

        $("#inTouchAccountConfigFormHeader").click(function () {
            $("#inTouchAccountForm").slideToggle();
        });

        $("#cinetPayAccountConfigFormHeader").click(function () {
            $("#cinetPayAccountForm").slideToggle();
        });

        $("#squadAccountConfigFormHeader").click(function () {
            $("#squadAccountForm").slideToggle();
        });
    }

    $.fn.pVal = function () {
        /*function for IE8 to not consider value of placeholders*/
        var $this = $(this),
            val = $this.eq(0).val();
        if (val == $this.attr('placeholder'))
            return '';
        else
            return val;
    };

    function setDisableButton() {
        if (settings.disabled === "True") {
            $("#confirmEnable").show();
            $("#confirmDisable").hide();

            $("#btnDisable").addClass("btn_verde");

            $("#txtEnable").show();
            $("#txtDisable").hide();
            $("#Disabled").val("False");
        } else {
            $("#confirmEnable").hide();
            $("#confirmDisable").show();


            $("#txtEnable").hide();
            $("#txtDisable").show();
            $("#Disabled").val("True");
        }

        $('#btnDisable').click(function () {
            $("#divConfirm").modal();
            return false;
        });
    }

    function readURL(input, imageType) {
        previewImage(input, imageType);
    }

    function previewImage(input, imageType) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                if (imageType == "banner") {
                    $('#divImgPreview').css("background-image", "url(" + e.target.result + ")");
                } else if (imageType == "logo") {
                    $('#divImgPreviewLogo').css("background-image", "url(" + e.target.result + ")");
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function clearImage() {
        $('#divImgPreview').css("background-image", "");
        $('#divImgPreviewLogo').css("background-image", "");
    }

    function filterPaymentMethods() {
        var roleTypesConfiguration = '';

        if (settings.action === 'Create') {
            roleTypesConfiguration = $('#AllowClearingAgentMode').is(':checked') ? 'clearingagents' : 'default';
        } else {
            roleTypesConfiguration = $('#AllowClearingAgentMode').val() === 'True' ? 'clearingagents' : 'default';
        }

        var $paymentMethods = $('[data-paymentmethod]');

        $paymentMethods.show();

        $paymentMethods.not('[data-role-' + roleTypesConfiguration + ']').hide();

        $paymentMethods.find('input[type="checkbox"]').prop("checked", false);
    }

    function displayImageType(imageSource, imageType) {
        setImagePreview(imageSource, imageType);
    };

    function setImagePreview(imageSource, imageType) {
        if (imageType == "banner") {
            $('#divImgPreview').css("background-image", "url(" + imageSource + "?t=" + new Date().getTime() + ")");
        } else if (imageType == "logo") {
            $('#divImgPreviewLogo').css("background-image", "url(" + imageSource + "?t=" + new Date().getTime() + ")");
        }
    }

    function setImagePreviewIE(imageSource, imageType) {
        if (imageType == "banner") {
            var newPreview = document.getElementById("divImgPreview");
            newPreview.filters.item("DXImageTransform.Microsoft.AlphaImageLoader").src = imageSource;
        } else if (imageType == "logo") {
            var newPreview = document.getElementById("divImgPreviewLogo");
            newPreview.filters.item("DXImageTransform.Microsoft.AlphaImageLoader").src = imageSource;
        }
    }

    function setPaymentMethodSelectionChangeEvent() {
        var paymentMethods = $('[id*="chkPay_"]');

        for (let i = 0; i < paymentMethods.length; ++i) {

            $(paymentMethods[i]).on('change', function () {
                var chkId = event.target.id;
                var changedChk = $('#' + chkId)[0];
                var checked = changedChk.checked;
                if (checked) {
                    $(changedChk).siblings('#settledReasonSection').show();
                } else {
                    $(changedChk).siblings('#settledReasonSection').hide();
                }
            })           
        }
    }

    function setInTouchPaymentMethodChangeEvent() {
        var inTouchCkLst = $('[id*="chkPay_inTouch_"]');

        for (let i = 0; i < inTouchCkLst.length; ++i) {

            $(inTouchCkLst[i]).on('change', inTouchPaymentMethodSelectionChanged);
        }
    }

    function inTouchPaymentMethodSelectionChanged(event) {
        var chkId = event.target.id;
        var changedChk = $("#" + chkId)[0];
        var checked = changedChk.checked;

        if (checked) {
            uncheckOtherSelectedInTouchPaymentMethods(chkId);
        }

        configureInTouchAccountInformationVisibility();
    }

    function uncheckOtherSelectedInTouchPaymentMethods(chkId) {
        var inTouchCkLst = $('[id*="chkPay_inTouch_"]');

        for (let i = 0; i < inTouchCkLst.length; ++i) {
            if (inTouchCkLst[i].id != chkId && inTouchCkLst[i].checked) {
                inTouchCkLst[i].checked = false;
                $(inTouchCkLst[i]).siblings('#settledReasonSection').hide();
            }
        }
    }

    function configureInTouchAccountInformationVisibility() {
        var selectedInTouchPaymentMethodsCount = $('[id*="chkPay_inTouch_"]').filter(":checked").length;

        if (selectedInTouchPaymentMethodsCount > 0) {
            $('#inTouchAccountSection').show();
        }
        else {
            $('#inTouchAccountSection').hide();
        }
    }

    function setCinetPayPaymentMethodChangeEvent() {
        var cinetPayCkLst = $('[id*="chkPay_cinetPay_"]');

        for (let i = 0; i < cinetPayCkLst.length; ++i) {

            $(cinetPayCkLst[i]).on('change', cinetPayPaymentMethodSelectionChanged);
        }
    }

    function cinetPayPaymentMethodSelectionChanged(event) {
        var chkId = event.target.id;
        var changedChk = $("#" + chkId)[0];
        var checked = changedChk.checked;

        configureCinetPayAccountInformationVisibility();
    }

    function configureCinetPayAccountInformationVisibility() {
        var selectedCinetPayPaymentMethodsCount = $('[id*="chkPay_cinetPay_"]').filter(":checked").length;

        if (selectedCinetPayPaymentMethodsCount > 0) {
            $('#cinetPayAccountSection').show();
        }
        else {
            $('#cinetPayAccountSection').hide();
        }
    }

    function setSquadPaymentMethodChangeEvent() {
        var squadCkLst = $('[id*="chkPay_squad_"]');

        for (let i = 0; i < squadCkLst.length; ++i) {

            $(squadCkLst[i]).on('change', squadPaymentMethodSelectionChanged);
        }
    }

    function squadPaymentMethodSelectionChanged(event) {
        var chkId = event.target.id;
        var changedChk = $("#" + chkId)[0];
        var checked = changedChk.checked;

        configureSquadAccountInformationVisibility();
    }

    function configureSquadAccountInformationVisibility() {
        var selectedSquadPaymentMethodsCount = $('[id*="chkPay_squad_"]').filter(":checked").length;

        if (selectedSquadPaymentMethodsCount > 0) {
            $('#squadAccountSection').show();
        }
        else {
            $('#squadAccountSection').hide();
        }
    }

    var bindDeleteLogoButton = function () {
        $('.deleteTerminalLogo').click(function () {
            $("#modalDeleteLogo #confirmation").text($(this).data("confirmation"));
            $('#modalDeleteLogo').modal({
                backdrop: 'static',
                keyboard: true,
            }, 'show');

            $("#confirmationMessage").show();
        });
    };

    var bindDeleteBannerButton = function () {
        $('.deleteBanner').click(function () {
            $("#modalDeleteBanner #confirmation").text($(this).data("confirmation"));
            $('#modalDeleteBanner').modal({
                backdrop: 'static',
                keyboard: true,
            }, 'show');

            $("#confirmationMessage").show();
        });
    };

    $('#deleteLogo').click(function () {
        $('#modalDeleteLogo').modal('hide');
        $('#divImgPreviewLogo').css("background-image", "");
        $('#btnDeleteLogo').css("display", "none");
    });

    $('#deleteBanner').click(function () {
        $('#modalDeleteBanner').modal('hide');
        $('#divImgPreview').css("background-image", "");
        $('#btnDeleteBanner').css("display", "none");
    });

    //account request form visibility
    $('[id*="Documents_20_"]').on('change', function () {
        var isChecked = $(this).is(":checked");

        if (isChecked) {
            $('#accountRequestFormInputFile').show();
        }
        else {
            $('#accountRequestFormInputFile').hide();
        }
    });

    return {
        init: init
    };
})(jQuery);