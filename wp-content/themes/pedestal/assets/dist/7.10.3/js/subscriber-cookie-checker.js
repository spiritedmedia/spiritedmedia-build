!function(){"use strict";function o(e){return(o="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function a(e,t){for(var r=0;r<t.length;r++){var n=t[r];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}function i(e,t){var r=!1;if(function(e){try{var t=window[e],r="__storage_test__";return t.setItem(r,r),t.removeItem(r),!0}catch(e){return!1}}("localStorage")&&(r=!0),null!=t&&("object"===o(t)&&(t=JSON.stringify(t)),r?localStorage.setItem(e,t):i(e,t,30)),void 0===t){if(r)var n=localStorage.getItem(e);else n=function(e){for(var t=e+"=",r=document.cookie.split(";"),n=0,a=r.length;n<a;n++){for(var i=r[n];" "===i.charAt(0);)i=i.substring(1,i.length);if(0===i.indexOf(t))return i.substring(t.length,i.length)}return null}(e);try{var a=JSON.parse(n)}catch(e){a=n}return a}function i(e,t,r){var n=new Date;n.setTime(n.getTime()+24*r*60*60*1e3);var a="; expires="+n.toGMTString();document.cookie=e+"="+t+a+"; path=/"}null===t&&(r?localStorage.removeItem(e):i(e,"",-1))}var t=function(){function e(){var r=this;!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this.storageKey="subscriberData",this.version=1,$(document).on("ready",function(){var e=i(r.storageKey);e&&"data"in e&&r.triggerEvent("ready",e)}),$(".js-signup-email-form").on("pedFormSubmission:success",function(e,t){return r.listenForEmailSignups(e,t)}),this.maybeRefresh()}var t,r,n;return t=e,(r=[{key:"maybeRefresh",value:function(){var e=function(){var e=0<arguments.length&&void 0!==arguments[0]?arguments[0]:"",t=1<arguments.length&&void 0!==arguments[1]?arguments[1]:"",n={};return t||(t=location.search),t.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(e,t,r){n[t]=r}),e?n[e]:n}("mc_eid"),t=i(this.storageKey);if(t&&"data"in t){var r=!1;if("mc_id"in t.data&&(r=t.data.mc_id),"updated"in t){var n=(new Date).getTime()/1e3,a=new Date(t.updated).getTime()/1e3;if((a+=1209600)<=n)return this.fetchData(r),!0}this.triggerEvent("ready",t)}else if(e)return this.fetchData(e),!0;return!1}},{key:"fetchData",value:function(e){var t=this,r={action:"get_subscriber_data",subscriberID:e},n=this.storageKey;$.post(PedVars.ajaxurl,r,function(e){e.success&&(i(n,e.data),t.triggerEvent("ready",e.data))})}},{key:"listenForEmailSignups",value:function(e,t){"emailAddress"in t&&this.fetchData(t.emailAddress)}},{key:"triggerEvent",value:function(e,t){var r="pedSubscriber:"+e;$(document).trigger(r,[t])}}])&&a(t.prototype,r),n&&a(t,n),e}();jQuery(document).ready(function(o){var e=new t,s=subscriberExpectedMergeFields.data;i(e.storageKey,null),e.fetchData(subscriberEmail),o(this).on("pedSubscriber:ready",function(e,t){o("#output").html(JSON.stringify(t.data,null,4));var r={pass:[],fail:[]};for(var n in s){var a=t.data[n],i=s[n];a!==i?r.fail.push({key:n,expected:i,actualValue:a}):r.pass.push(n)}r.fail.length<=0?o("#pass").show():(o("#fail").show(),o("#fail-output").html(JSON.stringify(r.fail,null,4))),o.get("?cleanup",function(){})})})}();