!function() {
    "use strict";
    function _typeof(obj) {
        return (_typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(obj) {
            return typeof obj;
        } : function(obj) {
            return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
        })(obj);
    }
    window.localStorageCookie = function(key, value) {
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
            return returnData;
        }
        function createCookie(key, value, exp) {
            var date = new Date();
            date.setTime(date.getTime() + 24 * exp * 60 * 60 * 1e3);
            var expires = "; expires=" + date.toGMTString();
            document.cookie = key + "=" + value + expires + "; path=/";
        }
        null === value && (lsSupport ? localStorage.removeItem(key) : createCookie(key, "", -1));
    };
}();