!function() {
    "use strict";
    function changeElems(tagName, attr, newHostName) {
        for (var elems = document.getElementsByTagName(tagName), i = 0; i < elems.length; i++) {
            var elem = elems[i], attrVal = elem.getAttribute(attr);
            if (attrVal) {
                var newAttrVal = attrVal.replace(/a\.spirited\.media/gi, newHostName);
                attrVal !== newAttrVal && elem.setAttribute(attr, newAttrVal);
            }
        }
    }
    var computedFontFamily = window.getComputedStyle(document.body).getPropertyValue("font-family");
    if (-1 === computedFontFamily.indexOf("Overpass") && -1 === computedFontFamily.indexOf("Merriweather")) {
        var newHostName = window.location.host;
        changeElems("link", "href", newHostName), changeElems("img", "srcset", "d9nsjsuh3e2lm.cloudfront.net"), 
        changeElems("img", "src", "d9nsjsuh3e2lm.cloudfront.net"), changeElems("script", "src", newHostName), 
        "function" == typeof ga && ga("send", "event", "Error", "", "CDN failed to load");
    }
}();