!function() {
    "use strict";
    jQuery(document).ready(function($) {
        var currentYear = new Date().getFullYear();
        $("input.js-contact-year").val(currentYear), $(".js-contact-year").addClass("hide");
    });
}();