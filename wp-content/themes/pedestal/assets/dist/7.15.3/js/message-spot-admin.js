!function() {
    "use strict";
    function debounce(fn) {
        var timeout, wait = 1 < arguments.length && void 0 !== arguments[1] ? arguments[1] : 300, immediate = 2 < arguments.length && void 0 !== arguments[2] && arguments[2];
        return function() {
            var _this = this, _arguments = arguments, functionCall = function() {
                return fn.apply(_this, _arguments);
            }, callNow = immediate && !timeout;
            clearTimeout(timeout), timeout = setTimeout(functionCall, wait), callNow && functionCall();
        };
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
    function _inherits(subClass, superClass) {
        if ("function" != typeof superClass && null !== superClass) throw new TypeError("Super expression must either be null or a function");
        subClass.prototype = Object.create(superClass && superClass.prototype, {
            constructor: {
                value: subClass,
                writable: !0,
                configurable: !0
            }
        }), superClass && _setPrototypeOf(subClass, superClass);
    }
    function _getPrototypeOf(o) {
        return (_getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function(o) {
            return o.__proto__ || Object.getPrototypeOf(o);
        })(o);
    }
    function _setPrototypeOf(o, p) {
        return (_setPrototypeOf = Object.setPrototypeOf || function(o, p) {
            return o.__proto__ = p, o;
        })(o, p);
    }
    function _possibleConstructorReturn(self, call) {
        return !call || "object" != typeof call && "function" != typeof call ? function(self) {
            if (void 0 === self) throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
            return self;
        }(self) : call;
    }
    var Preview = function() {
        function Preview($el, View, defaults) {
            if (_classCallCheck(this, Preview), this.$el = $el, this.$modelStorage = this.$el.find(".fm-preview_model .fm-element"), 
            this.modelDefaults = defaults, 0 < this.$modelStorage.length) {
                var stored = decodeURIComponent(this.$modelStorage.val());
                stored && (this.modelDefaults = JSON.parse(stored));
            }
            var Model = Backbone.Model.extend({
                defaults: this.modelDefaults,
                $storage: this.$modelStorage,
                sync: function() {
                    var encodedData = encodeURIComponent(JSON.stringify(this));
                    this.$storage.val(encodedData);
                }
            });
            this.Model = new Model(), this.View = new View({
                el: this.$el,
                model: this.Model
            });
        }
        return _createClass(Preview, [ {
            key: "destroy",
            value: function() {
                this.View.destroy();
            }
        } ]), Preview;
    }(), Message = function() {
        function Message($el, previewView, defaults) {
            _classCallCheck(this, Message), this.$el = $el, this.previewView = previewView, 
            this.defaults = defaults, this.createPreview();
        }
        return _createClass(Message, [ {
            key: "createPreview",
            value: function() {
                this.Preview = new Preview(this.$el, this.previewView, this.defaults);
            }
        }, {
            key: "destroyPreview",
            value: function() {
                "destroy" in this.Preview && this.Preview.destroy(), this.Preview = null;
            }
        }, {
            key: "setPreviewAttribute",
            value: function(attr, val) {
                this.Preview.View.model.save(attr, val);
            }
        }, {
            key: "getPreviewFrame",
            value: function() {
                return this.Preview.View.$output;
            }
        } ]), Message;
    }(), PreviewView = function(options) {
        var _this = this;
        this.editor = null, this.editorID = !1, this.$el = $(options.el), this.model = options.model, 
        this.setupFrame(), this.$output.on("load", function() {
            var $bodyEl = _this.$el.find(".fm-body .fm-element");
            $bodyEl.hasClass("fm-richtext") && (_this.editorID = $bodyEl.attr("id"), _this.listenToBodyEditor()), 
            _this.render();
        }), this.listenTo(this.model, "change", this.render), Backbone.View.apply(this, arguments);
    };
    PreviewView.extend = Backbone.View.extend, Object.assign(PreviewView.prototype, Backbone.View.prototype, {
        listeningToEditor: !1,
        widthButtonLabels: {
            toDesktop: "Switch to desktop preview",
            toMobile: "Switch to mobile preview"
        },
        debounceEvents: function(events) {
            for (var key in events) events.hasOwnProperty(key) && (events[key] = debounce(events[key]));
            return events;
        },
        setupFrame: function() {
            var messageID = this.$el.find(".fm-id .fm-element").val();
            this.$outputContainer = this.$el.find(".js-message-preview-container"), this.$outputContainer.html('\n      <iframe src="'.concat(pedestalPreviewURL).concat(messageID, '/"\n        class="message-preview js-message-preview js-responsive-iframe"\n        data-true-width="645"\n        data-true-height="260"\n      ></iframe>\n    ')), 
            this.$output = this.$outputContainer.find(".js-message-preview");
        },
        setupFrameWidthToggle: function() {
            this.$toggleWidthButton = $('\n      <button\n        type="button"\n        title="Change preview width"\n        class="js-message-preview-toggle-width button-secondary"\n      >'.concat(this.widthButtonLabels.toDesktop, "</button>\n    ")), 
            this.$toggleWidthButton.insertAfter(this.$outputContainer);
        },
        onToggleWidthClick: function(e) {
            var $this = $(e.target), largeClass = "message-preview-container--large";
            $this.text() === this.widthButtonLabels.toDesktop ? ($this.text(this.widthButtonLabels.toMobile), 
            this.$outputContainer.addClass(largeClass)) : ($this.text(this.widthButtonLabels.toDesktop), 
            this.$outputContainer.removeClass(largeClass)), e.preventDefault();
        },
        listenToBodyEditor: function() {
            var _this2 = this;
            if ("undefined" != typeof tinyMCE && this.editorID) {
                var listen = function() {
                    _this2.editor.on("keyup", debounce(function() {
                        _this2.model.save("body", _this2.editor.getContent());
                    })), _this2.listeningToEditor = !0;
                };
                tinyMCE.hasOwnProperty("editors") && $.each(tinyMCE.editors, function(i, ed) {
                    !_this2.listeningToEditor && ed.hasOwnProperty("id") && ed.id.trim() === _this2.editorID && (_this2.editor = ed, 
                    listen());
                }), tinyMCE.on("AddEditor", function(e) {
                    _this2.listeningToEditor || e.editor.id !== _this2.editorID || (_this2.editor = e.editor, 
                    listen());
                });
            }
        },
        listenToIconButton: function(e) {
            var name = $(e.currentTarget).data("message-icon-value");
            this.model.save("icon_name", name);
        },
        destroy: function() {
            this.undelegateEvents(), this.editor && "off" in this.editor && this.editor.off(), 
            this.$el.removeData().unbind(), this.$output.remove(), this.$toggleWidthButton && this.$toggleWidthButton.remove();
        }
    });
    var PreviewView$1 = PreviewView.extend({
        initialize: function() {
            var _this = this;
            this.setupFrameWidthToggle(), this.$output.contents().on("focus", ".js-message-spot", function() {
                _this.$output.focus();
            });
        },
        render: function() {
            var _this2 = this, endpoint = wpApiSettings.root + "pedestal/v1/message-spot/render", context = this.model.toJSON();
            return $.get(endpoint, context).done(function(data) {
                _this2.$output.contents().find("body").html(data);
            }), this;
        },
        events: function() {
            return this.debounceEvents({
                "change .fm-type .fm-element": function(e) {
                    this.model.save("type", e.target.value);
                },
                "keyup .fm-body .fm-element": function(e) {
                    this.model.save("body", e.target.value);
                },
                "input .fm-url .fm-element": function(e) {
                    this.model.save("url", e.target.value);
                },
                "input .fm-title .fm-element": function(e) {
                    this.model.save("title", e.target.value);
                },
                "input .fm-button_label .fm-element": function(e) {
                    this.model.save("button_label", e.target.value);
                },
                "click .js-ped-icon-button": function(e) {
                    this.model.save("icon", $(e.currentTarget).data("message-icon-value"));
                },
                "click .js-message-preview-toggle-width": function(e) {
                    this.onToggleWidthClick(e);
                }
            });
        },
        getVariantClass: function() {
            var type = this.model.get("type");
            if ("standard" === type) return "";
            var classStr = "message-spot--".concat(type.replace("_", "-"));
            return "override" === type && (classStr += " message-spot--with-title"), classStr;
        }
    }), icons = PedestalIcons, IconButtons = function() {
        function IconButtons(el) {
            var _this = this;
            _classCallCheck(this, IconButtons), this.$el = $(el), this.$iconOptions = this.$el.find(".fm-option .fm-element"), 
            this.createButtons(), this.$buttons = this.$el.find(".js-ped-icon-button"), this.$buttons.on("click", function(e) {
                return _this.onClick(e);
            }), this.$buttons.on("keydown", function(e) {
                return _this.onKeydown(e);
            });
        }
        return _createClass(IconButtons, [ {
            key: "createButtons",
            value: function() {
                this.$iconOptions.each(function(i, el) {
                    var $label = $('label[for="'.concat(el.id, '"]')), labelText = $.trim($label.text()), iconName = el.value, checkedClass = el.checked ? " is-checked" : "", icon = icons[iconName].svg, button = '\n        <a href="#"\n          title="'.concat(labelText, '"\n          class="js-ped-icon-button ped-icon-button button-secondary ').concat(checkedClass, '"\n          data-message-icon-value="').concat(iconName, '"\n        >\n          ').concat(icon, "\n        </a>\n      ");
                    el.style.display = "none", $label.hide(), $(button).insertAfter(el);
                });
            }
        }, {
            key: "onClick",
            value: function(e) {
                var $target = $(e.currentTarget);
                this.$buttons.removeClass("is-checked"), this.$buttons.find(".fm-element:radio").attr("checked", !1), 
                $target.addClass("is-checked"), $target.prev(".fm-element:radio").attr("checked", !0), 
                e.preventDefault();
            }
        }, {
            key: "onKeydown",
            value: function(e) {
                32 == e.which && $(e.currentTarget).trigger("click");
            }
        } ]), IconButtons;
    }(), StandardMessage = function(_Message) {
        function StandardMessage($el) {
            var _this;
            return _classCallCheck(this, StandardMessage), (_this = _possibleConstructorReturn(this, _getPrototypeOf(StandardMessage).call(this, $el, PreviewView$1, messagePreviewDefaults.standard))).iconButtons = new IconButtons($el.find(".fm-icon")), 
            _this;
        }
        return _inherits(StandardMessage, Message), StandardMessage;
    }(), OverrideMessage = function(_Message) {
        function OverrideMessage() {
            var _this;
            _classCallCheck(this, OverrideMessage);
            var defaults = messagePreviewDefaults.override;
            return (_this = _possibleConstructorReturn(this, _getPrototypeOf(OverrideMessage).call(this, $(".fm-override_message"), PreviewView$1, defaults))).post = null, 
            _this.$el.on("change", ".fm-autocomplete-hidden", function(e) {
                return _this.onPostSelection(e);
            }), _this;
        }
        return _inherits(OverrideMessage, Message), _createClass(OverrideMessage, [ {
            key: "onPostSelection",
            value: function(e) {
                var _this2 = this, $postSelect = $(e.target), postId = parseInt($postSelect.val()), $group = $postSelect.closest(".fm-group-inner"), $body = $group.find(".fm-body .fm-element"), $url = $group.find(".fm-url .fm-element");
                if (!postId) return $body.val(""), void $url.val("");
                var data = {
                    post_id: postId,
                    action: "pedestal-message-spot-override"
                };
                $.post(ajaxurl, data, function(response) {
                    if (response.data) {
                        _this2.post = response.data, $body.val(_this2.post.title), _this2.setPreviewAttribute("body", _this2.post.title), 
                        $group.find(".fm-post_title .fm-element").val(_this2.post.title), _this2.setPreviewAttribute("postTitle", _this2.post.title);
                        var url = encodeURI(_this2.post.url);
                        $url.val(url), _this2.setPreviewAttribute("url", url);
                    }
                });
            }
        } ]), OverrideMessage;
    }();
    jQuery(document).ready(function($) {
        $(".fm-message:not(.fmjs-proto)").each(function() {
            new StandardMessage($(this));
        }), $(document).on("fm_added_element", function(e) {
            var $this = $(e.target), messageID = function() {
                for (var length = 0 < arguments.length && void 0 !== arguments[0] ? arguments[0] : 8, out = "", alphabet = "23456789abdegjkmnpqrvwxyz", i = 0; i < length; i++) out += alphabet.charAt(Math.floor(Math.random() * alphabet.length));
                return out;
            }();
            $this.find(".fm-id .fm-element").val(messageID), new StandardMessage($this);
        });
        var maybeSetupOverride = function(el) {
            var $el = $(el), $fields = $el.closest(".fm-group-inner").find(".fm-wrapper:not(.fm-enabled-wrapper)");
            "true" === $el.val() ? (window.MessageSpotOverride = new OverrideMessage(), $fields.show()) : ($fields.hide(), 
            window.MessageSpotOverride instanceof OverrideMessage && window.MessageSpotOverride.destroyPreview(), 
            window.MessageSpotOverride = null);
        };
        maybeSetupOverride(".fm-enabled .fm-element:checked"), $(document).on("change", ".fm-enabled .fm-element", function(e) {
            return maybeSetupOverride(e.target);
        });
    });
}();