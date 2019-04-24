!function() {
    "use strict";
    ga("create", PedestalGAData.id, "auto", {
        allowLinker: !0
    }), ga("require", "displayfeatures"), PedestalGAData.optimizeID && ga("require", PedestalGAData.optimizeID), 
    ga("require", "linker"), ga("linker:autoLink", [ "checkout.fundjournalism.org" ]), 
    ga(function(tracker) {
        var contactEmailDimension = "Unknown", memberLevelDimension = "Unknown", contactData = localStorageCookie("contactData");
        if (contactData && "data" in contactData) {
            var data = contactData.data;
            data.subscribed_to_list && (contactEmailDimension = "Other"), data.newsletter_subscriber && (contactEmailDimension = "Daily Newsletter"), 
            data.current_member || data.donate_365 || (memberLevelDimension = "None"), !data.current_member && data.donate_365 && (memberLevelDimension = "Donor"), 
            data.current_member && (memberLevelDimension = "Error"), data.current_member && 0 < data.member_level && (memberLevelDimension = "Member " + data.member_level);
        }
        tracker.set("dimension1", contactEmailDimension), tracker.set("dimension2", memberLevelDimension);
        var contactFrequency = "0 posts", contactHistory = localStorageCookie("contactHistory");
        if (contactHistory) {
            var postCount = contactHistory.filter(function(item) {
                return "/20" === item.u.slice(0, 3);
            }).length;
            1 == postCount ? contactFrequency = "1 post" : 2 <= postCount && postCount < 4 ? contactFrequency = "2-3 posts" : 4 <= postCount && postCount < 6 ? contactFrequency = "4-5 posts" : 6 <= postCount && postCount < 9 ? contactFrequency = "6-8 posts" : 9 <= postCount && postCount < 14 ? contactFrequency = "9-13 posts" : 14 <= postCount && postCount < 22 ? contactFrequency = "14-21 posts" : 22 <= postCount && postCount < 35 ? contactFrequency = "22-34 posts" : 35 <= postCount && postCount < 56 ? contactFrequency = "35-55 posts" : 56 <= postCount && (contactFrequency = "56+");
        }
        tracker.set("dimension3", contactFrequency);
        var adblockerDimension = "Unknown", contactAdblocker = localStorageCookie("contactAdblocker");
        "boolean" == typeof contactAdblocker && (adblockerDimension = contactAdblocker ? "Detected" : "Not detected"), 
        tracker.set("dimension4", adblockerDimension), window.gaLinkTrackerParam = tracker.get("linkerParam");
    }), ga("send", "pageview");
}();