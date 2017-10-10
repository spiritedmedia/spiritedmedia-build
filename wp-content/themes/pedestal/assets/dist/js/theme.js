"use strict";function ScrollDepth(t,e,n){function i(t,e,n){"function"==typeof window.ga&&window.ga("send","event","Scroll Depth",t,e,n,{nonInteraction:!0})}var s=jQuery,o=s(window),r=[];if(this.selector=t,this.label=e,this.percs=n,this.$element=s(this.selector),this.eventNamespace="scroll.depth"+this.label.toCamelCase().capFirst(),this.$element.length){var a=s.proxy(function(){var t,e,n={},i=this.percs,s=this.$element.offset().top,o=this.$element.height();i.sort(function(t,e){return t-e});for(var r=0;r<i.length;r++)switch(t=i[r],e=t+"%",t){case 0:n[e]=s;break;case 100:n[e]=o-5+s;break;default:n[e]=parseInt(o*(.01*t),10)+s}return n},this),l=s.proxy(function(t){if(this.$element.length&&t>=this.$element.offset().top){var e;s.each(a(),s.proxy(function(n,o){-1===s.inArray(n,r)&&t>=o&&(e=Math.round(parseFloat(n)),i(n,this.label,e),r.push(n))},this))}},this);o.on(this.eventNamespace,PedUtils.throttle(s.proxy(function(){var t=window.innerHeight||o.height(),e=o.scrollTop()+t;r.length>=this.percs.length||!this.$element.length?o.off(this.eventNamespace):l(e)},this),750))}}function ShareButtons(t){if(this.result=!1,t(".js-share-buttons").length){var e=t(window),n=t("body"),i=t(".js-main-header");this.getCutoffs=function(){var n=t("#wpadminbar"),s=i.offset(),o=t(".js-main-footer").offset(),r=s?s.top+i.height():0,a=o?o.top-e.height():0;return n.length>0&&(r-=n.height()),{top:r,bottom:a}},this.cutoffs=this.getCutoffs(),this.scroll=function(){var t=e.scrollTop();t>this.cutoffs.top&&t<this.cutoffs.bottom?n.addClass("has-sticky-share-buttons"):n.removeClass("has-sticky-share-buttons")},this.resize=function(){this.cutoffs=this.getCutoffs()},this.result=!0}}var _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},objectFitImages=function(){function t(t,e){return"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='"+t+"' height='"+e+"'%3E%3C/svg%3E"}function e(t){if(t.srcset&&!m&&window.picturefill){var e=window.picturefill._;t[e.ns]&&t[e.ns].evaled||e.fillImg(t,{reselect:!0}),t[e.ns].curSrc||(t[e.ns].supported=!1,e.fillImg(t,{reselect:!0})),t.currentSrc=t[e.ns].curSrc||t.src}}function n(t){for(var e,n=getComputedStyle(t).fontFamily,i={};null!==(e=u.exec(n));)i[e[1]]=e[2];return i}function i(e,n,i){var s=t(n||1,i||0);p.call(e,"src")!==s&&g.call(e,"src",s)}function s(t,e){t.naturalWidth?e(t):setTimeout(s,100,t,e)}function o(t){var o=n(t),a=t[l];if(o["object-fit"]=o["object-fit"]||"fill",!a.img){if("fill"===o["object-fit"])return;if(!a.skipTest&&f&&!o["object-position"])return}if(!a.img){a.img=new Image(t.width,t.height),a.img.srcset=p.call(t,"data-ofi-srcset")||t.srcset,a.img.src=p.call(t,"data-ofi-src")||t.src,g.call(t,"data-ofi-src",t.src),t.srcset&&g.call(t,"data-ofi-srcset",t.srcset),i(t,t.naturalWidth||t.width,t.naturalHeight||t.height),t.srcset&&(t.srcset="");try{r(t)}catch(t){window.console&&console.warn("https://bit.ly/ofi-old-browser")}}e(a.img),t.style.backgroundImage='url("'+(a.img.currentSrc||a.img.src).replace(/"/g,'\\"')+'")',t.style.backgroundPosition=o["object-position"]||"center",t.style.backgroundRepeat="no-repeat",t.style.backgroundOrigin="content-box",/scale-down/.test(o["object-fit"])?s(a.img,function(){a.img.naturalWidth>t.width||a.img.naturalHeight>t.height?t.style.backgroundSize="contain":t.style.backgroundSize="auto"}):t.style.backgroundSize=o["object-fit"].replace("none","auto").replace("fill","100% 100%"),s(a.img,function(e){i(t,e.naturalWidth,e.naturalHeight)})}function r(t){var e={get:function(e){return t[l].img[e||"src"]},set:function(e,n){return t[l].img[n||"src"]=e,g.call(t,"data-ofi-"+n,e),o(t),e}};Object.defineProperty(t,"src",e),Object.defineProperty(t,"currentSrc",{get:function(){return e.get("currentSrc")}}),Object.defineProperty(t,"srcset",{get:function(){return e.get("srcset")},set:function(t){return e.set(t,"srcset")}})}function a(t,e){var n=!y&&!t;if(e=e||{},t=t||"img",h&&!e.skipTest||!d)return!1;"img"===t?t=document.getElementsByTagName("img"):"string"==typeof t?t=document.querySelectorAll(t):"length"in t||(t=[t]);for(var i=0;i<t.length;i++)t[i][l]=t[i][l]||{skipTest:e.skipTest},o(t[i]);n&&(document.body.addEventListener("load",function(t){"IMG"===t.target.tagName&&a(t.target,{skipTest:e.skipTest})},!0),y=!0,t="img"),e.watchMQ&&window.addEventListener("resize",a.bind(null,t,{skipTest:e.skipTest}))}var l="bfred-it:object-fit-images",u=/(object-fit|object-position)\s*:\s*([-\w\s%]+)/g,c="undefined"==typeof Image?{style:{"object-position":1}}:new Image,f="object-fit"in c.style,h="object-position"in c.style,d="background-size"in c.style,m="string"==typeof c.currentSrc,p=c.getAttribute,g=c.setAttribute,y=!1;return a.supportsObjectFit=f,a.supportsObjectPosition=h,function(){function t(t,e){return t[l]&&t[l].img&&("src"===e||"srcset"===e)?t[l].img:t}h||(HTMLImageElement.prototype.getAttribute=function(e){return p.call(t(this,e),e)},HTMLImageElement.prototype.setAttribute=function(e,n){return g.call(t(this,e),e,String(n))})}(),a}();!function(t,e,n,i){function s(t){return("string"==typeof t||t instanceof String)&&(t=t.replace(/^['\\/"]+|(;\s?})+|['\\/"]+$/g,"")),t}function o(t){this.selector=t,this.query=""}!function(e){var n=t("head");n.prepend(t.map(e,function(t){if(0===n.has("."+t).length)return'<meta class="'+t+'" />'}))}(["foundation-mq-small","foundation-mq-small-only","foundation-mq-medium","foundation-mq-medium-only","foundation-mq-large","foundation-mq-large-only","foundation-mq-xlarge","foundation-mq-xlarge-only","foundation-mq-xxlarge","foundation-data-attribute-namespace"]),t(function(){"undefined"!=typeof FastClick&&void 0!==n.body&&FastClick.attach(n.body)});var r=function(e,i){if("string"==typeof e){if(i){var s;if(i.jquery){if(!(s=i[0]))return i}else s=i;return t(s.querySelectorAll(e))}return t(n.querySelectorAll(e))}return t(e,i)},a=function(t){var e=[];return t||e.push("data"),this.namespace.length>0&&e.push(this.namespace),e.push(this.name),e.join("-")},l=function(t){for(var e=t.split("-"),n=e.length,i=[];n--;)0!==n?i.push(e[n]):this.namespace.length>0?i.push(this.namespace,e[n]):i.push(e[n]);return i.reverse().join("-")},u=function(e,n){var i=this,s=function(){var s=r(this),o=!s.data(i.attr_name(!0)+"-init");s.data(i.attr_name(!0)+"-init",t.extend({},i.settings,n||e,i.data_options(s))),o&&i.events(this)};if(r(this.scope).is("["+this.attr_name()+"]")?s.call(this.scope):r("["+this.attr_name()+"]",this.scope).each(s),"string"==typeof e)return this[e].call(this,n)},c=function(t,e){function n(){e(t[0])}t.attr("src")?t[0].complete||4===t[0].readyState?n():function(){if(this.one("load",n),/MSIE (\d+\.\d+);/.test(navigator.userAgent)){var t=this.attr("src"),e=t.match(/\?/)?"&":"?";e+="random="+(new Date).getTime(),this.attr("src",t+e)}}.call(t):n()};e.matchMedia||(e.matchMedia=function(){var t=e.styleMedia||e.media;if(!t){var i=n.createElement("style"),s=n.getElementsByTagName("script")[0],o=null;i.type="text/css",i.id="matchmediajs-test",s.parentNode.insertBefore(i,s),o="getComputedStyle"in e&&e.getComputedStyle(i,null)||i.currentStyle,t={matchMedium:function(t){var e="@media "+t+"{ #matchmediajs-test { width: 1px; } }";return i.styleSheet?i.styleSheet.cssText=e:i.textContent=e,"1px"===o.width}}}return function(e){return{matches:t.matchMedium(e||"all"),media:e||"all"}}}()),function(t){function n(){i&&(r(n),l&&t.fx.tick())}for(var i,s=0,o=["webkit","moz"],r=e.requestAnimationFrame,a=e.cancelAnimationFrame,l=void 0!==t.fx;s<o.length&&!r;s++)r=e[o[s]+"RequestAnimationFrame"],a=a||e[o[s]+"CancelAnimationFrame"]||e[o[s]+"CancelRequestAnimationFrame"];r?(e.requestAnimationFrame=r,e.cancelAnimationFrame=a,l&&(t.fx.timer=function(e){e()&&t.timers.push(e)&&!i&&(i=!0,n())},t.fx.stop=function(){i=!1})):(e.requestAnimationFrame=function(t){var n=(new Date).getTime(),i=Math.max(0,16-(n-s)),o=e.setTimeout(function(){t(n+i)},i);return s=n+i,o},e.cancelAnimationFrame=function(t){clearTimeout(t)})}(t),o.prototype.toString=function(){return this.query||(this.query=r(this.selector).css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g,""))},e.Foundation={name:"Foundation",version:"5.5.3",media_queries:{small:new o(".foundation-mq-small"),"small-only":new o(".foundation-mq-small-only"),medium:new o(".foundation-mq-medium"),"medium-only":new o(".foundation-mq-medium-only"),large:new o(".foundation-mq-large"),"large-only":new o(".foundation-mq-large-only"),xlarge:new o(".foundation-mq-xlarge"),"xlarge-only":new o(".foundation-mq-xlarge-only"),xxlarge:new o(".foundation-mq-xxlarge")},stylesheet:t("<style></style>").appendTo("head")[0].sheet,global:{namespace:i},init:function(t,n,i,s,o){var a=[t,i,s,o],l=[];if(this.rtl=/rtl/i.test(r("html").attr("dir")),this.scope=t||this.scope,this.set_namespace(),n&&"string"==typeof n&&!/reflow/i.test(n))this.libs.hasOwnProperty(n)&&l.push(this.init_lib(n,a));else for(var u in this.libs)l.push(this.init_lib(u,n));return r(e).load(function(){r(e).trigger("resize.fndtn.clearing").trigger("resize.fndtn.dropdown").trigger("resize.fndtn.equalizer").trigger("resize.fndtn.interchange").trigger("resize.fndtn.joyride").trigger("resize.fndtn.magellan").trigger("resize.fndtn.topbar").trigger("resize.fndtn.slider")}),t},init_lib:function(e,n){return this.libs.hasOwnProperty(e)?(this.patch(this.libs[e]),n&&n.hasOwnProperty(e)?(void 0!==this.libs[e].settings?t.extend(!0,this.libs[e].settings,n[e]):void 0!==this.libs[e].defaults&&t.extend(!0,this.libs[e].defaults,n[e]),this.libs[e].init.apply(this.libs[e],[this.scope,n[e]])):(n=n instanceof Array?n:new Array(n),this.libs[e].init.apply(this.libs[e],n))):function(){}},patch:function(t){t.scope=this.scope,t.namespace=this.global.namespace,t.rtl=this.rtl,t.data_options=this.utils.data_options,t.attr_name=a,t.add_namespace=l,t.bindings=u,t.S=this.utils.S},inherit:function(t,e){for(var n=e.split(" "),i=n.length;i--;)this.utils.hasOwnProperty(n[i])&&(t[n[i]]=this.utils[n[i]])},set_namespace:function(){var e=this.global.namespace===i?t(".foundation-data-attribute-namespace").css("font-family"):this.global.namespace;this.global.namespace=e===i||/false/i.test(e)?"":e},libs:{},utils:{S:r,throttle:function(t,e){var n=null;return function(){var i=this,s=arguments;null==n&&(n=setTimeout(function(){t.apply(i,s),n=null},e))}},debounce:function(t,e,n){var i,s;return function(){var o=this,r=arguments,a=n&&!i;return clearTimeout(i),i=setTimeout(function(){i=null,n||(s=t.apply(o,r))},e),a&&(s=t.apply(o,r)),s}},data_options:function(e,n){function i(e){return"string"==typeof e?t.trim(e):e}n=n||"options";var s,o,r,a={},l=function(t){var e=Foundation.global.namespace;return e.length>0?t.data(e+"-"+n):t.data(n)}(e);if("object"===(void 0===l?"undefined":_typeof(l)))return l;for(s=(r=(l||":").split(";")).length;s--;)o=[(o=r[s].split(":"))[0],o.slice(1).join(":")],/true/i.test(o[1])&&(o[1]=!0),/false/i.test(o[1])&&(o[1]=!1),function(t){return!isNaN(t-0)&&null!==t&&""!==t&&!1!==t&&!0!==t}(o[1])&&(-1===o[1].indexOf(".")?o[1]=parseInt(o[1],10):o[1]=parseFloat(o[1])),2===o.length&&o[0].length>0&&(a[i(o[0])]=i(o[1]));return a},register_media:function(e,n){Foundation.media_queries[e]===i&&(t("head").append('<meta class="'+n+'"/>'),Foundation.media_queries[e]=s(t("."+n).css("font-family")))},add_custom_rule:function(t,e){e===i&&Foundation.stylesheet?Foundation.stylesheet.insertRule(t,Foundation.stylesheet.cssRules.length):Foundation.media_queries[e]!==i&&Foundation.stylesheet.insertRule("@media "+Foundation.media_queries[e]+"{ "+t+" }",Foundation.stylesheet.cssRules.length)},image_loaded:function(t,e){var n=this,s=t.length;(0===s||function(t){for(var e=t.length-1;e>=0;e--)if(t.attr("height")===i)return!1;return!0}(t))&&e(t),t.each(function(){c(n.S(this),function(){0===(s-=1)&&e(t)})})},random_str:function(){return this.fidx||(this.fidx=0),this.prefix=this.prefix||[this.name||"F",(+new Date).toString(36)].join("-"),this.prefix+(this.fidx++).toString(36)},match:function(t){return e.matchMedia(t).matches},is_small_up:function(){return this.match(Foundation.media_queries.small)},is_medium_up:function(){return this.match(Foundation.media_queries.medium)},is_large_up:function(){return this.match(Foundation.media_queries.large)},is_xlarge_up:function(){return this.match(Foundation.media_queries.xlarge)},is_xxlarge_up:function(){return this.match(Foundation.media_queries.xxlarge)},is_small_only:function(){return!(this.is_medium_up()||this.is_large_up()||this.is_xlarge_up()||this.is_xxlarge_up())},is_medium_only:function(){return this.is_medium_up()&&!this.is_large_up()&&!this.is_xlarge_up()&&!this.is_xxlarge_up()},is_large_only:function(){return this.is_medium_up()&&this.is_large_up()&&!this.is_xlarge_up()&&!this.is_xxlarge_up()},is_xlarge_only:function(){return this.is_medium_up()&&this.is_large_up()&&this.is_xlarge_up()&&!this.is_xxlarge_up()},is_xxlarge_only:function(){return this.is_medium_up()&&this.is_large_up()&&this.is_xlarge_up()&&this.is_xxlarge_up()}}},t.fn.foundation=function(){var t=Array.prototype.slice.call(arguments,0);return this.each(function(){return Foundation.init.apply(Foundation,[this].concat(t)),this})}}(jQuery,window,window.document),jQuery(document).ready(function(t){t(".js-follow-this-anchor").on("click",function(e){var n=t(this).attr("href").split("#")[1],i=t("#"+n).offset(),s=i?i.top:0;t("html, body").animate({scrollTop:s},750),t(".js-follow-this-email").focus(),window.location.hash=n,e.preventDefault()})});var PedUtils={debounce:function(t,e,n){var i;return function(){var s=this,o=arguments,r=n&&!i;clearTimeout(i),i=setTimeout(function(){i=null,n||t.apply(s,o)},e||200),r&&t.apply(s,o)}},throttle:function(t,e){var n=!1;return function(){n||(t.call(),n=!0,setTimeout(function(){n=!1},e))}}};String.prototype.toCamelCase=function(){return this.replace(/\s(.)/g,function(t){return t.toUpperCase()}).replace(/\s/g,"").replace(/^(.)/,function(t){return t.toLowerCase()})},String.prototype.capFirst=function(){return this.charAt(0).toUpperCase()+this.slice(1)},jQuery(document).ready(function(t){var e=t("body");e.on("click keyup",".js-sitewide-search-icon",function(n){if("keyup"!==n.type||13===n.which){e.toggleClass("is-search-open");var i=t(this).attr("for");t("#"+i).attr("tabindex",1).select()}}),t(".js-search-form").on("click",".js-search-icon-close",function(t){e.removeClass("is-search-open"),t.preventDefault()})}),jQuery(document).ready(function(t){var e=t(window),n=new ShareButtons(t),i=n.result;if(!i)return!1;var s=t.proxy(n.scroll,n);e.on("scroll",PedUtils.throttle(s,50));var o=t.proxy(n.resize,n);e.on("resize",PedUtils.throttle(o,250));var r=o;return"function"==typeof MutationObserver&&new MutationObserver(function(t){t.forEach(function(){r.call()})}).observe(document.body,{attributes:!1,childList:!0,characterData:!1}),i}),function(t){var e={init:function(){t(document).foundation(),t("html").removeClass("no-js").addClass("js"),objectFitImages(".js-stream-item-img img",{watchMQ:!0}),this.bindEvents(),this.handleSubscriptionForms(),this.responsiveIframes(),this.disabledAnchors(),this.analyticsEventTracking(),this.scrollDepthTracking(),this.honeyPotHelper(),this.lazyLoad()},bindEvents:function(){var e=!1;t(window).resize(t.proxy(function(){e&&clearTimeout(e),e=setTimeout(t.proxy(function(){this.responsiveIframes()},this),30)},this))},handleSubscriptionForms:function(){t([".js-follow-this-form-container","#subscribe-to-newsletter-page",".js-widget-signup-newsletter"].join(", ")).find("form").on("submit",function(e){e.preventDefault();var n=t(this),i=n.find(".js-form-fields"),s=n.find(".js-form-submit"),o=n.find(".js-form-submit-text"),r=n.find(".js-fail-message"),a=s.width(),l=n.attr("action");l+=(l.indexOf("?")>=0?"&":"?")+t.param({"ajax-request":1}),s.width(a),s.css("padding-left",0),s.css("padding-right",0),o.hide(),n.removeClass("is-failed"),n.addClass("is-loading"),t.post(l,n.serialize(),function(){if(n.find(".js-success-message").length){var t=n.find(".js-success-message-email"),e=n.find(".js-email-input").val();i.hide(),n.removeClass("is-loading"),n.addClass("is-success"),e&&t.length&&t.text(e).addClass("u-font-weight--bold")}}).fail(function(t){var e=t.responseText;n.removeClass("is-loading"),n.addClass("is-failed"),o.show(),r.length&&e.length?r.text(e):s.before(e)})})},responsiveIframes:function(){t(".pedestal-responsive").each(function(){var e=t(this),n=e.parent().width();window.self!==window.top&&(n=parent.innerWidth);var i=e.data("true-height")?e.data("true-height"):360,s=n/(e.data("true-width")?e.data("true-width"):640)*i;t(this).css("height",s+"px").css("width",n+"px")})},disabledAnchors:function(){t("a.disabled").click(function(t){t.preventDefault()})},analyticsEventTracking:function(){var e=!1;t("body").is(".js-debug-ga")&&(e=!0),("function"==typeof ga||e)&&t("body").on("click","a[data-ga-category]",function(n){var i=t(this),s=i.data("ga-category"),o=n.currentTarget.href,r=i.data("ga-label");if(e)return console.group("Google Analytics Event Data"),console.log("Category: ",s),console.log("Action: ",o),console.log("Label: ",r),console.groupEnd(),void n.preventDefault();ga("send","event",s,o,r)}).on("submit","form[data-ga-category]",function(n){var i=t(this),s=i.data("ga-category"),o=i.attr("action"),r=i.data("ga-label");if(e)return console.group("Google Analytics Event Data"),console.log("Category: ",s),console.log("Action: ",o),console.log("Label: ",r),console.groupEnd(),void n.preventDefault();ga("send","event",s,o,r)})},scrollDepthTracking:function(){new ScrollDepth(".js-original-content-body","Original Content Body",[0,50,100])},honeyPotHelper:function(){var e=(new Date).getFullYear();t(".js-pedestal-current-year-check").val(e)},lazyLoad:function(){t(".content-wrapper").on("click",".js-yt-placeholder-link",function(e){var n=t(this),i=n.data("youtube-id");if(i){var s="https://www.youtube.com/embed/",o="<iframe ";o+='src="'+(s+=i+"?showinfo=0&autoplay=1")+'" ',o+='frameborder="0" ',o+="allowfullscreen ",o+="/>",n.parents(".js-yt-placeholder").append(o),n.fadeOut(750,function(){n.remove()}),e.preventDefault()}})}};t(document).ready(function(){e.init()})}(jQuery);