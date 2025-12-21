Required = function(value, errorDiv, message) {
    if (value != null && value != "") {
        return true;
    } else {
        ShowErrorMessage(errorDiv, message);
        return false;
    }
}

CheckRegEx = function (value, regEx) {
    return regEx.test(value);
}