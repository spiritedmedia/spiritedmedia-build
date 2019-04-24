!function() {
    "use strict";
    function handleSlotRenderEnded(e) {
        if (!e.isEmpty) {
            var id = e.slot.getSlotElementId(), adElem = document.getElementById(id);
            if (adElem && null === adElem.querySelector(".js-dfp-disclaimer")) {
                adElem.insertAdjacentHTML("afterbegin", '<div class="dfp-disclaimer js-dfp-disclaimer">ADVERTISEMENT</div>');
            }
        }
    }
    var googletag = window.googletag || {};
    googletag.cmd = googletag.cmd || [], function() {
        var gads = document.createElement("script");
        gads.async = !0, gads.type = "text/javascript", gads.src = "https://www.googletagservices.com/tag/js/gpt.js";
        var node = document.getElementsByTagName("script")[0];
        node.parentNode.insertBefore(gads, node);
    }();
    var DFP_SCRIPT = document.getElementById("dfp-load"), DFP_PATH = DFP_SCRIPT.getAttribute("data-dfp-path"), DFP_SITE = DFP_SCRIPT.getAttribute("data-dfp-site"), DFP_ARTICLE_ID = DFP_SCRIPT.getAttribute("data-dfp-article-id"), DFP_TOPICS = DFP_SCRIPT.getAttribute("data-dfp-topics");
    jQuery(function($) {
        var screenSize = "desktop";
        "none" === $(".js-rail").css("display") && (screenSize = "mobile"), $(".js-dfp").each(function(elIndex, el) {
            var $el = $(el);
            if ("none" != $el.css("display")) {
                var rawSize = $el.data("dfp-sizes"), slotName = $el.data("dfp-name"), uniqueId = $el.data("dfp-unique"), slotTarget = $el.data("dfp-slottarget");
                if (!slotTarget && uniqueId && (slotTarget = uniqueId), rawSize && slotName) {
                    var sizes = [];
                    $.each(rawSize.split(","), function(sizeIndex, item) {
                        if (item = $.trim(item)) {
                            var dimensions = item.split("x");
                            if (2 === dimensions.length) {
                                for (var i = 0; i < dimensions.length; i++) dimensions[i] = parseInt(dimensions[i]);
                                sizes.push(dimensions);
                            } else {
                                console.warn("Pedestal DFP: Bad dimensions!", item, el);
                            }
                        }
                    }), "mobile" == screenSize && (slotTarget = slotTarget.replace(/artclbox/gi, "m"));
                    var path = "/" + DFP_PATH, id = DFP_SITE + "-" + screenSize + "-" + slotTarget;
                    $el.attr("id", id), googletag.cmd.push(function() {
                        googletag.defineSlot(path, sizes, id).setTargeting("slot", [ slotTarget ]).addService(googletag.pubads()), 
                        googletag.display(id);
                    });
                } else {
                    console.warn("Pedestal DFP: Slot missing required parameters", el);
                }
            }
        });
    }), googletag.cmd.push(function() {
        googletag.pubads().enableSingleRequest(), googletag.pubads().collapseEmptyDivs(!0), 
        googletag.pubads().addEventListener("slotRenderEnded", handleSlotRenderEnded), DFP_ARTICLE_ID && googletag.pubads().setTargeting("sm_article", [ DFP_ARTICLE_ID ]), 
        DFP_TOPICS && googletag.pubads().setTargeting("sm_topic", DFP_TOPICS.split(" ")), 
        googletag.enableServices();
    }), window.googletag = googletag;
}();