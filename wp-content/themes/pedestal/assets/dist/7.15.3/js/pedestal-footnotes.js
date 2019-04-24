!function() {
    "use strict";
    jQuery(document).ready(function($) {
        $(".js-main").on("click", ".js-footnote-link", function(e) {
            var $entityShareBar = $(".js-entity-share.fixed"), targetID = this.href.split("#")[1], $target = $("#" + targetID), offsetPadding = 0;
            0 < $entityShareBar.length && (offsetPadding = $entityShareBar.height());
            var offset = $target.offset().top - offsetPadding, duration = function(offset) {
                return Math.abs($(document.body).scrollTop() - offset) / 1200 * 1e3;
            }(offset);
            $("html, body").animate({
                scrollTop: offset
            }, duration), e.preventDefault(), $target.attr("tabindex", 0).focus();
        });
    });
}();