var fieldSettingsConfig = (function ($) {

    var settings = {
        urlGetFieldSettings: '',
        urlSaveFieldSettings:'',
    };

    var fieldSettings = [];
    var currentSiteId = null;
    var currentSiteName = "";

    function init(config) {
        if (config) {
            $.extend(settings, config);
        }

        initChkFieldSettingChangeEvent();
    }

    function loadAlertFieldSetting() {
        $('#alertFieldSettingsContent').load('_AlertFieldSettings');
    }

    function cleanAlertFieldSetting() {
        $('#alertFieldSettingsContent').empty();
    }
    function initChkFieldSettingChangeEvent() {
        $(document).on('change', '.chkFieldSettings', function () {
            var htmlId = this.id;
            var id = parseInt(htmlId.substr(this.id.indexOf("-") + 1));

            fieldSettings = $.grep(fieldSettings, function (data) {
                return data.Id != id
            });

            var fieldSetting = { Id: id, Visible: this.checked };
            fieldSettings.push(fieldSetting);
            setGroupColumnState(this);
        });
    }

    function chkGroupChanged(groupId) {
        var groupChecked = $("#" + groupId).prop('checked');
        var className = groupId.replace('chkGr-', 'chkFs-');

        $("." + className).each(function (index, data) {
            if (!data.disabled) {
                $("#" + data.id).prop('checked', groupChecked).change();
            }
        });
    }

    function setGroupColumnState(element) {
        var className = element.classList[element.classList.length - 1];
        var allChecked = false;

        $("." + className).each(function (index, data) {
            allChecked = data.disabled || data.checked;
            return allChecked;
        });

        var chkGroupId = className.replace('chkFs-', 'chkGr-');
        $("#" + chkGroupId).prop('checked', allChecked);
    }

    function getFieldSettings(siteId, siteName) {
        $.blockScreen();

         $.ajax({
             url: settings.urlGetFieldSettings+"?siteId="+siteId,
             type: "GET",
             cache: false,
             success: function (data) {
                setStyleSiteButtons(siteId);
                $('#fieldSettingsContainer').html(data);
                $("body").find('#siteNameLabel').html(siteName);
                $('#sitesDataList').val('');
                cleanAlertFieldSetting();

                currentSiteName = siteName;
                currentSiteId = siteId;
             },
             error: function(response) {
                 loadAlertFieldSetting();
             }
         }).always(function () {
             $.unblockScreen();
         });
    }

    function setStyleSiteButtons(siteId) {
        $('#sitelist li a').each(function () {
            $(this).removeClass("active");
        });

        if (siteId != null) {
            $("#lnkSite-" + siteId).addClass('active');
        }
    }

    function changeFieldSettingsVisibility(tdElementId, group) {
        var tr = $("#" + tdElementId).parent("tr");

        var tableId = tdElementId.replace('td-', 'table-');
        var table = $("#" + tableId);

        if (tr.hasClass("shown")) {
            tr.removeClass("shown");
            table.addClass("d-none");
        } else {
            tr.addClass("shown");
            table.removeClass("d-none");
        }
    }

    function save() {
       $.blockScreen();

        var token = $('input[name="__RequestVerificationToken"]').val();

        $.ajax({
            url: settings.urlSaveFieldSettings,
            type: "POST",
            data: { __RequestVerificationToken: token, 'fieldSettingsViewModel': fieldSettings, 'currentSiteId': currentSiteId },
            cache:false,
            success: function (data) {
                fieldSettings = [];
                $('#fieldSettingsContainer').html(data);
                $("body").find('#siteNameLabel').html(currentSiteName);
                loadAlertFieldSetting();
            }
        }).always(function () {
            $.unblockScreen();
        });
    }

    function cancel() {
        currentSiteId = null;
        fieldSettings = [];
        $('#fieldSettingsContainer').empty();
        setStyleSiteButtons(null);
    }

    return {
        init: init,
        getFieldSettings: getFieldSettings,
        changeFieldSettingsVisibility: changeFieldSettingsVisibility,
        chkGroupChanged: chkGroupChanged,
        save: save,
        cancel: cancel
    };

}(jQuery));