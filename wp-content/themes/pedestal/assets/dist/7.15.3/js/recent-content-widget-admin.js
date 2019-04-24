!function() {
    "use strict";
    jQuery(document).ready(function($) {
        var autocompleteCache = {}, autocompleteArgs = {
            minLength: 2,
            source: function(request, response) {
                var term = request.term;
                if (term in autocompleteCache) response(autocompleteCache[term]); else {
                    var data = {
                        action: "recent-content-widget-cluster-autocomplete",
                        term: term
                    };
                    $.post(ajaxurl, data, function(ajaxResponse) {
                        autocompleteCache[term] = ajaxResponse.data, response(ajaxResponse.data);
                    });
                }
            },
            select: function(e, ui) {
                $(".js-recent-content-widget-clusters").append(ui.item.selected_item), $(this).val(""), 
                e.preventDefault();
            }
        };
        $("#widgets-right").on("change", ".js-recent-content-widget-filter-trigger", function() {
            var $this = $(this), $parentElem = $this.parents("fieldset"), $filterElem = $parentElem.find(".js-recent-content-widget-filter"), $clusterElem = $parentElem.find(".js-recent-content-widget-clusters");
            1 == $this.val() ? ($filterElem.show(), $clusterElem.show()) : ($filterElem.hide(), 
            $clusterElem.hide().html(""));
        }).on("click", ".js-recent-content-widget-remove-cluster", function(e) {
            $(this).parent().remove(), e.preventDefault();
        }), $(".js-cluster-autocomplete").autocomplete(autocompleteArgs), $(document).on("widget-added widget-updated", function(e, widget) {
            $(widget).find(".js-cluster-autocomplete").autocomplete(autocompleteArgs);
        });
    });
}();