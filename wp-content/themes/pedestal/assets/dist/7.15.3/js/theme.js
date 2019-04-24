!function() {
    "use strict";
    function throttle(callback, limit) {
        var wait = !1;
        return function() {
            wait || (callback.call(), wait = !0, setTimeout(function() {
                wait = !1;
            }, limit));
        };
    }
    function focusAtEnd($elem) {
        if (0 < $elem.length) {
            var elem = $elem[0], elemLen = elem.value.length;
            (elem.selectionStart || "0" == elem.selectionStart) && (elem.selectionStart = elemLen, 
            elem.selectionEnd = elemLen, elem.focus());
        }
    }
    function analytics() {
        function debugEvent(eventCategory, eventAction, eventLabel, eventValue) {
            console.group("Google Analytics Event Data"), console.log("Category: ", eventCategory), 
            console.log("Action: ", eventAction), console.log("Label: ", eventLabel), eventValue && console.log("Value: ", eventValue), 
            console.groupEnd();
        }
        var debugging = !1;
        $("body").is(".js-debug-ga") && (debugging = !0), ("function" == typeof ga || debugging) && ($("body").on("click", "a[data-ga-category]", function(e) {
            var $this = $(this), eventCategory = $this.data("ga-category"), eventAction = $this.data("ga-action") || e.currentTarget.href, eventLabel = $this.data("ga-label"), eventValue = parseInt($this.data("ga-value")) || null;
            if (debugging) return debugEvent(eventCategory, eventAction, eventLabel, eventValue), 
            void e.preventDefault();
            ga("send", "event", eventCategory, eventAction, eventLabel, eventValue);
        }).on("submit", "form[data-ga-category]", function(e) {
            var $this = $(this), eventCategory = $this.data("ga-category"), eventAction = $this.data("ga-action") || $this.attr("action"), eventLabel = $this.data("ga-label"), eventValue = null;
            if ($this.is(".js-donate-form") && ((eventAction = $this.find(".js-donate-form-frequency:checked").val()) || (eventAction = "once"), 
            eventValue = $this.find(".js-donate-form-amount").val()), debugging) return debugEvent(eventCategory, eventAction, eventLabel, eventValue), 
            void e.preventDefault();
            ga("send", "event", eventCategory, eventAction, eventLabel, eventValue);
        }), $(".js-main").on("click", "a", function(e) {
            var $this = $(this), eventCategory = $this.data("ga-category");
            if (!eventCategory) {
                eventCategory = "post-content";
                var eventAction = $this.data("ga-action") || e.currentTarget.href, eventLabel = $this.data("ga-label") || "link", eventValue = parseInt($this.data("ga-value")) || null;
                if (debugging) return debugEvent(eventCategory, eventAction, eventLabel, eventValue), 
                void e.preventDefault();
                ga("send", "event", eventCategory, eventAction, eventLabel, eventValue);
            }
        }), $(".js-rail").on("click", "a", function(e) {
            var $this = $(this), eventCategory = $this.data("ga-category");
            if (!eventCategory) {
                eventCategory = "sidebar";
                var eventAction = $this.data("ga-action") || e.currentTarget.href, eventLabel = $this.data("ga-label") || "link", eventValue = parseInt($this.data("ga-value")) || null;
                if (debugging) return debugEvent(eventCategory, eventAction, eventLabel, eventValue), 
                void e.preventDefault();
                ga("send", "event", eventCategory, eventAction, eventLabel, eventValue);
            }
        }));
    }
    function handleDonateForm() {
        var prevPeriod, $form = $(".js-donate-form"), endpointDomain = $form.data("nrh-endpoint-domain");
        $form.on("change", ".js-donate-form-frequency", function() {
            var endpointPath;
            endpointPath = "" === $(this).val() ? "/donateform" : "/memberform", $form.attr("action", endpointDomain + endpointPath);
        }), $form.on("submit", function() {
            if ("undefined" != typeof gaLinkTrackerParam) {
                var parts = gaLinkTrackerParam.split("=");
                2 === parts.length && $form.append('<input type="hidden" name="'.concat(parts[0], '" value="').concat(parts[1], '">'));
            }
        }), $form.on("change", ".js-donate-form-frequency", function() {
            var $amountInput = $form.find(".js-donate-form-amount"), currentPeriod = $(this).val(), oldAmount = parseInt($amountInput.val()), newAmount = oldAmount;
            "yearly" === currentPeriod && "" !== prevPeriod || "" === currentPeriod && "yearly" !== prevPeriod ? newAmount = 12 * oldAmount : "monthly" === currentPeriod && (newAmount = oldAmount / 12), 
            newAmount = Math.ceil(newAmount), $amountInput.val(newAmount), prevPeriod = currentPeriod;
        });
    }
    !function($, window, document, undefined) {
        var class_array, head;
        class_array = [ "foundation-mq-small", "foundation-mq-small-only", "foundation-mq-medium", "foundation-mq-medium-only", "foundation-mq-large", "foundation-mq-large-only", "foundation-mq-xlarge", "foundation-mq-xlarge-only", "foundation-mq-xxlarge", "foundation-data-attribute-namespace" ], 
        (head = $("head")).prepend($.map(class_array, function(class_name) {
            if (0 === head.has("." + class_name).length) return '<meta class="' + class_name + '" />';
        })), $(function() {
            "undefined" != typeof FastClick && void 0 !== document.body && FastClick.attach(document.body);
        });
        var S = function(selector, context) {
            if ("string" != typeof selector) return $(selector, context);
            if (context) {
                var cont;
                if (context.jquery) {
                    if (!(cont = context[0])) return context;
                } else cont = context;
                return $(cont.querySelectorAll(selector));
            }
            return $(document.querySelectorAll(selector));
        }, attr_name = function(init) {
            var arr = [];
            return init || arr.push("data"), 0 < this.namespace.length && arr.push(this.namespace), 
            arr.push(this.name), arr.join("-");
        }, add_namespace = function(str) {
            for (var parts = str.split("-"), i = parts.length, arr = []; i--; ) 0 !== i ? arr.push(parts[i]) : 0 < this.namespace.length ? arr.push(this.namespace, parts[i]) : arr.push(parts[i]);
            return arr.reverse().join("-");
        }, bindings = function(method, options) {
            var self = this, bind = function() {
                var $this = S(this), should_bind_events = !$this.data(self.attr_name(!0) + "-init");
                $this.data(self.attr_name(!0) + "-init", $.extend({}, self.settings, options || method, self.data_options($this))), 
                should_bind_events && self.events(this);
            };
            if (S(this.scope).is("[" + this.attr_name() + "]") ? bind.call(this.scope) : S("[" + this.attr_name() + "]", this.scope).each(bind), 
            "string" == typeof method) return this[method].call(this, options);
        };
        function MediaQuery(selector) {
            this.selector = selector, this.query = "";
        }
        window.matchMedia || (window.matchMedia = function() {
            var styleMedia = window.styleMedia || window.media;
            if (!styleMedia) {
                var info, style = document.createElement("style"), script = document.getElementsByTagName("script")[0];
                style.type = "text/css", style.id = "matchmediajs-test", script.parentNode.insertBefore(style, script), 
                info = "getComputedStyle" in window && window.getComputedStyle(style, null) || style.currentStyle, 
                styleMedia = {
                    matchMedium: function(media) {
                        var text = "@media " + media + "{ #matchmediajs-test { width: 1px; } }";
                        return style.styleSheet ? style.styleSheet.cssText = text : style.textContent = text, 
                        "1px" === info.width;
                    }
                };
            }
            return function(media) {
                return {
                    matches: styleMedia.matchMedium(media || "all"),
                    media: media || "all"
                };
            };
        }()), function(jQuery) {
            for (var animating, lastTime = 0, vendors = [ "webkit", "moz" ], requestAnimationFrame = window.requestAnimationFrame, cancelAnimationFrame = window.cancelAnimationFrame, jqueryFxAvailable = void 0 !== jQuery.fx; lastTime < vendors.length && !requestAnimationFrame; lastTime++) requestAnimationFrame = window[vendors[lastTime] + "RequestAnimationFrame"], 
            cancelAnimationFrame = cancelAnimationFrame || window[vendors[lastTime] + "CancelAnimationFrame"] || window[vendors[lastTime] + "CancelRequestAnimationFrame"];
            function raf() {
                animating && (requestAnimationFrame(raf), jqueryFxAvailable && jQuery.fx.tick());
            }
            requestAnimationFrame ? (window.requestAnimationFrame = requestAnimationFrame, window.cancelAnimationFrame = cancelAnimationFrame, 
            jqueryFxAvailable && (jQuery.fx.timer = function(timer) {
                timer() && jQuery.timers.push(timer) && !animating && (animating = !0, raf());
            }, jQuery.fx.stop = function() {
                animating = !1;
            })) : (window.requestAnimationFrame = function(callback) {
                var currTime = new Date().getTime(), timeToCall = Math.max(0, 16 - (currTime - lastTime)), id = window.setTimeout(function() {
                    callback(currTime + timeToCall);
                }, timeToCall);
                return lastTime = currTime + timeToCall, id;
            }, window.cancelAnimationFrame = function(id) {
                clearTimeout(id);
            });
        }($), MediaQuery.prototype.toString = function() {
            return this.query || (this.query = S(this.selector).css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g, ""));
        }, window.Foundation = {
            name: "Foundation",
            version: "5.5.3",
            media_queries: {
                small: new MediaQuery(".foundation-mq-small"),
                "small-only": new MediaQuery(".foundation-mq-small-only"),
                medium: new MediaQuery(".foundation-mq-medium"),
                "medium-only": new MediaQuery(".foundation-mq-medium-only"),
                large: new MediaQuery(".foundation-mq-large"),
                "large-only": new MediaQuery(".foundation-mq-large-only"),
                xlarge: new MediaQuery(".foundation-mq-xlarge"),
                "xlarge-only": new MediaQuery(".foundation-mq-xlarge-only"),
                xxlarge: new MediaQuery(".foundation-mq-xxlarge")
            },
            stylesheet: $("<style></style>").appendTo("head")[0].sheet,
            global: {
                namespace: void 0
            },
            init: function(scope, libraries, method, options, response) {
                var args = [ scope, method, options, response ], responses = [];
                if (this.rtl = /rtl/i.test(S("html").attr("dir")), this.scope = scope || this.scope, 
                this.set_namespace(), libraries && "string" == typeof libraries && !/reflow/i.test(libraries)) this.libs.hasOwnProperty(libraries) && responses.push(this.init_lib(libraries, args)); else for (var lib in this.libs) responses.push(this.init_lib(lib, libraries));
                return S(window).load(function() {
                    S(window).trigger("resize.fndtn.clearing").trigger("resize.fndtn.dropdown").trigger("resize.fndtn.equalizer").trigger("resize.fndtn.interchange").trigger("resize.fndtn.joyride").trigger("resize.fndtn.magellan").trigger("resize.fndtn.topbar").trigger("resize.fndtn.slider");
                }), scope;
            },
            init_lib: function(lib, args) {
                return this.libs.hasOwnProperty(lib) ? (this.patch(this.libs[lib]), args && args.hasOwnProperty(lib) ? (void 0 !== this.libs[lib].settings ? $.extend(!0, this.libs[lib].settings, args[lib]) : void 0 !== this.libs[lib].defaults && $.extend(!0, this.libs[lib].defaults, args[lib]), 
                this.libs[lib].init.apply(this.libs[lib], [ this.scope, args[lib] ])) : (args = args instanceof Array ? args : new Array(args), 
                this.libs[lib].init.apply(this.libs[lib], args))) : function() {};
            },
            patch: function(lib) {
                lib.scope = this.scope, lib.namespace = this.global.namespace, lib.rtl = this.rtl, 
                lib.data_options = this.utils.data_options, lib.attr_name = attr_name, lib.add_namespace = add_namespace, 
                lib.bindings = bindings, lib.S = this.utils.S;
            },
            inherit: function(scope, methods) {
                for (var methods_arr = methods.split(" "), i = methods_arr.length; i--; ) this.utils.hasOwnProperty(methods_arr[i]) && (scope[methods_arr[i]] = this.utils[methods_arr[i]]);
            },
            set_namespace: function() {
                var namespace = void 0 === this.global.namespace ? $(".foundation-data-attribute-namespace").css("font-family") : this.global.namespace;
                this.global.namespace = void 0 === namespace || /false/i.test(namespace) ? "" : namespace;
            },
            libs: {},
            utils: {
                S: S,
                throttle: function(func, delay) {
                    var timer = null;
                    return function() {
                        var context = this, args = arguments;
                        null == timer && (timer = setTimeout(function() {
                            func.apply(context, args), timer = null;
                        }, delay));
                    };
                },
                debounce: function(func, delay, immediate) {
                    var timeout, result;
                    return function() {
                        var context = this, args = arguments, callNow = immediate && !timeout;
                        return clearTimeout(timeout), timeout = setTimeout(function() {
                            timeout = null, immediate || (result = func.apply(context, args));
                        }, delay), callNow && (result = func.apply(context, args)), result;
                    };
                },
                data_options: function(el, data_attr_name) {
                    data_attr_name = data_attr_name || "options";
                    var ii, p, opts_arr, o, opts = {}, cached_options = function(el) {
                        var namespace = Foundation.global.namespace;
                        return 0 < namespace.length ? el.data(namespace + "-" + data_attr_name) : el.data(data_attr_name);
                    }(el);
                    if ("object" == typeof cached_options) return cached_options;
                    function trim(str) {
                        return "string" == typeof str ? $.trim(str) : str;
                    }
                    for (ii = (opts_arr = (cached_options || ":").split(";")).length; ii--; ) p = [ (p = opts_arr[ii].split(":"))[0], p.slice(1).join(":") ], 
                    /true/i.test(p[1]) && (p[1] = !0), /false/i.test(p[1]) && (p[1] = !1), o = p[1], 
                    isNaN(o - 0) || null === o || "" === o || !1 === o || !0 === o || (-1 === p[1].indexOf(".") ? p[1] = parseInt(p[1], 10) : p[1] = parseFloat(p[1])), 
                    2 === p.length && 0 < p[0].length && (opts[trim(p[0])] = trim(p[1]));
                    return opts;
                },
                register_media: function(media, media_class) {
                    var string;
                    void 0 === Foundation.media_queries[media] && ($("head").append('<meta class="' + media_class + '"/>'), 
                    Foundation.media_queries[media] = (("string" == typeof (string = $("." + media_class).css("font-family")) || string instanceof String) && (string = string.replace(/^['\\/"]+|(;\s?})+|['\\/"]+$/g, "")), 
                    string));
                },
                add_custom_rule: function(rule, media) {
                    void 0 === media && Foundation.stylesheet ? Foundation.stylesheet.insertRule(rule, Foundation.stylesheet.cssRules.length) : void 0 !== Foundation.media_queries[media] && Foundation.stylesheet.insertRule("@media " + Foundation.media_queries[media] + "{ " + rule + " }", Foundation.stylesheet.cssRules.length);
                },
                image_loaded: function(images, callback) {
                    var self = this, unloaded = images.length;
                    (0 === unloaded || function(images) {
                        for (var i = images.length - 1; 0 <= i; i--) if (void 0 === images.attr("height")) return !1;
                        return !0;
                    }(images)) && callback(images), images.each(function() {
                        !function(image, callback) {
                            function loaded() {
                                callback(image[0]);
                            }
                            image.attr("src") ? image[0].complete || 4 === image[0].readyState ? loaded() : function() {
                                if (this.one("load", loaded), /MSIE (\d+\.\d+);/.test(navigator.userAgent)) {
                                    var src = this.attr("src"), param = src.match(/\?/) ? "&" : "?";
                                    param += "random=" + new Date().getTime(), this.attr("src", src + param);
                                }
                            }.call(image) : loaded();
                        }(self.S(this), function() {
                            0 === (unloaded -= 1) && callback(images);
                        });
                    });
                },
                random_str: function() {
                    return this.fidx || (this.fidx = 0), this.prefix = this.prefix || [ this.name || "F", (+new Date()).toString(36) ].join("-"), 
                    this.prefix + (this.fidx++).toString(36);
                },
                match: function(mq) {
                    return window.matchMedia(mq).matches;
                },
                is_small_up: function() {
                    return this.match(Foundation.media_queries.small);
                },
                is_medium_up: function() {
                    return this.match(Foundation.media_queries.medium);
                },
                is_large_up: function() {
                    return this.match(Foundation.media_queries.large);
                },
                is_xlarge_up: function() {
                    return this.match(Foundation.media_queries.xlarge);
                },
                is_xxlarge_up: function() {
                    return this.match(Foundation.media_queries.xxlarge);
                },
                is_small_only: function() {
                    return !(this.is_medium_up() || this.is_large_up() || this.is_xlarge_up() || this.is_xxlarge_up());
                },
                is_medium_only: function() {
                    return this.is_medium_up() && !this.is_large_up() && !this.is_xlarge_up() && !this.is_xxlarge_up();
                },
                is_large_only: function() {
                    return this.is_medium_up() && this.is_large_up() && !this.is_xlarge_up() && !this.is_xxlarge_up();
                },
                is_xlarge_only: function() {
                    return this.is_medium_up() && this.is_large_up() && this.is_xlarge_up() && !this.is_xxlarge_up();
                },
                is_xxlarge_only: function() {
                    return this.is_medium_up() && this.is_large_up() && this.is_xlarge_up() && this.is_xxlarge_up();
                }
            }
        }, $.fn.foundation = function() {
            var args = Array.prototype.slice.call(arguments, 0);
            return this.each(function() {
                return Foundation.init.apply(Foundation, [ this ].concat(args)), this;
            });
        };
    }(jQuery, window, window.document);
    var showVideoControls = 480 <= $(window).width() ? 1 : 0;
    function lazyLoad(e) {
        var $this = $(this), youTubeID = $this.data("youtube-id");
        if (youTubeID) {
            var $parent = $this.parents(".js-yt-placeholder"), params = {
                autoplay: 1,
                cc_load_policy: 1,
                color: "white",
                controls: showVideoControls,
                rel: 0,
                showinfo: 0
            }, query = $.param(params), youTubeIframe = "<iframe ";
            youTubeIframe += 'src="' + "https://www.youtube.com/embed/".concat(youTubeID, "?").concat(query) + '" ', 
            youTubeIframe += 'frameborder="0" ', youTubeIframe += "allowfullscreen ", youTubeIframe += "/>", 
            $parent.append(youTubeIframe), $this.fadeOut(750, function() {
                $this.remove();
            }), e.preventDefault();
        }
    }
    function _typeof(obj) {
        return (_typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(obj) {
            return typeof obj;
        } : function(obj) {
            return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
        })(obj);
    }
    function _classCallCheck(instance, Constructor) {
        if (!(instance instanceof Constructor)) throw new TypeError("Cannot call a class as a function");
    }
    function _defineProperties(target, props) {
        for (var i = 0; i < props.length; i++) {
            var descriptor = props[i];
            descriptor.enumerable = descriptor.enumerable || !1, descriptor.configurable = !0, 
            "value" in descriptor && (descriptor.writable = !0), Object.defineProperty(target, descriptor.key, descriptor);
        }
    }
    function _createClass(Constructor, protoProps, staticProps) {
        return protoProps && _defineProperties(Constructor.prototype, protoProps), staticProps && _defineProperties(Constructor, staticProps), 
        Constructor;
    }
    var $body = $("body");
    $body.addClass("has-closed-modal");
    var focusableSelectors = [ "a[href]", "area[href]", 'input:not([disabled]):not([type="hidden"]):not([readonly])', 'select:not([disabled]):not([type="hidden"]):not([readonly])', 'textarea:not([disabled]):not([type="hidden"]):not([readonly])', 'button:not([disabled]):not([type="hidden"]):not([readonly])', "iframe", "object", "embed", "*[tabindex]", "*[contenteditable]" ].join(), allModals = [], Modal = function() {
        function Modal() {
            _classCallCheck(this, Modal);
            var defaults = {
                uniqueID: "modal-" + allModals.length
            };
            return arguments[0] && "object" === _typeof(arguments[0]) && (this.options = $.extend(arguments[0], defaults)), 
            this.$target = $("#" + this.options.target), this.modalClass = this.$target.data("modal-class"), 
            this.isOpen = !1, this.$site = $(".js-site"), this.$modal = $("#modal"), this.$modalContent = $("#modal-content"), 
            this.$modalOverlay = $("#modal-overlay"), this.$modalOverlay.on("click", this, function(e) {
                e.data.close();
            }), this.$modalFrame = $("#modal-frame"), this.$modalFrame.on("click", this, function(e) {
                var $target = $(e.target), theModal = e.data;
                ($target.is(".js-modal__frame") || $target.is(".js-modal__close-button") || $target.parents(".js-modal__close-button").length) && theModal.close();
            }).on("keydown", this, function(e) {
                var theModal = e.data;
                27 == e.which && (theModal.close(), e.preventDefault());
            }), allModals.push(this), arguments[1] && "function" == typeof arguments[1] && arguments[1].call(this), 
            this;
        }
        return _createClass(Modal, [ {
            key: "getOptions",
            value: function() {
                return this.options;
            }
        }, {
            key: "isOpen",
            value: function() {
                return this.isOpen;
            }
        }, {
            key: "open",
            value: function() {
                this.$modalTriggerElement = $(document.activeElement), this.closeAll(), this.$placeholder = $("<div></div>").hide(), 
                this.$target.after(this.$placeholder), this.$detached = this.$target.detach(), this.$modalContent.append(this.$detached), 
                this.$modal.addClass(this.modalClass), this.trigger("modal:open"), this.$site.attr("tabindex", "-1").attr("aria-hidden", !0).find(focusableSelectors).attr("tabindex", "-1"), 
                $body.removeClass("has-closed-modal").addClass("has-open-modal"), this.$modalFrame.removeAttr("aria-hidden"), 
                this.$modalContent.attr("tabindex", "0").focus(), this.isOpen = !0, this.trigger("modal:opened");
            }
        }, {
            key: "close",
            value: function() {
                if (!this.isOpen) return !1;
                $body.removeClass("has-open-modal").addClass("has-closed-modal"), this.$placeholder.replaceWith(this.$target), 
                this.$modalContent.html(""), this.$modalFrame.attr("aria-hidden", "true"), this.$modal.removeClass(this.modalClass), 
                this.trigger("modal:close"), this.$site.removeAttr("tabindex").removeAttr("aria-hidden").find(focusableSelectors).removeAttr("tabindex"), 
                this.$modalTriggerElement.focus(), this.$modalTriggerElement = null, this.isOpen = !1;
            }
        }, {
            key: "closeAll",
            value: function() {
                for (var i = 0; i < allModals.length; i++) allModals[i].close();
            }
        }, {
            key: "on",
            value: function(event, callback) {
                var eventName = event + "-" + this.options.uniqueID;
                return this.$modalContent.on(eventName, callback), this;
            }
        }, {
            key: "trigger",
            value: function(event) {
                var eventName = event + "-" + this.options.uniqueID;
                this.$modalContent.trigger(eventName, this);
            }
        } ]), Modal;
    }();
    function handleModals() {
        $(".js-modal-trigger").each(function(index, elem) {
            var $elem = $(elem), target = $elem.data("modal-id");
            if (target) {
                var theModal = new Modal({
                    target: target
                });
                "search-modal" == target && theModal.on("modal:opened", function() {
                    focusAtEnd($(".js-modal-search-field"));
                }), $elem.on("click", theModal, function(e) {
                    e.data.open(), e.preventDefault();
                });
            }
        });
    }
    function ScrollDepth(selector, label, percs) {
        var $ = jQuery, $window = $(window), cache = [];
        if (this.selector = selector, this.label = label, this.percs = percs, this.$element = $(this.selector), 
        this.eventNamespace = "scroll.depth" + function(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }(this.label.replace(/\s(.)/g, function(s) {
            return s.toUpperCase();
        }).replace(/\s/g, "").replace(/^(.)/, function(s) {
            return s.toLowerCase();
        })), this.$element.length) {
            var calculateMarks = $.proxy(function() {
                var perc, markKey, marks = {}, percs = this.percs, offset = this.$element.offset().top, height = this.$element.height();
                percs.sort(function(a, b) {
                    return a - b;
                });
                for (var i = 0; i < percs.length; i++) switch (markKey = (perc = percs[i]) + "%", 
                perc) {
                  case 0:
                    marks[markKey] = offset;
                    break;

                  case 100:
                    marks[markKey] = height - 5 + offset;
                    break;

                  default:
                    marks[markKey] = parseInt(height * (.01 * perc), 10) + offset;
                }
                return marks;
            }, this), checkElements = $.proxy(function(depth) {
                this.$element.length && depth >= this.$element.offset().top && $.each(calculateMarks(), $.proxy(function(perc, coordY) {
                    var action, label;
                    -1 === $.inArray(perc, cache) && coordY <= depth && (action = perc, label = this.label, 
                    "function" == typeof window.ga && window.ga("send", "event", "post-scroll-depth", action, label, null, {
                        nonInteraction: !0
                    }), cache.push(perc));
                }, this));
            }, this);
            $window.on(this.eventNamespace, throttle($.proxy(function() {
                var winHeight = window.innerHeight || $window.height(), scrollDepth = $window.scrollTop() + winHeight;
                cache.length >= this.percs.length || !this.$element.length ? $window.off(this.eventNamespace) : checkElements(scrollDepth);
            }, this), 750));
        }
    }
    function ShareButtons($) {
        this.result = !1;
        var $buttonsContainerTop = $(".js-share-buttons-top"), $buttonsContainerBottom = $(".js-share-buttons-bottom"), $bottomPlaceholder = $(".js-share-buttons-bottom-placeholder");
        if ($buttonsContainerTop.length && $buttonsContainerBottom.length) {
            var $win = $(window), $bottomButtons = $buttonsContainerBottom.find(".js-share-button");
            this.getCutoffs = function() {
                var $adminBar = $("#wpadminbar"), topOffset = $buttonsContainerTop.offset(), top = topOffset ? topOffset.top + $buttonsContainerTop.height() : 0, bottomOffset = $bottomPlaceholder.offset(), bottom = bottomOffset ? bottomOffset.top - $win.height() : 0;
                return 0 < $adminBar.length && (top -= $adminBar.height()), {
                    top: top,
                    bottom: bottom
                };
            }, this.cutoffs = this.getCutoffs(), this.scroll = function() {
                var currentPosition = $win.scrollTop();
                currentPosition < this.cutoffs.top ? $buttonsContainerBottom.hide() : ($buttonsContainerBottom.show(), 
                currentPosition < this.cutoffs.bottom ? this.updateBottomButtonsLabel("sticky") : this.updateBottomButtonsLabel("bottom"));
            }, this.resize = function() {
                this.cutoffs = this.getCutoffs();
            }, this.updateBottomButtonsLabel = function(position) {
                $bottomButtons.attr("data-ga-label", position).data("ga-label", position);
            }, this.result = !0;
        }
    }
    function handleSubscriptionForms(e) {
        e.preventDefault();
        var $el = $(this), $parent = $el.parent(), $submitBtn = $el.find(".js-form-submit"), $invalidFeedback = $el.find(".js-fail-message"), actionURL = $el.attr("action"), actionUrlSeparator = 0 <= actionURL.indexOf("?") ? "&" : "?";
        actionURL += actionUrlSeparator + $.param({
            "ajax-request": 1
        }), $parent.removeClass("is-failed").addClass("is-loading"), $invalidFeedback.removeAttr("role"), 
        $.post(actionURL, $el.serialize(), function() {
            if ($el.find(".js-success-message").length) {
                var $successEmail = $el.find(".js-success-message-email"), emailAddress = $el.find(".js-email-input").val();
                $parent.removeClass("is-loading").addClass("is-success"), $el.trigger("pedFormSubmission:success", [ {
                    emailAddress: emailAddress
                } ]), emailAddress && $successEmail.length && $successEmail.text(emailAddress).addClass("u-font-weight--bold");
            }
        }).fail(function(response) {
            var msg = response.responseText;
            $parent.removeClass("is-loading").addClass("is-failed"), $invalidFeedback.length && msg.length ? $invalidFeedback.attr("role", "alert").text(msg) : $submitBtn.before(msg);
        });
    }
    function handleTargetedMessages(e, data) {
        if (!$("body").is(".is-target-audience--disabled")) {
            var targetAudience = !1;
            if ("data" in data) {
                var theData = data.data;
                "current_member" in theData && theData.current_member ? targetAudience = "member" : "donate_365" in theData && theData.donate_365 ? targetAudience = "donor" : "newsletter_subscriber" in theData && theData.newsletter_subscriber && (targetAudience = "subscriber");
            }
            targetAudience && setTargetAudience(targetAudience);
        }
    }
    function setTargetAudience(targetAudience) {
        $("body").removeClass(function(i, className) {
            return (className.match(/(^|\s)(is-target-audience)\S+/g) || []).join(" ");
        }).addClass("is-target-audience--" + targetAudience);
    }
    var adblockerDetection = new (function() {
        function AdblockerDetection() {
            var _this = this;
            _classCallCheck(this, AdblockerDetection), this.log("constructor", "Launched!"), 
            this.detecting = !1, this.loopIteration = 0, this.loopInterval = 50, this.loopMaxNumber = 10, 
            this._bait = null;
            var loadBait = document.createElement("script");
            loadBait.src = window.location.protocol + "//" + window.location.hostname + "/wp-content/themes/pedestal/assets/dist/" + PEDESTAL_VERSION + "/js/ads.js", 
            loadBait.onload = function() {
                return _this.detect();
            }, loadBait.onerror = function() {
                _this.log("constructor", "ads.js did not load successfully"), _this.triggerEvent(!0);
            }, document.head.appendChild(loadBait);
        }
        return _createClass(AdblockerDetection, [ {
            key: "log",
            value: function(caller, message) {
                if (-1 < location.search.indexOf("debug-abd")) {
                    console.log("[PedestalABD][".concat(caller, "] ").concat(message));
                    var containers = document.querySelectorAll(".js-abd-debug");
                    if (0 !== containers.length) {
                        var _iteratorNormalCompletion = !0, _didIteratorError = !1, _iteratorError = void 0;
                        try {
                            for (var _step, _iterator = containers[Symbol.iterator](); !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = !0) {
                                var div = _step.value, content = document.createTextNode(message), elem = document.createElement("p");
                                elem.appendChild(content), div.appendChild(elem), div.style.display = "block";
                            }
                        } catch (err) {
                            _didIteratorError = !0, _iteratorError = err;
                        } finally {
                            try {
                                _iteratorNormalCompletion || null == _iterator.return || _iterator.return();
                            } finally {
                                if (_didIteratorError) throw _iteratorError;
                            }
                        }
                    } else console.log("ABD debug message containers not found!");
                }
            }
        }, {
            key: "detect",
            value: function() {
                var _this2 = this;
                if (this.log("detect", "Detecting..."), !0 === this.detecting) return this.log("detect", "Detection was cancelled because there's already an ongoing check!"), 
                !1;
                this.detecting = !0, this.loop = setInterval(function() {
                    _this2.log("detect", "A check is in progress..."), _this2.checkBait(), _this2.loopIteration++;
                }, this.loopInterval);
            }
        }, {
            key: "checkBait",
            value: function() {
                var detected = !1;
                if (this.bait) {
                    null === document.body.getAttribute("abp") && null !== this.bait.offsetParent && 0 != this.bait.offsetHeight && 0 != this.bait.offsetLeft && 0 != this.bait.offsetTop && 0 != this.bait.offsetWidth && 0 != this.bait.clientHeight && 0 != this.bait.clientWidth || (detected = !0);
                    var baitTemp = window.getComputedStyle(this.bait);
                    !baitTemp || "none" != baitTemp.getPropertyValue("display") && "hidden" != baitTemp.getPropertyValue("visibility") || (detected = !0);
                } else detected = !0;
                if (this.loopIteration >= this.loopMaxNumber) this.stopLoop(); else {
                    var loopCount = this.loopIteration + 1, timeElapsed = loopCount * this.loopInterval, status = "".concat(loopCount, "/").concat(this.loopMaxNumber, " ~").concat(timeElapsed, "ms"), resultStr = !0 === detected ? "positive" : "negative", message = "A check (".concat(status, ") was conducted and detection is ").concat(resultStr);
                    this.log("checkBait", message);
                }
                !0 === detected ? (this.stopLoop(), this.destroyBait(), this.triggerEvent(!0), document.body.classList.add("has-abd-positive")) : null === this.loop && (this.destroyBait(), 
                this.triggerEvent(!1)), this.detecting = !1;
            }
        }, {
            key: "destroyBait",
            value: function() {
                this.bait && (document.body.removeChild(this.bait), this.bait = null, this.log("destroyBait", "Bait has been removed"));
            }
        }, {
            key: "stopLoop",
            value: function() {
                clearInterval(this.loop), this.loop = null, this.loopIteration = 0, this.log("stopLoop", "A loop has been stopped");
            }
        }, {
            key: "triggerEvent",
            value: function(result) {
                var resultStr = !0 === result ? "positive" : "negative", event = new CustomEvent("pedABD:" + resultStr);
                document.dispatchEvent(event), this.log("triggerEvent", "An event with a ".concat(resultStr, " detection was fired"));
            }
        }, {
            key: "bait",
            get: function() {
                if (null === this._bait) {
                    var bait = document.querySelector(".adsbox");
                    if (null === bait) return this.log("getBait", "Bait not found"), !1;
                    this._bait = bait, this.log("get:bait", "Bait found");
                }
                return this._bait;
            },
            set: function(bait) {
                this._bait = bait;
            }
        } ]), AdblockerDetection;
    }())(), contact = new (function() {
        function Contact() {
            _classCallCheck(this, Contact), this.dataStorageKey = "contactData", this.historyStorageKey = "contactHistory", 
            this.adblockerStorageKey = "contactAdblocker", this.version = 4, this.contactData(), 
            this.contactHistory();
        }
        return _createClass(Contact, [ {
            key: "contactData",
            value: function() {
                var _this = this;
                $(".js-signup-email-form").on("pedFormSubmission:success", function(e, data) {
                    "emailAddress" in data && _this.fetchData(data.emailAddress, !1);
                });
                var oldContactData = localStorageCookie("subscriberData");
                oldContactData && "data" in oldContactData && (localStorageCookie("subscriberData", ""), 
                localStorageCookie(this.dataStorageKey, oldContactData));
                var queryStringId = function() {
                    var key = 0 < arguments.length && void 0 !== arguments[0] ? arguments[0] : "", url = 1 < arguments.length && void 0 !== arguments[1] ? arguments[1] : "", params = {};
                    return url || (url = location.search), url.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(match, param, val) {
                        params[param] = val;
                    }), key ? params[key] : params;
                }("mc_eid"), contactData = localStorageCookie(this.dataStorageKey);
                if (!(contactData && "object" == _typeof(contactData) && "data" in contactData)) return this.deleteData(), 
                void this.fetchData(queryStringId);
                if (!("mc_id" in contactData.data && "version" in contactData && "updated" in contactData)) return this.deleteData(), 
                void this.fetchData(queryStringId);
                var theId = contactData.data.mc_id;
                if (queryStringId && (theId = queryStringId), contactData.version != this.version) return this.deleteData(), 
                void this.fetchData(theId);
                var now = new Date().getTime() / 1e3, updatedCutOff = new Date(contactData.updated).getTime() / 1e3;
                if ((updatedCutOff += 1209600) <= now) return this.deleteData(), void this.fetchData(theId);
                $(document).on("ready", function() {
                    return _this.triggerEvent("ready", contactData);
                });
            }
        }, {
            key: "contactHistory",
            value: function() {
                var history = localStorageCookie(this.historyStorageKey);
                history && Array.isArray(history) || (history = []), history.unshift({
                    t: Date.now(),
                    u: window.location.pathname
                });
                var dateCutoff = new Date();
                dateCutoff.setDate(dateCutoff.getDate() - 30), history = history.filter(function(item) {
                    return item.t > dateCutoff.getTime();
                }), localStorageCookie(this.historyStorageKey, history);
            }
        }, {
            key: "isFrequentReader",
            value: function() {
                var history = localStorageCookie(this.historyStorageKey);
                if (history && 6 <= history.filter(function(item) {
                    return "/20" === item.u.slice(0, 3);
                }).length) return !0;
                return !1;
            }
        }, {
            key: "deleteData",
            value: function() {
                localStorageCookie(this.dataStorageKey, "");
            }
        }, {
            key: "fetchData",
            value: function(id) {
                var _this2 = this, triggerReadyEvent = !(1 < arguments.length && void 0 !== arguments[1]) || arguments[1];
                if (id) {
                    var storageKey = this.dataStorageKey, ajaxData = {
                        action: "get_contact_data",
                        contactID: id
                    };
                    $.post(PedVars.ajaxurl, ajaxData, function(resp) {
                        resp.success && (localStorageCookie(storageKey, resp.data), triggerReadyEvent && _this2.triggerEvent("ready", resp.data));
                    });
                }
            }
        }, {
            key: "triggerEvent",
            value: function(eventName, data) {
                var evt = "pedContact:" + eventName;
                $(document).trigger(evt, [ data ]);
            }
        }, {
            key: "adblocker",
            set: function(detected) {
                var value = "boolean" == typeof detected ? detected : null;
                localStorageCookie(this.adblockerStorageKey, value);
            }
        } ]), Contact;
    }())();
    !function($) {
        var Pedestal = {
            init: function() {
                var $document = $(document);
                $document.foundation(), $("html").removeClass("no-js").addClass("js"), analytics(), 
                handleDonateForm(), handleModals(), new ScrollDepth(".js-original-content-body", "", [ 0, 50, 100 ]), 
                jQuery(window).on("load", function() {
                    var $ = jQuery, $win = $(window), obj = new ShareButtons($), result = obj.result;
                    if (!result) return !1;
                    obj.updateBottomButtonsLabel("sticky");
                    var scrollCb = $.proxy(obj.scroll, obj);
                    $win.on("scroll", throttle(scrollCb, 50));
                    var resizeCb = $.proxy(obj.resize, obj);
                    $win.on("resize", throttle(resizeCb, 250));
                    var mutationCb = resizeCb;
                    return "function" == typeof MutationObserver && new MutationObserver(function(mutations) {
                        mutations.forEach(function() {
                            mutationCb.call();
                        });
                    }).observe(document.body, {
                        attributes: !1,
                        childList: !0,
                        characterData: !1
                    }), result;
                }), contact.isFrequentReader() && setTargetAudience("frequent-reader"), $document.on("pedContact:ready", handleTargetedMessages), 
                $(".js-signup-email-form").on("submit", handleSubscriptionForms), $(".content-wrapper").on("click", ".js-yt-placeholder-link", lazyLoad), 
                focusAtEnd($("#search-standalone-input")), this.disabledAnchors(), this.honeyPotHelper(), 
                $document.on("pedABD:positive", function() {
                    return contact.adblocker = !0;
                }), $document.on("pedABD:negative", function() {
                    return contact.adblocker = !1;
                }), this.AdblockerDetection = adblockerDetection;
            },
            disabledAnchors: function() {
                $("a.disabled").click(function(e) {
                    e.preventDefault();
                });
            },
            honeyPotHelper: function() {
                var fullYear = new Date().getFullYear();
                $(".js-pedestal-current-year-check").val(fullYear);
            }
        };
        $(document).ready(function() {
            Pedestal.init();
        });
    }(jQuery);
}();