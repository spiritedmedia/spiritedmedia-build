"use strict";var _createClass=function(){function i(t,e){for(var n=0;n<e.length;n++){var i=e[n];i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(t,i.key,i)}}return function(t,e,n){return e&&i(t.prototype,e),n&&i(t,n),t}}(),_typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t};function _classCallCheck(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}var objectFitImages=function(){var o="bfred-it:object-fit-images",s=/(object-fit|object-position)\s*:\s*([-\w\s%]+)/g,t="undefined"==typeof Image?{style:{"object-position":1}}:new Image,r="object-fit"in t.style,a="object-position"in t.style,l="background-size"in t.style,c="string"==typeof t.currentSrc,u=t.getAttribute,d=t.setAttribute,h=!1;function f(t,e,n){var i="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='"+(e||1)+"' height='"+(n||0)+"'%3E%3C/svg%3E";u.call(t,"src")!==i&&d.call(t,"src",i)}function m(t,e){t.naturalWidth?e(t):setTimeout(m,100,t,e)}function p(e){var n,i,t=function(t){for(var e,n=getComputedStyle(t).fontFamily,i={};null!==(e=s.exec(n));)i[e[1]]=e[2];return i}(e),a=e[o];if(t["object-fit"]=t["object-fit"]||"fill",!a.img){if("fill"===t["object-fit"])return;if(!a.skipTest&&r&&!t["object-position"])return}if(!a.img){a.img=new Image(e.width,e.height),a.img.srcset=u.call(e,"data-ofi-srcset")||e.srcset,a.img.src=u.call(e,"data-ofi-src")||e.src,d.call(e,"data-ofi-src",e.src),e.srcset&&d.call(e,"data-ofi-srcset",e.srcset),f(e,e.naturalWidth||e.width,e.naturalHeight||e.height),e.srcset&&(e.srcset="");try{n=e,i={get:function(t){return n[o].img[t||"src"]},set:function(t,e){return n[o].img[e||"src"]=t,d.call(n,"data-ofi-"+e,t),p(n),t}},Object.defineProperty(n,"src",i),Object.defineProperty(n,"currentSrc",{get:function(){return i.get("currentSrc")}}),Object.defineProperty(n,"srcset",{get:function(){return i.get("srcset")},set:function(t){return i.set(t,"srcset")}})}catch(t){window.console&&console.warn("https://bit.ly/ofi-old-browser")}}!function(t){if(t.srcset&&!c&&window.picturefill){var e=window.picturefill._;t[e.ns]&&t[e.ns].evaled||e.fillImg(t,{reselect:!0}),t[e.ns].curSrc||(t[e.ns].supported=!1,e.fillImg(t,{reselect:!0})),t.currentSrc=t[e.ns].curSrc||t.src}}(a.img),e.style.backgroundImage='url("'+(a.img.currentSrc||a.img.src).replace(/"/g,'\\"')+'")',e.style.backgroundPosition=t["object-position"]||"center",e.style.backgroundRepeat="no-repeat",e.style.backgroundOrigin="content-box",/scale-down/.test(t["object-fit"])?m(a.img,function(){a.img.naturalWidth>e.width||a.img.naturalHeight>e.height?e.style.backgroundSize="contain":e.style.backgroundSize="auto"}):e.style.backgroundSize=t["object-fit"].replace("none","auto").replace("fill","100% 100%"),m(a.img,function(t){f(e,t.naturalWidth,t.naturalHeight)})}function g(t,e){var n=!h&&!t;if(e=e||{},t=t||"img",a&&!e.skipTest||!l)return!1;"img"===t?t=document.getElementsByTagName("img"):"string"==typeof t?t=document.querySelectorAll(t):"length"in t||(t=[t]);for(var i=0;i<t.length;i++)t[i][o]=t[i][o]||{skipTest:e.skipTest},p(t[i]);n&&(document.body.addEventListener("load",function(t){"IMG"===t.target.tagName&&g(t.target,{skipTest:e.skipTest})},!0),h=!0,t="img"),e.watchMQ&&window.addEventListener("resize",g.bind(null,t,{skipTest:e.skipTest}))}return g.supportsObjectFit=r,g.supportsObjectPosition=a,function(){function n(t,e){return t[o]&&t[o].img&&("src"===e||"srcset"===e)?t[o].img:t}a||(HTMLImageElement.prototype.getAttribute=function(t){return u.call(n(this,t),t)},HTMLImageElement.prototype.setAttribute=function(t,e){return d.call(n(this,t),t,String(e))})}(),g}();function DonateForm(){var e,a,o=$(".js-donate-form");e=o.data("nrh-endpoint-domain"),o.on("change",".js-donate-form-frequency",function(){var t=void 0;t=""===$(this).val()?"/donateform":"/memberform",o.attr("action",e+t)}),a=void 0,o.on("change",".js-donate-form-frequency",function(){var t=o.find(".js-donate-form-amount"),e=$(this).val(),n=parseInt(t.val()),i=n;"yearly"===e&&""!==a||""===e&&"yearly"!==a?i=12*n:"monthly"===e&&(i=n/12),i=Math.ceil(i),t.val(i),a=e})}!function(u,l,a,o){var t,e;t=["foundation-mq-small","foundation-mq-small-only","foundation-mq-medium","foundation-mq-medium-only","foundation-mq-large","foundation-mq-large-only","foundation-mq-xlarge","foundation-mq-xlarge-only","foundation-mq-xxlarge","foundation-data-attribute-namespace"],(e=u("head")).prepend(u.map(t,function(t){if(0===e.has("."+t).length)return'<meta class="'+t+'" />'})),u(function(){"undefined"!=typeof FastClick&&void 0!==a.body&&FastClick.attach(a.body)});var c=function(t,e){if("string"==typeof t){if(e){var n;if(e.jquery){if(!(n=e[0]))return e}else n=e;return u(n.querySelectorAll(t))}return u(a.querySelectorAll(t))}return u(t,e)},n=function(t){var e=[];return t||e.push("data"),0<this.namespace.length&&e.push(this.namespace),e.push(this.name),e.join("-")},i=function(t){for(var e=t.split("-"),n=e.length,i=[];n--;)0!==n?i.push(e[n]):0<this.namespace.length?i.push(this.namespace,e[n]):i.push(e[n]);return i.reverse().join("-")},s=function(n,i){var a=this,t=function(){var t=c(this),e=!t.data(a.attr_name(!0)+"-init");t.data(a.attr_name(!0)+"-init",u.extend({},a.settings,i||n,a.data_options(t))),e&&a.events(this)};if(c(this.scope).is("["+this.attr_name()+"]")?t.call(this.scope):c("["+this.attr_name()+"]",this.scope).each(t),"string"==typeof n)return this[n].call(this,i)};function r(t){this.selector=t,this.query=""}l.matchMedia||(l.matchMedia=function(){var e=l.styleMedia||l.media;if(!e){var n,i=a.createElement("style"),t=a.getElementsByTagName("script")[0];i.type="text/css",i.id="matchmediajs-test",t.parentNode.insertBefore(i,t),n="getComputedStyle"in l&&l.getComputedStyle(i,null)||i.currentStyle,e={matchMedium:function(t){var e="@media "+t+"{ #matchmediajs-test { width: 1px; } }";return i.styleSheet?i.styleSheet.cssText=e:i.textContent=e,"1px"===n.width}}}return function(t){return{matches:e.matchMedium(t||"all"),media:t||"all"}}}()),function(e){for(var n,a=0,t=["webkit","moz"],i=l.requestAnimationFrame,o=l.cancelAnimationFrame,s=void 0!==e.fx;a<t.length&&!i;a++)i=l[t[a]+"RequestAnimationFrame"],o=o||l[t[a]+"CancelAnimationFrame"]||l[t[a]+"CancelRequestAnimationFrame"];function r(){n&&(i(r),s&&e.fx.tick())}i?(l.requestAnimationFrame=i,l.cancelAnimationFrame=o,s&&(e.fx.timer=function(t){t()&&e.timers.push(t)&&!n&&(n=!0,r())},e.fx.stop=function(){n=!1})):(l.requestAnimationFrame=function(t){var e=(new Date).getTime(),n=Math.max(0,16-(e-a)),i=l.setTimeout(function(){t(e+n)},n);return a=e+n,i},l.cancelAnimationFrame=function(t){clearTimeout(t)})}(u),r.prototype.toString=function(){return this.query||(this.query=c(this.selector).css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g,""))},l.Foundation={name:"Foundation",version:"5.5.3",media_queries:{small:new r(".foundation-mq-small"),"small-only":new r(".foundation-mq-small-only"),medium:new r(".foundation-mq-medium"),"medium-only":new r(".foundation-mq-medium-only"),large:new r(".foundation-mq-large"),"large-only":new r(".foundation-mq-large-only"),xlarge:new r(".foundation-mq-xlarge"),"xlarge-only":new r(".foundation-mq-xlarge-only"),xxlarge:new r(".foundation-mq-xxlarge")},stylesheet:u("<style></style>").appendTo("head")[0].sheet,global:{namespace:o},init:function(t,e,n,i,a){var o=[t,n,i,a],s=[];if(this.rtl=/rtl/i.test(c("html").attr("dir")),this.scope=t||this.scope,this.set_namespace(),e&&"string"==typeof e&&!/reflow/i.test(e))this.libs.hasOwnProperty(e)&&s.push(this.init_lib(e,o));else for(var r in this.libs)s.push(this.init_lib(r,e));return c(l).load(function(){c(l).trigger("resize.fndtn.clearing").trigger("resize.fndtn.dropdown").trigger("resize.fndtn.equalizer").trigger("resize.fndtn.interchange").trigger("resize.fndtn.joyride").trigger("resize.fndtn.magellan").trigger("resize.fndtn.topbar").trigger("resize.fndtn.slider")}),t},init_lib:function(t,e){return this.libs.hasOwnProperty(t)?(this.patch(this.libs[t]),e&&e.hasOwnProperty(t)?(void 0!==this.libs[t].settings?u.extend(!0,this.libs[t].settings,e[t]):void 0!==this.libs[t].defaults&&u.extend(!0,this.libs[t].defaults,e[t]),this.libs[t].init.apply(this.libs[t],[this.scope,e[t]])):(e=e instanceof Array?e:new Array(e),this.libs[t].init.apply(this.libs[t],e))):function(){}},patch:function(t){t.scope=this.scope,t.namespace=this.global.namespace,t.rtl=this.rtl,t.data_options=this.utils.data_options,t.attr_name=n,t.add_namespace=i,t.bindings=s,t.S=this.utils.S},inherit:function(t,e){for(var n=e.split(" "),i=n.length;i--;)this.utils.hasOwnProperty(n[i])&&(t[n[i]]=this.utils[n[i]])},set_namespace:function(){var t=this.global.namespace===o?u(".foundation-data-attribute-namespace").css("font-family"):this.global.namespace;this.global.namespace=t===o||/false/i.test(t)?"":t},libs:{},utils:{S:c,throttle:function(n,i){var a=null;return function(){var t=this,e=arguments;null==a&&(a=setTimeout(function(){n.apply(t,e),a=null},i))}},debounce:function(i,a,o){var s,r;return function(){var t=this,e=arguments,n=o&&!s;return clearTimeout(s),s=setTimeout(function(){s=null,o||(r=i.apply(t,e))},a),n&&(r=i.apply(t,e)),r}},data_options:function(t,n){n=n||"options";var e,i,a,o,s={},r=function(t){var e=Foundation.global.namespace;return 0<e.length?t.data(e+"-"+n):t.data(n)},l=r(t);if("object"===(void 0===l?"undefined":_typeof(l)))return l;function c(t){return"string"==typeof t?u.trim(t):t}for(e=(a=(l||":").split(";")).length;e--;)i=[(i=a[e].split(":"))[0],i.slice(1).join(":")],/true/i.test(i[1])&&(i[1]=!0),/false/i.test(i[1])&&(i[1]=!1),o=i[1],isNaN(o-0)||null===o||""===o||!1===o||!0===o||(-1===i[1].indexOf(".")?i[1]=parseInt(i[1],10):i[1]=parseFloat(i[1])),2===i.length&&0<i[0].length&&(s[c(i[0])]=c(i[1]));return s},register_media:function(t,e){var n;Foundation.media_queries[t]===o&&(u("head").append('<meta class="'+e+'"/>'),Foundation.media_queries[t]=(("string"==typeof(n=u("."+e).css("font-family"))||n instanceof String)&&(n=n.replace(/^['\\/"]+|(;\s?})+|['\\/"]+$/g,"")),n))},add_custom_rule:function(t,e){e===o&&Foundation.stylesheet?Foundation.stylesheet.insertRule(t,Foundation.stylesheet.cssRules.length):Foundation.media_queries[e]!==o&&Foundation.stylesheet.insertRule("@media "+Foundation.media_queries[e]+"{ "+t+" }",Foundation.stylesheet.cssRules.length)},image_loaded:function(t,e){var n=this,i=t.length;(0===i||function(t){for(var e=t.length-1;0<=e;e--)if(t.attr("height")===o)return!1;return!0}(t))&&e(t),t.each(function(){!function(t,e){function n(){e(t[0])}t.attr("src")?t[0].complete||4===t[0].readyState?n():function(){if(this.one("load",n),/MSIE (\d+\.\d+);/.test(navigator.userAgent)){var t=this.attr("src"),e=t.match(/\?/)?"&":"?";e+="random="+(new Date).getTime(),this.attr("src",t+e)}}.call(t):n()}(n.S(this),function(){0===(i-=1)&&e(t)})})},random_str:function(){return this.fidx||(this.fidx=0),this.prefix=this.prefix||[this.name||"F",(+new Date).toString(36)].join("-"),this.prefix+(this.fidx++).toString(36)},match:function(t){return l.matchMedia(t).matches},is_small_up:function(){return this.match(Foundation.media_queries.small)},is_medium_up:function(){return this.match(Foundation.media_queries.medium)},is_large_up:function(){return this.match(Foundation.media_queries.large)},is_xlarge_up:function(){return this.match(Foundation.media_queries.xlarge)},is_xxlarge_up:function(){return this.match(Foundation.media_queries.xxlarge)},is_small_only:function(){return!(this.is_medium_up()||this.is_large_up()||this.is_xlarge_up()||this.is_xxlarge_up())},is_medium_only:function(){return this.is_medium_up()&&!this.is_large_up()&&!this.is_xlarge_up()&&!this.is_xxlarge_up()},is_large_only:function(){return this.is_medium_up()&&this.is_large_up()&&!this.is_xlarge_up()&&!this.is_xxlarge_up()},is_xlarge_only:function(){return this.is_medium_up()&&this.is_large_up()&&this.is_xlarge_up()&&!this.is_xxlarge_up()},is_xxlarge_only:function(){return this.is_medium_up()&&this.is_large_up()&&this.is_xlarge_up()&&this.is_xxlarge_up()}}},u.fn.foundation=function(){var t=Array.prototype.slice.call(arguments,0);return this.each(function(){return Foundation.init.apply(Foundation,[this].concat(t)),this})}}(jQuery,window,window.document),function(i){var t=i("body");t.addClass("has-closed-modal");var n=["a[href]","area[href]",'input:not([disabled]):not([type="hidden"]):not([readonly])','select:not([disabled]):not([type="hidden"]):not([readonly])','textarea:not([disabled]):not([type="hidden"]):not([readonly])','button:not([disabled]):not([type="hidden"]):not([readonly])',"iframe","object","embed","*[tabindex]","*[contenteditable]"].join(),a=[];window.Modal=function(){function e(){_classCallCheck(this,e);var t={uniqueID:"modal-"+a.length};return arguments[0]&&"object"===_typeof(arguments[0])&&(this.options=i.extend(arguments[0],t)),this.isOpen=!1,this.$target=i(this.options.target),this.$site=i(".js-site"),this.$modalContent=i("#modal-content"),this.$modalOverlay=i("#modal-overlay"),this.$modalOverlay.on("click",this,function(t){t.data.close()}),this.$modalFrame=i("#modal-frame"),this.$modalFrame.on("click",this,function(t){var e=i(t.target),n=t.data;(e.is(".js-modal__frame")||e.is(".js-modal__close-button")||e.parents(".js-modal__close-button").length)&&n.close()}).on("keydown",this,function(t){var e=t.data;27==t.which&&(e.close(),t.preventDefault())}),a.push(this),arguments[1]&&"function"==typeof arguments[1]&&arguments[1].call(this),this}return _createClass(e,[{key:"getOptions",value:function(){return this.options}},{key:"isOpen",value:function(){return this.isOpen}},{key:"open",value:function(){this.$modalTriggerElement=i(document.activeElement),this.closeAll(),this.$placeholder=i("<div></div>").hide(),this.$target.after(this.$placeholder),this.detached=this.$target.detach(),this.$modalContent.append(this.detached),this.trigger("modal:open"),this.$site.attr("tabindex","-1").attr("aria-hidden",!0).find(n).attr("tabindex","-1"),t.removeClass("has-closed-modal").addClass("has-open-modal"),this.$modalFrame.removeAttr("aria-hidden"),this.$modalContent.attr("tabindex","0").focus(),this.isOpen=!0,this.trigger("modal:opened")}},{key:"close",value:function(){if(!this.isOpen)return!1;t.removeClass("has-open-modal").addClass("has-closed-modal"),this.$placeholder.replaceWith(this.$target),this.$modalContent.html(""),this.$modalFrame.attr("aria-hidden","true"),this.trigger("modal:close"),this.$site.removeAttr("tabindex").removeAttr("aria-hidden").find(n).removeAttr("tabindex"),this.$modalTriggerElement.focus(),this.$modalTriggerElement=null,this.isOpen=!1}},{key:"closeAll",value:function(){for(var t=0;t<a.length;t++)a[t].close()}},{key:"on",value:function(t,e){var n=t+"-"+this.options.uniqueID;return this.$modalContent.on(n,e),this}},{key:"trigger",value:function(t){var e=t+"-"+this.options.uniqueID;this.$modalContent.trigger(e,this)}}]),e}()}(jQuery);var PedUtils=function(){function t(){_classCallCheck(this,t)}return _createClass(t,null,[{key:"debounce",value:function(i,a,o){var s;return function(){var t=this,e=arguments,n=o&&!s;clearTimeout(s),s=setTimeout(function(){s=null,o||i.apply(t,e)},a||200),n&&i.apply(t,e)}}},{key:"throttle",value:function(t,e){var n=!1;return function(){n||(t.call(),n=!0,setTimeout(function(){n=!1},e))}}},{key:"removeHash",value:function(){history.pushState("",document.title,window.location.pathname+window.location.search)}},{key:"focusAtEnd",value:function(t){if(0<t.length){var e=t[0],n=e.value.length;(e.selectionStart||"0"==e.selectionStart)&&(e.selectionStart=n,e.selectionEnd=n,e.focus())}}}]),t}();function ScrollDepth(t,e,n){var r=jQuery,i=r(window),l=[];if(this.selector=t,this.label=e,this.percs=n,this.$element=r(this.selector),this.eventNamespace="scroll.depth"+this.label.toCamelCase().capFirst(),this.$element.length){var a=r.proxy(function(){var t,e,n={},i=this.percs,a=this.$element.offset().top,o=this.$element.height();i.sort(function(t,e){return t-e});for(var s=0;s<i.length;s++)switch(e=(t=i[s])+"%",t){case 0:n[e]=a;break;case 100:n[e]=o-5+a;break;default:n[e]=parseInt(o*(.01*t),10)+a}return n},this),o=r.proxy(function(o){var s;this.$element.length&&o>=this.$element.offset().top&&r.each(a(),r.proxy(function(t,e){var n,i,a;-1===r.inArray(t,l)&&e<=o&&(s=Math.round(parseFloat(t)),n=t,i=this.label,a=s,"function"==typeof window.ga&&window.ga("send","event","Scroll Depth",n,i,a,{nonInteraction:!0}),l.push(t))},this))},this);i.on(this.eventNamespace,PedUtils.throttle(r.proxy(function(){var t=window.innerHeight||i.height(),e=i.scrollTop()+t;l.length>=this.percs.length||!this.$element.length?i.off(this.eventNamespace):o(e)},this),750))}}function ShareButtons(o){if(this.result=!1,o(".js-share-buttons").length){var s=o(window),e=o("body"),r=o(".js-main-header");this.getCutoffs=function(){var t=o("#wpadminbar"),e=r.offset(),n=o(".js-main-footer").offset(),i=e?e.top+r.height():0,a=n?n.top-s.height():0;return 0<t.length&&(i-=t.height()),{top:i,bottom:a}},this.cutoffs=this.getCutoffs(),this.scroll=function(){var t=s.scrollTop();t>this.cutoffs.top&&t<this.cutoffs.bottom?e.addClass("has-sticky-share-buttons"):e.removeClass("has-sticky-share-buttons")},this.resize=function(){this.cutoffs=this.getCutoffs()},this.result=!0}}String.prototype.toCamelCase=function(){return this.replace(/\s(.)/g,function(t){return t.toUpperCase()}).replace(/\s/g,"").replace(/^(.)/,function(t){return t.toLowerCase()})},String.prototype.capFirst=function(){return this.charAt(0).toUpperCase()+this.slice(1)},jQuery(document).ready(function(n){var i=n("body");i.on("click keyup",".js-sitewide-search",function(t){if("keyup"!==t.type||13===t.which){i.toggleClass("is-search-open");var e=n(this).attr("for");n("#"+e).attr("tabindex",1).focus()}}),n(".js-search-form").on("click",".js-search-icon-close",function(t){t.preventDefault(),i.removeClass("is-search-open")})}),jQuery(document).ready(function(t){var e=t(window),n=new ShareButtons(t),i=n.result;if(!i)return!1;var a=t.proxy(n.scroll,n);e.on("scroll",PedUtils.throttle(a,50));var o=t.proxy(n.resize,n);e.on("resize",PedUtils.throttle(o,250));var s=o;"function"==typeof MutationObserver&&new MutationObserver(function(t){t.forEach(function(){s.call()})}).observe(document.body,{attributes:!1,childList:!0,characterData:!1});return i}),function(u){var t={init:function(){u(document).foundation(),u("html").removeClass("no-js").addClass("js"),this.showVideoControls=480<=u(window).width(),objectFitImages(".js-stream-item-img img",{watchMQ:!0}),this.bindEvents(),this.handleSubscriptionForms(),this.responsiveIframes(),this.disabledAnchors(),this.analyticsEventTracking(),this.scrollDepthTracking(),this.honeyPotHelper(),this.lazyLoad(),this.setupModals(),DonateForm(),PedUtils.focusAtEnd(u("#search-standalone-input"))},showVideoControls:!0,bindEvents:function(){var t=!1;u(window).resize(u.proxy(function(){t&&clearTimeout(t),t=setTimeout(u.proxy(function(){this.responsiveIframes()},this),30)},this))},handleSubscriptionForms:function(){u(".js-signup-email-form").on("submit",function(t){t.preventDefault();var n=u(this),i=n.find(".js-form-fields"),a=n.find(".js-form-submit"),o=n.find(".js-form-submit-text"),s=n.find(".js-fail-message"),r=n.closest(".js-modal"),e=a.width(),l=n.attr("action"),c=0<=l.indexOf("?")?"&":"?";l+=c+u.param({"ajax-request":1}),a.width(e),a.css("padding-left",0),a.css("padding-right",0),o.hide(),n.removeClass("is-failed"),n.addClass("is-loading"),r.length&&r.removeClass("has-failed-form"),u.post(l,n.serialize(),function(){if(n.find(".js-success-message").length){var t=n.find(".js-success-message-email"),e=n.find(".js-email-input").val();i.hide(),n.removeClass("is-loading"),n.addClass("is-success"),r.length&&r.addClass("has-successful-form"),e&&t.length&&t.text(e).addClass("u-font-weight--bold")}}).fail(function(t){var e=t.responseText;n.removeClass("is-loading"),n.addClass("is-failed"),r.length&&r.addClass("has-failed-form"),o.show(),s.length&&e.length?s.text(e):a.before(e)})})},responsiveIframes:function(){u(".pedestal-responsive").each(function(){var t=u(this),e=t.parent().width();window.self!==window.top&&(e=parent.innerWidth);var n=t.data("true-height")?t.data("true-height"):360,i=e/(t.data("true-width")?t.data("true-width"):640)*n;u(this).css("height",i+"px").css("width",e+"px")})},disabledAnchors:function(){u("a.disabled").click(function(t){t.preventDefault()})},analyticsEventTracking:function(){var o=!1;u("body").is(".js-debug-ga")&&(o=!0),("function"==typeof ga||o)&&u("body").on("click","a[data-ga-category]",function(t){var e=u(this),n=e.data("ga-category"),i=t.currentTarget.href,a=e.data("ga-label");if(o)return console.group("Google Analytics Event Data"),console.log("Category: ",n),console.log("Action: ",i),console.log("Label: ",a),console.groupEnd(),void t.preventDefault();ga("send","event",n,i,a)}).on("submit","form[data-ga-category]",function(t){var e=u(this),n=e.data("ga-category"),i=e.attr("action"),a=e.data("ga-label");if(o)return console.group("Google Analytics Event Data"),console.log("Category: ",n),console.log("Action: ",i),console.log("Label: ",a),console.groupEnd(),void t.preventDefault();ga("send","event",n,i,a)})},scrollDepthTracking:function(){new ScrollDepth(".js-original-content-body","Original Content Body",[0,50,100])},honeyPotHelper:function(){var t=(new Date).getFullYear();u(".js-pedestal-current-year-check").val(t)},lazyLoad:function(){var s=this.showVideoControls?1:0;u(".content-wrapper").on("click",".js-yt-placeholder-link",function(t){var e=u(this),n=e.data("youtube-id");if(n){var i=e.parents(".js-yt-placeholder"),a={autoplay:1,cc_load_policy:1,color:"white",controls:s,showinfo:0},o="<iframe ";o+='src="'+("https://www.youtube.com/embed/"+n+"?"+u.param(a))+'" ',o+='frameborder="0" ',o+="allowfullscreen ",o+="/>",i.append(o),e.fadeOut(750,function(){e.remove()}),t.preventDefault()}})},setupModals:function(){u(".js-modal-trigger").each(function(t,e){var n=u(e),i=n.data("modal-target");if(i){var a=!1;"#modal-signup-email-form"==i&&(a=function(){var t=this;-1!==window.location.href.indexOf("#subscribe")&&t.open(),window.addEventListener("hashchange",function(){"#subscribe"===location.hash&&t.open()})});var o=new Modal({target:i},a);if("#modal-signup-email-form"==i&&o.on("modal:close",function(){"#subscribe"===location.hash&&PedUtils.removeHash()}),"#site-header--with-nav-modal-menu"==i){var s=u(".js-modal__frame");o.on("modal:open",function(){s.addClass("modal--header-nav-modal")}),o.on("modal:close",function(){s.removeClass("modal--header-nav-modal")})}"#site-header-search-modal"==i&&o.on("modal:opened",function(){var t=u(".js-site-header--search-field");PedUtils.focusAtEnd(t)}),n.on("click",o,function(t){t.data.open(),t.preventDefault()})}})}};u(document).ready(function(){t.init()})}(jQuery);