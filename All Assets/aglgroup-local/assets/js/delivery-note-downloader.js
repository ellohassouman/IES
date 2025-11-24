
function getZippedDeliveryNotes(YINumbers, URL, URLParamName, successCallback, errorCallback) {
    if (YINumbers.length != 0) {
        var promises = YINumbers.map((number) => { return getDeliveryNote(number, URL, URLParamName) });

        Promise.all(promises).then((results) => {
            successCallback(results);
        }).catch(function () {
            errorCallback();
        });

    }
}

function getDeliveryNote(number,URL, URLParamName){
    return new Promise(function (resolve, reject) {

        URL = URL.replace(URLParamName, number);
        var xhr = new XMLHttpRequest();

        xhr.open('GET', URL, true);

        xhr.responseType = 'arraybuffer';
        xhr.onload = function () {
            if (this.status === 200) {
                var array = this.response;
                var filename = "";
                var disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    var matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
                }
                if (filename) {
                    var file = {
                        fileName: filename,
                        arrayBuffer: array
                    }
                    resolve(file);
                }
                else {
                    reject();
                }
            }
        }
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.send();
    });
}