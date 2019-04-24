!function() {
    "use strict";
    jQuery(document).ready(function($) {
        $("body").on("change", ".fm-autocomplete-hidden", function() {
            var $this = $(this), postId = parseInt($this.val()), $textarea = $this.closest(".fm-group-inner").find(".fm-description textarea");
            if (postId) {
                var data = {
                    post_id: postId,
                    action: "pedestal-featured-entities-placeholder"
                };
                $.post(ajaxurl, data, function(response) {
                    response.data && $textarea.attr("placeholder", response.data);
                });
            } else $textarea.attr("placeholder", "");
        });
    });
}();