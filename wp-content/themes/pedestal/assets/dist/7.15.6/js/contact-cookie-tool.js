!function(){"use strict";function c(t){return(c="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}function o(t,e){for(var a=0;a<e.length;a++){var r=e[a];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,r.key,r)}}function i(t,e){var a=!1;if(function(t){try{var e=window[t],a="__storage_test__";return e.setItem(a,a),e.removeItem(a),!0}catch(t){return!1}}("localStorage")&&(a=!0),null!=e&&("object"===c(e)&&(e=JSON.stringify(e)),a?localStorage.setItem(t,e):n(t,e,30)),void 0===e){if(a)var r=localStorage.getItem(t);else r=function(t){for(var e=t+"=",a=document.cookie.split(";"),r=0,o=a.length;r<o;r++){for(var n=a[r];" "===n.charAt(0);)n=n.substring(1,n.length);if(0===n.indexOf(e))return n.substring(e.length,n.length)}return null}(t);try{var o=JSON.parse(r)}catch(t){o=r}return o}function n(t,e,a){var r=new Date;r.setTime(r.getTime()+24*a*60*60*1e3);var o="; expires="+r.toGMTString();document.cookie=t+"="+e+o+"; path=/"}null===e&&(a?localStorage.removeItem(t):n(t,"",-1))}var a,s=new(function(){function t(){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.dataStorageKey="contactData",this.historyStorageKey="contactHistory",this.adblockerStorageKey="contactAdblocker",this.version=4,this.contactData(),this.contactHistory()}var e,a,r;return e=t,(a=[{key:"contactData",value:function(){var a=this;$(".js-signup-email-form").on("pedFormSubmission:success",function(t,e){"emailAddress"in e&&a.fetchData(e.emailAddress,!1)});var t=localStorageCookie("subscriberData");t&&"data"in t&&(localStorageCookie("subscriberData",""),localStorageCookie(this.dataStorageKey,t));var e=function(){var t=0<arguments.length&&void 0!==arguments[0]?arguments[0]:"",e=1<arguments.length&&void 0!==arguments[1]?arguments[1]:"",r={};return e||(e=location.search),e.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(t,e,a){r[e]=a}),t?r[t]:r}("mc_eid"),r=localStorageCookie(this.dataStorageKey);if(!(r&&"object"==c(r)&&"data"in r))return this.deleteData(),void this.fetchData(e);if(!("mc_id"in r.data&&"version"in r&&"updated"in r))return this.deleteData(),void this.fetchData(e);var o=r.data.mc_id;if(e&&(o=e),r.version!=this.version)return this.deleteData(),void this.fetchData(o);var n=(new Date).getTime()/1e3,i=new Date(r.updated).getTime()/1e3;if((i+=1209600)<=n)return this.deleteData(),void this.fetchData(o);$(document).on("ready",function(){return a.triggerEvent("ready",r)})}},{key:"contactHistory",value:function(){var t=localStorageCookie(this.historyStorageKey);t&&Array.isArray(t)||(t=[]),t.unshift({t:Date.now(),u:window.location.pathname});var e=new Date;e.setDate(e.getDate()-30),t=t.filter(function(t){return t.t>e.getTime()}),localStorageCookie(this.historyStorageKey,t)}},{key:"isFrequentReader",value:function(){var t=localStorageCookie(this.historyStorageKey);if(t&&6<=t.filter(function(t){return"/20"===t.u.slice(0,3)}).length)return!0;return!1}},{key:"deleteData",value:function(){localStorageCookie(this.dataStorageKey,"")}},{key:"fetchData",value:function(t){var e=this,a=!(1<arguments.length&&void 0!==arguments[1])||arguments[1];if(t){var r=this.dataStorageKey,o={action:"get_contact_data",contactID:t};$.post(PedVars.ajaxurl,o,function(t){t.success&&(localStorageCookie(r,t.data),a&&e.triggerEvent("ready",t.data))})}}},{key:"triggerEvent",value:function(t,e){var a="pedContact:"+t;$(document).trigger(a,[e])}},{key:"adblocker",set:function(t){var e="boolean"==typeof t?t:null;localStorageCookie(this.adblockerStorageKey,e)}}])&&o(e.prototype,a),r&&o(e,r),t}());(a=jQuery).fn.serializeFormJSON=function(){var t={},e=this.serializeArray();return a.each(e,function(){t[this.name]?(t[this.name].push||(t[this.name]=[t[this.name]]),t[this.name].push(this.value||"")):t[this.name]=this.value||""}),t},jQuery(document).ready(function(r){var t=i(s.dataStorageKey),o=r("#status");function e(){var t=i(s.dataStorageKey);r("#raw-data-output").text(JSON.stringify(t,null,4))}if(t&&"data"in t)for(var n in o.text("Importing values from cookie"),e(),t.data){var a=t.data[n];switch(c(a)){case"boolean":a=a?"true":"false"}r("#"+n).val(a).change()}r("#target-audiences").on("change",function(){var t=r(this).val(),e={newsletter_subscriber:!0,current_member:!1,donate_365:!1};switch(t){case"unidentified":e.newsletter_subscriber=!1;break;case"contact":break;case"donor":e.donate_365=!0;break;case"member":e.current_member=!0}for(n in e){var a=String(e[n]);r("#"+n).val(a)}r(".the-form input").trigger("change"),o.html("Set cookie to <code>"+t+"</code> target audience")}),r(".the-form").on("change","input, select",function(){var t=r(this),a=t.parents("form").serializeFormJSON();r.each(a,function(t,e){""!==e&&(isNaN(1*e)?"false"!==e&&"true"!==e||(a[t]="true"==e):a[t]=1*e)}),i(s.dataStorageKey,{version:4,updated:(new Date).toISOString(),data:a}),o.html("Updated cookie: <code>"+t.attr("name")+"</code> set to <code>"+t.val()+"</code>"),e()})})}();