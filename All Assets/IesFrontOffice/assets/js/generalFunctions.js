function goBack() {
    window.history.back()
}
function clearInputs(data) {
    $(data).val('');
}

function getCookie(c_name) {
    var i, x, y, ARRcookies = document.cookie.split(";");
    for (i = 0; i < ARRcookies.length; i++) {
        x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
        y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
        x = x.replace(/^\s+|\s+$/g, "");
        if (x == c_name) {
            return unescape(y);
        }
    }
}

function GetURLRegex() {
    return /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
}

function ShowErrorMessage(div, message) {
    $("html body").scrollTop(0);
    var modal = $("div [role='dialog']");
    if (modal != null) {
        modal.scrollTop(0);
    }
    div.show();
    $("#" + div.attr("id") + " #message").text(message);
}

function HideErrorMessage(div) {
    $("#" + div.attr("id") + " #message").text("");
    div.hide();
}

function disableSubmitButton(classButton) {
    $(document).submit(function () {
        $('.' + classButton).prop('disabled', true);
    });
}

function pollDownloadState() {
    var cookieName = "ActionComplete";
    if (Cookies.get(cookieName) === undefined) {
        setTimeout(function () {
            pollDownloadState(cookieName);
        }, 200);
        return;
    }
    Cookies.remove(cookieName);
    $.unblockScreen();
}

     (function ($) {
         $.support.placeholder = ('placeholder' in document.createElement('input'));
     })(jQuery);


     //fix for IE7 and IE8
     $(function () {
         if (!$.support.placeholder) {
             $("[placeholder]").focus(function () {
                 if ($(this).val() == $(this).attr("placeholder")) $(this).val("");
             }).blur(function () {
                 if ($(this).val() == "") $(this).val($(this).attr("placeholder"));
             }).blur();

             $("[placeholder]").parents("form").submit(function () {
                 $(this).find('[placeholder]').each(function() {
                     if ($(this).val() == $(this).attr("placeholder")) {
                         $(this).val("");
                     }
                 });
             });
         }
     });


         WebFontConfig = {
             google: { families: ['Nunito Sans:400,700:latin'] }
         };
     (function () {
         var wf = document.createElement('script');
         wf.src = ('https:' == document.location.protocol ? 'https' : 'http') +
           '://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
         wf.type = 'text/javascript';
         wf.async = 'true';
         var s = document.getElementsByTagName('script')[0];
         s.parentNode.insertBefore(wf, s);
     })();

     jQuery.extend({
         blockScreen: function () {
             var overlayId = "overlay";
             if ($("div#" + overlayId).length == 0) {
                 jQuery('body').spin({
                     lines: 12, // The number of lines to draw
                     length: 20, // The length of each line
                     width: 5, // The line thickness
                     radius: 25, // The radius of the inner circle
                     shadow: false, // Whether to render a shadow
                     position: 'fixed'
                 }).append(jQuery("<div>", {
                     id: overlayId
                 }));
             }
         },
         unblockScreen: function () {
             jQuery('body').spin(false).find("#overlay").remove();
         },
         blockScreenWhileDownloading: function () {
             $.blockScreen();
             setTimeout(pollDownloadState, 200);
         }
     });