!function() {
    "use strict";
    function resizeIframe(el) {
        var $el = $(el), parentWidth = $el.parent().width();
        window.self !== window.top && (parentWidth = parent.innerWidth);
        var trueHeight = $el.data("true-height") || 360, newHeight = parentWidth / ($el.data("true-width") || 640) * trueHeight;
        $el.css("height", newHeight + "px").css("width", parentWidth + "px");
    }
    function connectionMetabox(connection, generalType) {
        for (var toType, toClusters = connection.to, i = toClusters.length - 1; 0 <= i; i--) {
            toType = toClusters[i];
            var specificType = connection.from + "_to_" + toType, $box = $("[data-p2p_type=" + specificType + "].p2p-box"), $connectionTabPanel = $("#fm-pedestal_" + generalType + "_connections-0-" + toType + "-0-tab .fm-group-inner");
            0 !== $box.length && 0 !== $connectionTabPanel.length && ($connectionTabPanel.append($box.parent().html()), 
            $box.closest(".postbox").remove());
        }
    }
    function filterHierarchicalTerms() {
        var $boxes = $(".categorydiv");
        if (!($boxes.length < 1)) {
            jQuery.expr[":"].contains = function(a, i, m) {
                var haystack = (a.textContent || a.innerText || "").toUpperCase(), needle = m[3].toUpperCase();
                return 0 <= haystack.indexOf(needle);
            };
            var $filterInputElement = $('<input type="search" />').addClass("categorydiv-filter").css("width", "100%");
            $boxes.each(function(index, box) {
                var $box = $(box);
                if (!($box.find(".categorychecklist li").length < 10)) {
                    var boxTitle = $box.parent().siblings(".hndle").text();
                    $filterInputElement.attr("placeholder", "Filter " + boxTitle), $box.prepend($filterInputElement);
                }
            }), $("#post").on("keyup", ".categorydiv-filter", function() {
                var $this = $(this), $checklists = $this.parent().find(".categorychecklist li");
                $this.val().length < 2 ? $checklists.show() : $checklists.hide().find(".selectit:contains(" + $this.val() + ")").each(function(index, label) {
                    $(label).parent().show();
                });
            }).on("keydown", ".categorydiv-filter", function(e) {
                if (13 == e.keyCode) return e.preventDefault(), !1;
            }).on("click", ".categorydiv-filter", function() {
                var $this = $(this);
                setTimeout(function() {
                    $this.trigger("keyup");
                }, 100, $this);
            });
        }
    }
    function _slicedToArray(arr, i) {
        return function(arr) {
            if (Array.isArray(arr)) return arr;
        }(arr) || function(arr, i) {
            var _arr = [], _n = !0, _d = !1, _e = void 0;
            try {
                for (var _s, _i = arr[Symbol.iterator](); !(_n = (_s = _i.next()).done) && (_arr.push(_s.value), 
                !i || _arr.length !== i); _n = !0) ;
            } catch (err) {
                _d = !0, _e = err;
            } finally {
                try {
                    _n || null == _i.return || _i.return();
                } finally {
                    if (_d) throw _e;
                }
            }
            return _arr;
        }(arr, i) || function() {
            throw new TypeError("Invalid attempt to destructure non-iterable instance");
        }();
    }
    var domainBoxes = {
        instagram: {
            domainSubstring: "instagr",
            selector: "#fm_meta_box_daily_insta_date"
        },
        twitter: {
            domainSubstring: "twitter.com",
            selector: "#fm_meta_box_embed_options"
        }
    }, handleEmbedURLChange = function(e) {
        for (var url = $(e.target).val(), _arr = Object.entries(domainBoxes), _i = 0; _i < _arr.length; _i++) {
            var _arr$_i = _slicedToArray(_arr[_i], 2), key = _arr$_i[0], val = _arr$_i[1], $el = $(val.selector);
            url.includes(val.domainSubstring) ? $el.show() : $el.hide(), "instagram" == key && fm.datepicker.add_datepicker(e);
        }
    };
    function handleEventUI() {
        $("#fm-event_details-0-all_day-0").on("change", function() {
            var timeSelectors = "".concat(".fm-start_time-wrapper .fm-datepicker-time-wrapper", ", ").concat(".fm-end_time-wrapper .fm-datepicker-time-wrapper");
            $(timeSelectors).toggle(!this.checked);
        }).change();
        $("#fm-event_details-0-start_time-0, #fm-event_details-0-end_time-0").on("change keyup copy paste cut", function() {
            var $this = $(this);
            0 === $this.val().length && $this.closest(".fm-item").find(".fm-datepicker-time").val("");
        });
    }
    var requirePostTitle = function(e) {
        var $title = $("#title");
        0 !== $title.length && $title.val().length < 1 && ($('\n      <div class="notice notice-error">\n        <p>A headline is required!</p>\n      </div>\n    ').insertAfter(".wp-header-end"), 
        $title.focus(), e.preventDefault());
    };
    function summaryButtons() {
        var $summary = $("#fm-homepage_settings-0-summary-0");
        $summary.length < 1 || ($(".js-pedestal-summary-copy-subhead").on("click", function() {
            var subhead = $("textarea#excerpt").val();
            "" !== subhead && $summary.val(subhead);
        }), $(".js-pedestal-summary-copy-first-graf").on("click", function() {
            var contentHTML = tinyMCE.get("content").getContent(), $normalGrafs = $(contentHTML).filter("p").not(function() {
                return this.innerHTML.match(/\[([^\s\]]+)([^\]]+)?\]([^[]*)?(\[\/(\S+?)\])?/);
            });
            if (!($normalGrafs.length <= 0)) {
                var graf = $normalGrafs.first().html();
                $summary.val(graf);
            }
        }));
    }
    !function($) {
        var PedestalAdmin = {
            init: function() {
                var _this = this;
                for (var k in this.clusterMap = {
                    stories: "pedestal_story",
                    topics: "pedestal_topic",
                    people: "pedestal_person",
                    organizations: "pedestal_org",
                    places: "pedestal_place",
                    localities: "pedestal_locality"
                }, this.connections = {
                    entities_to_clusters: {
                        from: "entities",
                        to: [ "stories", "topics", "people", "organizations", "places", "localities" ]
                    },
                    stories_to_clusters: {
                        from: "stories",
                        to: [ "topics", "people", "organizations", "places", "localities" ]
                    }
                }, this.siteURL = window.location.protocol + "//" + window.location.hostname, this.connections) connectionMetabox(this.connections[k], k);
                setTimeout(function() {
                    $(".p2p-toggle-tabs a").click().hide();
                }, 1500), $(".post-type-pedestal_embed #fm-embed_url-0").on("blur", handleEmbedURLChange).blur(), 
                $(document).on("click", "#publish", requirePostTitle), filterHierarchicalTerms(), 
                handleEventUI(), summaryButtons(), $(window).on("resize", function(fn) {
                    var timeout, wait = 1 < arguments.length && void 0 !== arguments[1] ? arguments[1] : 300, immediate = 2 < arguments.length && void 0 !== arguments[2] && arguments[2];
                    return function() {
                        var _this = this, _arguments = arguments, functionCall = function() {
                            return fn.apply(_this, _arguments);
                        }, callNow = immediate && !timeout;
                        clearTimeout(timeout), timeout = setTimeout(functionCall, wait), callNow && functionCall();
                    };
                }(function() {
                    return _this.responsiveIframes();
                })), this.responsiveIframes(), this.disableDraggingDistributionMetaboxes();
            },
            disableDraggingDistributionMetaboxes: function() {
                var $distSection = $("#distribution-sortables");
                $distSection.sortable({
                    disabled: !0
                }), $distSection.find(".postbox .hndle").css("cursor", "pointer");
            },
            responsiveIframes: function() {
                $(".pedestal-responsive, .js-responsive-iframe").each(function(i, el) {
                    return resizeIframe(el);
                });
            }
        };
        $(document).ready(function() {
            PedestalAdmin.init();
        });
    }(jQuery);
}();