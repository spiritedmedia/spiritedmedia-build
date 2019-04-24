!function() {
    "use strict";
    var googletag = window.googletag || {};
    googletag.cmd = googletag.cmd || [], googletag.cmd.push(function() {
        googletag.pubads().addEventListener("slotRenderEnded", function() {
            jQuery(document).ready(function($) {
                var themeColor = $('meta[name="theme-color"]').attr("content");
                (themeColor = themeColor.replace("#", "")) || (themeColor = "ccc"), $(".js-dfp").each(function(index, ad) {
                    for (var $ad = $(ad), adSizes = $ad.data("dfp-sizes").split(","), adName = $ad.data("dfp-name"), newHTML = "", i = 0; i < adSizes.length; i++) {
                        var adSize = adSizes[i], adWidth = adSize.split("x")[0], adHeight = adSize.split("x")[1], imgHTML = '<img src="'.concat("https://dummyimage.com/" + adSize + "/" + themeColor + "/fff/.png", '">'), displayVal = "none";
                        0 === i && (displayVal = "block;");
                        var counterText = "";
                        1 < adSizes.length && (counterText = i + 1 + "/" + adSizes.length);
                        var $placeholder = $("<div></div>").css({
                            width: adWidth + "px",
                            height: adHeight + "px",
                            marginLeft: "auto",
                            marginRight: "auto",
                            position: "relative",
                            display: displayVal
                        });
                        $placeholder.append($('<a href="#" class="js-dfp-placeholder">' + imgHTML + "</a>"), $("<p>" + adName + "</p>").css({
                            position: "absolute",
                            top: "5px",
                            right: "5px",
                            margin: 0,
                            padding: 0,
                            color: "#fff",
                            fontSize: "10px"
                        }), $('<p class="">' + counterText + "</p>").css({
                            position: "absolute",
                            top: "18px",
                            right: "5px",
                            margin: 0,
                            padding: 0,
                            color: "#fff",
                            fontSize: "10px"
                        })), newHTML += $placeholder[0].outerHTML;
                    }
                    $ad.html(newHTML).show();
                }).on("click", ".js-dfp-placeholder", function(e) {
                    e.preventDefault();
                    var $this = $(this), $children = $this.parents(".js-dfp").children(), numOfSizes = $children.length;
                    if (!(numOfSizes < 2)) {
                        var nextIndex = $this.parent().index() + 1;
                        numOfSizes - 1 < nextIndex && (nextIndex = 0), $children.hide(), $children.eq(nextIndex).show();
                    }
                });
            });
        });
    }), window.googletag = googletag;
}();