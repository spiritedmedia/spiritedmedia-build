!function() {
    "use strict";
    function _typeof(obj) {
        return (_typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(obj) {
            return typeof obj;
        } : function(obj) {
            return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
        })(obj);
    }
    function _defineProperties(target, props) {
        for (var i = 0; i < props.length; i++) {
            var descriptor = props[i];
            descriptor.enumerable = descriptor.enumerable || !1, descriptor.configurable = !0, 
            "value" in descriptor && (descriptor.writable = !0), Object.defineProperty(target, descriptor.key, descriptor);
        }
    }
    var contact = new (function() {
        function Contact() {
            !function(instance, Constructor) {
                if (!(instance instanceof Constructor)) throw new TypeError("Cannot call a class as a function");
            }(this, Contact), this.dataStorageKey = "contactData", this.historyStorageKey = "contactHistory", 
            this.adblockerStorageKey = "contactAdblocker", this.version = 4, this.contactData(), 
            this.contactHistory();
        }
        var Constructor, protoProps, staticProps;
        return Constructor = Contact, (protoProps = [ {
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
        } ]) && _defineProperties(Constructor.prototype, protoProps), staticProps && _defineProperties(Constructor, staticProps), 
        Contact;
    }())();
    !function(key, value) {
        var lsSupport = !1;
        if (function(type) {
            try {
                var storage = window[type], x = "__storage_test__";
                return storage.setItem(x, x), storage.removeItem(x), !0;
            } catch (e) {
                return !1;
            }
        }("localStorage") && (lsSupport = !0), null != value && ("object" === _typeof(value) && (value = JSON.stringify(value)), 
        lsSupport ? localStorage.setItem(key, value) : createCookie(key, value, 30)), void 0 === value) {
            if (lsSupport) var data = localStorage.getItem(key); else data = function(key) {
                for (var nameEQ = key + "=", ca = document.cookie.split(";"), i = 0, max = ca.length; i < max; i++) {
                    for (var c = ca[i]; " " === c.charAt(0); ) c = c.substring(1, c.length);
                    if (0 === c.indexOf(nameEQ)) return c.substring(nameEQ.length, c.length);
                }
                return null;
            }(key);
            try {
                var returnData = JSON.parse(data);
            } catch (e) {
                returnData = data;
            }
            return;
        }
        function createCookie(key, value, exp) {
            var date = new Date();
            date.setTime(date.getTime() + 24 * exp * 60 * 60 * 1e3);
            var expires = "; expires=" + date.toGMTString();
            document.cookie = key + "=" + value + expires + "; path=/";
        }
        null === value && (lsSupport ? localStorage.removeItem(key) : createCookie(key, "", -1));
    }("contactData", ""), jQuery(document).ready(function($) {
        var expectedMergeFields = contactExpectedMergeFields.data;
        contact.fetchData(contactEmail), $(document).on("pedContact:ready", function(e, data) {
            $("#output").html(JSON.stringify(data.data, null, 4));
            var output = {
                pass: [],
                fail: []
            };
            for (var key in expectedMergeFields) if ("since" != key) {
                var valueToCheck = data.data[key], expectedValue = expectedMergeFields[key];
                valueToCheck !== expectedValue ? output.fail.push({
                    key: key,
                    expected: expectedValue,
                    actualValue: valueToCheck
                }) : output.pass.push(key);
            } else output.pass.push(key);
            output.fail.length <= 0 ? $("#pass").show() : ($("#fail").show(), $("#fail-output").html(JSON.stringify(output.fail, null, 4))), 
            $.get("?cleanup", function() {});
        });
    });
}();