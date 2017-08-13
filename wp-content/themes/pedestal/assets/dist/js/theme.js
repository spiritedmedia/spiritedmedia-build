"use strict";function ScrollDepth(t,e,i){function n(t,e,i){void 0!==window.ga&&window.ga("send","event","Scroll Depth",t,e,i,{nonInteraction:!0})}var s=jQuery,o=s(window),r=[];if(this.selector=t,this.label=e,this.percs=i,this.$element=s(this.selector),this.eventNamespace="scroll.depth"+this.label.toCamelCase().capFirst(),this.$element.length){var a=s.proxy(function(){var t,e,i={},n=this.percs,s=this.$element.offset().top,o=this.$element.height();n.sort(function(t,e){return t-e});for(var r=0;r<n.length;r++)switch(t=n[r],e=t+"%",t){case 0:i[e]=s;break;case 100:i[e]=o-5+s;break;default:i[e]=parseInt(o*(.01*t),10)+s}return i},this),l=s.proxy(function(t){if(this.$element.length&&t>=this.$element.offset().top){var e;s.each(a(),s.proxy(function(i,o){-1===s.inArray(i,r)&&t>=o&&(e=Math.round(parseFloat(i)),n(i,this.label,e),r.push(i))},this))}},this);o.on(this.eventNamespace,PedUtils.throttle(s.proxy(function(){var t=window.innerHeight||o.height(),e=o.scrollTop()+t;r.length>=this.percs.length||!this.$element.length?o.off(this.eventNamespace):l(e)},this),750))}}function ShareButtons(t){if(this.result=!1,t(".js-share-buttons").length){var e=t(window),i=t("body"),n=t(".js-main-header");this.getCutoffs=function(){var i=t("#wpadminbar"),s=n.offset(),o=t(".js-main-footer").offset(),r=s?s.top+n.height():0,a=o?o.top-e.height():0;return i.length>0&&(r-=i.height()),{top:r,bottom:a}},this.cutoffs=this.getCutoffs(),this.scroll=function(){var t=e.scrollTop();t>this.cutoffs.top&&t<this.cutoffs.bottom?i.addClass("has-sticky-share-buttons"):i.removeClass("has-sticky-share-buttons")},this.resize=function(){this.cutoffs=this.getCutoffs()},this.result=!0}}var _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},objectFitImages=function(){function t(t,e){return"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='"+t+"' height='"+e+"'%3E%3C/svg%3E"}function e(t){if(t.srcset&&!m&&window.picturefill){var e=window.picturefill._;t[e.ns]&&t[e.ns].evaled||e.fillImg(t,{reselect:!0}),t[e.ns].curSrc||(t[e.ns].supported=!1,e.fillImg(t,{reselect:!0})),t.currentSrc=t[e.ns].curSrc||t.src}}function i(t){for(var e,i=getComputedStyle(t).fontFamily,n={};null!==(e=c.exec(i));)n[e[1]]=e[2];return n}function n(e,i,n){var s=t(i||1,n||0);p.call(e,"src")!==s&&g.call(e,"src",s)}function s(t,e){t.naturalWidth?e(t):setTimeout(s,100,t,e)}function o(t){var o=i(t),a=t[l];if(o["object-fit"]=o["object-fit"]||"fill",!a.img){if("fill"===o["object-fit"])return;if(!a.skipTest&&f&&!o["object-position"])return}if(!a.img){a.img=new Image(t.width,t.height),a.img.srcset=p.call(t,"data-ofi-srcset")||t.srcset,a.img.src=p.call(t,"data-ofi-src")||t.src,g.call(t,"data-ofi-src",t.src),t.srcset&&g.call(t,"data-ofi-srcset",t.srcset),n(t,t.naturalWidth||t.width,t.naturalHeight||t.height),t.srcset&&(t.srcset="");try{r(t)}catch(t){window.console&&console.warn("https://bit.ly/ofi-old-browser")}}e(a.img),t.style.backgroundImage='url("'+(a.img.currentSrc||a.img.src).replace(/"/g,'\\"')+'")',t.style.backgroundPosition=o["object-position"]||"center",t.style.backgroundRepeat="no-repeat",t.style.backgroundOrigin="content-box",/scale-down/.test(o["object-fit"])?s(a.img,function(){a.img.naturalWidth>t.width||a.img.naturalHeight>t.height?t.style.backgroundSize="contain":t.style.backgroundSize="auto"}):t.style.backgroundSize=o["object-fit"].replace("none","auto").replace("fill","100% 100%"),s(a.img,function(e){n(t,e.naturalWidth,e.naturalHeight)})}function r(t){var e={get:function(e){return t[l].img[e||"src"]},set:function(e,i){return t[l].img[i||"src"]=e,g.call(t,"data-ofi-"+i,e),o(t),e}};Object.defineProperty(t,"src",e),Object.defineProperty(t,"currentSrc",{get:function(){return e.get("currentSrc")}}),Object.defineProperty(t,"srcset",{get:function(){return e.get("srcset")},set:function(t){return e.set(t,"srcset")}})}function a(t,e){var i=!y&&!t;if(e=e||{},t=t||"img",h&&!e.skipTest||!d)return!1;"img"===t?t=document.getElementsByTagName("img"):"string"==typeof t?t=document.querySelectorAll(t):"length"in t||(t=[t]);for(var n=0;n<t.length;n++)t[n][l]=t[n][l]||{skipTest:e.skipTest},o(t[n]);i&&(document.body.addEventListener("load",function(t){"IMG"===t.target.tagName&&a(t.target,{skipTest:e.skipTest})},!0),y=!0,t="img"),e.watchMQ&&window.addEventListener("resize",a.bind(null,t,{skipTest:e.skipTest}))}var l="bfred-it:object-fit-images",c=/(object-fit|object-position)\s*:\s*([-\w\s%]+)/g,u="undefined"==typeof Image?{style:{"object-position":1}}:new Image,f="object-fit"in u.style,h="object-position"in u.style,d="background-size"in u.style,m="string"==typeof u.currentSrc,p=u.getAttribute,g=u.setAttribute,y=!1;return a.supportsObjectFit=f,a.supportsObjectPosition=h,function(){function t(t,e){return t[l]&&t[l].img&&("src"===e||"srcset"===e)?t[l].img:t}h||(HTMLImageElement.prototype.getAttribute=function(e){return p.call(t(this,e),e)},HTMLImageElement.prototype.setAttribute=function(e,i){return g.call(t(this,e),e,String(i))})}(),a}();!function(t,e,i,n){function s(t){return("string"==typeof t||t instanceof String)&&(t=t.replace(/^['\\/"]+|(;\s?})+|['\\/"]+$/g,"")),t}function o(t){this.selector=t,this.query=""}!function(e){var i=t("head");i.prepend(t.map(e,function(t){if(0===i.has("."+t).length)return'<meta class="'+t+'" />'}))}(["foundation-mq-small","foundation-mq-small-only","foundation-mq-medium","foundation-mq-medium-only","foundation-mq-large","foundation-mq-large-only","foundation-mq-xlarge","foundation-mq-xlarge-only","foundation-mq-xxlarge","foundation-data-attribute-namespace"]),t(function(){"undefined"!=typeof FastClick&&void 0!==i.body&&FastClick.attach(i.body)});var r=function(e,n){if("string"==typeof e){if(n){var s;if(n.jquery){if(!(s=n[0]))return n}else s=n;return t(s.querySelectorAll(e))}return t(i.querySelectorAll(e))}return t(e,n)},a=function(t){var e=[];return t||e.push("data"),this.namespace.length>0&&e.push(this.namespace),e.push(this.name),e.join("-")},l=function(t){for(var e=t.split("-"),i=e.length,n=[];i--;)0!==i?n.push(e[i]):this.namespace.length>0?n.push(this.namespace,e[i]):n.push(e[i]);return n.reverse().join("-")},c=function(e,i){var n=this,s=function(){var s=r(this),o=!s.data(n.attr_name(!0)+"-init");s.data(n.attr_name(!0)+"-init",t.extend({},n.settings,i||e,n.data_options(s))),o&&n.events(this)};if(r(this.scope).is("["+this.attr_name()+"]")?s.call(this.scope):r("["+this.attr_name()+"]",this.scope).each(s),"string"==typeof e)return this[e].call(this,i)},u=function(t,e){function i(){e(t[0])}t.attr("src")?t[0].complete||4===t[0].readyState?i():function(){if(this.one("load",i),/MSIE (\d+\.\d+);/.test(navigator.userAgent)){var t=this.attr("src"),e=t.match(/\?/)?"&":"?";e+="random="+(new Date).getTime(),this.attr("src",t+e)}}.call(t):i()};e.matchMedia||(e.matchMedia=function(){var t=e.styleMedia||e.media;if(!t){var n=i.createElement("style"),s=i.getElementsByTagName("script")[0],o=null;n.type="text/css",n.id="matchmediajs-test",s.parentNode.insertBefore(n,s),o="getComputedStyle"in e&&e.getComputedStyle(n,null)||n.currentStyle,t={matchMedium:function(t){var e="@media "+t+"{ #matchmediajs-test { width: 1px; } }";return n.styleSheet?n.styleSheet.cssText=e:n.textContent=e,"1px"===o.width}}}return function(e){return{matches:t.matchMedium(e||"all"),media:e||"all"}}}()),function(t){function i(){n&&(r(i),l&&t.fx.tick())}for(var n,s=0,o=["webkit","moz"],r=e.requestAnimationFrame,a=e.cancelAnimationFrame,l=void 0!==t.fx;s<o.length&&!r;s++)r=e[o[s]+"RequestAnimationFrame"],a=a||e[o[s]+"CancelAnimationFrame"]||e[o[s]+"CancelRequestAnimationFrame"];r?(e.requestAnimationFrame=r,e.cancelAnimationFrame=a,l&&(t.fx.timer=function(e){e()&&t.timers.push(e)&&!n&&(n=!0,i())},t.fx.stop=function(){n=!1})):(e.requestAnimationFrame=function(t){var i=(new Date).getTime(),n=Math.max(0,16-(i-s)),o=e.setTimeout(function(){t(i+n)},n);return s=i+n,o},e.cancelAnimationFrame=function(t){clearTimeout(t)})}(t),o.prototype.toString=function(){return this.query||(this.query=r(this.selector).css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g,""))},e.Foundation={name:"Foundation",version:"5.5.3",media_queries:{small:new o(".foundation-mq-small"),"small-only":new o(".foundation-mq-small-only"),medium:new o(".foundation-mq-medium"),"medium-only":new o(".foundation-mq-medium-only"),large:new o(".foundation-mq-large"),"large-only":new o(".foundation-mq-large-only"),xlarge:new o(".foundation-mq-xlarge"),"xlarge-only":new o(".foundation-mq-xlarge-only"),xxlarge:new o(".foundation-mq-xxlarge")},stylesheet:t("<style></style>").appendTo("head")[0].sheet,global:{namespace:n},init:function(t,i,n,s,o){var a=[t,n,s,o],l=[];if(this.rtl=/rtl/i.test(r("html").attr("dir")),this.scope=t||this.scope,this.set_namespace(),i&&"string"==typeof i&&!/reflow/i.test(i))this.libs.hasOwnProperty(i)&&l.push(this.init_lib(i,a));else for(var c in this.libs)l.push(this.init_lib(c,i));return r(e).load(function(){r(e).trigger("resize.fndtn.clearing").trigger("resize.fndtn.dropdown").trigger("resize.fndtn.equalizer").trigger("resize.fndtn.interchange").trigger("resize.fndtn.joyride").trigger("resize.fndtn.magellan").trigger("resize.fndtn.topbar").trigger("resize.fndtn.slider")}),t},init_lib:function(e,i){return this.libs.hasOwnProperty(e)?(this.patch(this.libs[e]),i&&i.hasOwnProperty(e)?(void 0!==this.libs[e].settings?t.extend(!0,this.libs[e].settings,i[e]):void 0!==this.libs[e].defaults&&t.extend(!0,this.libs[e].defaults,i[e]),this.libs[e].init.apply(this.libs[e],[this.scope,i[e]])):(i=i instanceof Array?i:new Array(i),this.libs[e].init.apply(this.libs[e],i))):function(){}},patch:function(t){t.scope=this.scope,t.namespace=this.global.namespace,t.rtl=this.rtl,t.data_options=this.utils.data_options,t.attr_name=a,t.add_namespace=l,t.bindings=c,t.S=this.utils.S},inherit:function(t,e){for(var i=e.split(" "),n=i.length;n--;)this.utils.hasOwnProperty(i[n])&&(t[i[n]]=this.utils[i[n]])},set_namespace:function(){var e=this.global.namespace===n?t(".foundation-data-attribute-namespace").css("font-family"):this.global.namespace;this.global.namespace=e===n||/false/i.test(e)?"":e},libs:{},utils:{S:r,throttle:function(t,e){var i=null;return function(){var n=this,s=arguments;null==i&&(i=setTimeout(function(){t.apply(n,s),i=null},e))}},debounce:function(t,e,i){var n,s;return function(){var o=this,r=arguments,a=i&&!n;return clearTimeout(n),n=setTimeout(function(){n=null,i||(s=t.apply(o,r))},e),a&&(s=t.apply(o,r)),s}},data_options:function(e,i){function n(e){return"string"==typeof e?t.trim(e):e}i=i||"options";var s,o,r,a={},l=function(t){var e=Foundation.global.namespace;return e.length>0?t.data(e+"-"+i):t.data(i)}(e);if("object"===(void 0===l?"undefined":_typeof(l)))return l;for(s=(r=(l||":").split(";")).length;s--;)o=[(o=r[s].split(":"))[0],o.slice(1).join(":")],/true/i.test(o[1])&&(o[1]=!0),/false/i.test(o[1])&&(o[1]=!1),function(t){return!isNaN(t-0)&&null!==t&&""!==t&&!1!==t&&!0!==t}(o[1])&&(-1===o[1].indexOf(".")?o[1]=parseInt(o[1],10):o[1]=parseFloat(o[1])),2===o.length&&o[0].length>0&&(a[n(o[0])]=n(o[1]));return a},register_media:function(e,i){Foundation.media_queries[e]===n&&(t("head").append('<meta class="'+i+'"/>'),Foundation.media_queries[e]=s(t("."+i).css("font-family")))},add_custom_rule:function(t,e){e===n&&Foundation.stylesheet?Foundation.stylesheet.insertRule(t,Foundation.stylesheet.cssRules.length):Foundation.media_queries[e]!==n&&Foundation.stylesheet.insertRule("@media "+Foundation.media_queries[e]+"{ "+t+" }",Foundation.stylesheet.cssRules.length)},image_loaded:function(t,e){var i=this,s=t.length;(0===s||function(t){for(var e=t.length-1;e>=0;e--)if(t.attr("height")===n)return!1;return!0}(t))&&e(t),t.each(function(){u(i.S(this),function(){0===(s-=1)&&e(t)})})},random_str:function(){return this.fidx||(this.fidx=0),this.prefix=this.prefix||[this.name||"F",(+new Date).toString(36)].join("-"),this.prefix+(this.fidx++).toString(36)},match:function(t){return e.matchMedia(t).matches},is_small_up:function(){return this.match(Foundation.media_queries.small)},is_medium_up:function(){return this.match(Foundation.media_queries.medium)},is_large_up:function(){return this.match(Foundation.media_queries.large)},is_xlarge_up:function(){return this.match(Foundation.media_queries.xlarge)},is_xxlarge_up:function(){return this.match(Foundation.media_queries.xxlarge)},is_small_only:function(){return!(this.is_medium_up()||this.is_large_up()||this.is_xlarge_up()||this.is_xxlarge_up())},is_medium_only:function(){return this.is_medium_up()&&!this.is_large_up()&&!this.is_xlarge_up()&&!this.is_xxlarge_up()},is_large_only:function(){return this.is_medium_up()&&this.is_large_up()&&!this.is_xlarge_up()&&!this.is_xxlarge_up()},is_xlarge_only:function(){return this.is_medium_up()&&this.is_large_up()&&this.is_xlarge_up()&&!this.is_xxlarge_up()},is_xxlarge_only:function(){return this.is_medium_up()&&this.is_large_up()&&this.is_xlarge_up()&&this.is_xxlarge_up()}}},t.fn.foundation=function(){var t=Array.prototype.slice.call(arguments,0);return this.each(function(){return Foundation.init.apply(Foundation,[this].concat(t)),this})}}(jQuery,window,window.document),jQuery(document).ready(function(t){t(".js-follow-this-anchor").on("click",function(e){var i=t(this).attr("href").split("#")[1],n=t("#"+i).offset(),s=n?n.top:0;t("html, body").animate({scrollTop:s},750),t(".js-follow-this-email").focus(),window.location.hash=i,e.preventDefault()})});var PedUtils={debounce:function(t,e,i){var n;return function(){var s=this,o=arguments,r=i&&!n;clearTimeout(n),n=setTimeout(function(){n=null,i||t.apply(s,o)},e||200),r&&t.apply(s,o)}},throttle:function(t,e){var i=!1;return function(){i||(t.call(),i=!0,setTimeout(function(){i=!1},e))}}};String.prototype.toCamelCase=function(){return this.replace(/\s(.)/g,function(t){return t.toUpperCase()}).replace(/\s/g,"").replace(/^(.)/,function(t){return t.toLowerCase()})},String.prototype.capFirst=function(){return this.charAt(0).toUpperCase()+this.slice(1)},jQuery(document).ready(function(t){function e(){var e=c.offset().top;t(window).scrollTop()<e||t("html, body").animate({scrollTop:e},250)}function i(){c.find(".js-search-input").addClass("is-loading"),l.addClass("is-loading"),f.show(),e()}function n(){c.find(".is-loading").removeClass("is-loading"),l.removeClass("is-loading"),f.removeAttr("style")}function s(){r.removeClass("is-search-open")}function o(i){if(!a)return i?void t.ajax({type:"GET",dataType:"html",url:i,beforeSend:function(){a=!0},success:function(e){var s="",o=(e=t.trim(e)).match(/<title>(.+)<\/title>/);o[1]&&(s=o[1]);var r=t(e),a=r.find(".js-stream").html();a=t.trim(a);var c=r.find(".js-search-tools").html();c=t.trim(c),u.html(a),t(".js-search-tools").html(c),l.addClass("is-active-search"),window.history.pushState({spiritedSearch:!0},s,i),"function"==typeof ga&&ga("send",{hitType:"pageview",title:s,location:i}),n()},error:function(){n()}}).always(function(){a=!1,e()}):(n(),!1)}var r=t("body"),a=!1,l=t(".js-main"),c=t(".js-search-form"),u=t(".js-stream"),f=t(".js-spinner");r.on("click keyup",".js-sitewide-search-icon",function(e){if("keyup"!==e.type||13===e.which){r.toggleClass("is-search-open");var i=t(this).attr("for");t("#"+i).attr("tabindex",1).select()}}),window.history&&history.pushState&&(l.on("click","a.js-pagination-item",function(e){if(l.is(".is-active-search")){var n=t(this);n.is(".js-is-disabled")||(i(),o(n.attr("href")),e.preventDefault())}}),c.on("submit",function(t){if(i(),u.length){var e=c.attr("action");if(e=e.replace(/\/?$/,"/"),e+="?"+c.serialize(),e=e.replace(/&orderby=relevance/gi,""),window.location.href===e)return setTimeout(function(){n()},450),void t.preventDefault();c.find(".js-search-field").blur(),o(e),t.preventDefault()}}),c.on("click",".js-search-icon-close",function(t){s(),t.preventDefault()}),t(".js-search-tools").on("change",".js-search-filters-radio",function(){var e=t(this);e.is("selected")||(e.closest(".js-search-filters").find(".is-active").removeClass("is-active js-btn"),e.parent().addClass("is-active js-btn"),c.submit())}),t(window).bind("popstate",function(t){t.originalEvent.state&&t.originalEvent.state.spiritedSearch&&window.location.href!==document.referrer&&(i(),window.location=window.location.href)}))}),jQuery(document).ready(function(t){var e=t(window),i=new ShareButtons(t),n=i.result;if(!n)return!1;var s=t.proxy(i.scroll,i);e.on("scroll",PedUtils.throttle(s,50));var o=t.proxy(i.resize,i);e.on("resize",PedUtils.throttle(o,250));var r=o;return"function"==typeof MutationObserver&&new MutationObserver(function(t){t.forEach(function(){r.call()})}).observe(document.body,{attributes:!1,childList:!0,characterData:!1}),n}),function(t){var e={init:function(){t(document).foundation(),t("html").removeClass("no-js").addClass("js"),objectFitImages(".js-stream-item-img img",{watchMQ:!0}),this.bindEvents(),this.handleSubscriptionForms(),this.responsiveIframes(),this.disabledAnchors(),this.analyticsEventTracking(),this.scrollDepthTracking(),this.honeyPotHelper(),this.lazyLoad()},bindEvents:function(){var e=!1;t(window).resize(t.proxy(function(){e&&clearTimeout(e),e=setTimeout(t.proxy(function(){this.responsiveIframes()},this),30)},this))},handleSubscriptionForms:function(){t([".js-follow-this-form-container","#subscribe-to-newsletter-page",".widget_pedestal_signup_newsletter"].join(", ")).find("form").on("submit",function(e){e.preventDefault();var i=t(this),n=i.find(".js-form-fields"),s=i.find(".js-form-submit"),o=i.find(".js-fail-message"),r=s.width(),a=i.attr("action");a+=(a.indexOf("?")>=0?"&":"?")+t.param({"ajax-request":1}),s.width(r),s.css("padding-left",0),s.css("padding-right",0),i.removeClass("is-failed"),i.addClass("is-loading"),t.post(a,i.serialize(),function(){i.find(".js-success-message").length&&(n.hide(),i.removeClass("is-loading"),i.addClass("is-success"))}).fail(function(t){var e=t.responseText;i.removeClass("is-loading"),i.addClass("is-failed"),o.length&&e.length?o.text(e):s.before(e)})})},responsiveIframes:function(){t(".pedestal-responsive").each(function(){var e=t(this),i=e.parent().width();window.self!==window.top&&(i=parent.innerWidth);var n=e.data("true-height")?e.data("true-height"):360,s=i/(e.data("true-width")?e.data("true-width"):640)*n;t(this).css("height",s+"px").css("width",i+"px")})},disabledAnchors:function(){t("a.disabled").click(function(t){t.preventDefault()})},analyticsEventTracking:function(){var e=!1;t("body").is(".js-debug-ga")&&(e=!0),("undefined"!=typeof ga||e)&&t("body").on("click","a[data-ga-category]",function(i){var n=t(this),s=n.data("ga-category"),o=i.currentTarget.href,r=n.data("ga-label");if(e)return console.group("Google Analytics Event Data"),console.log("Category: ",s),console.log("Action: ",o),console.log("Label: ",r),console.groupEnd(),void i.preventDefault();ga("send","event",s,o,r)}).on("submit","form[data-ga-category]",function(i){var n=t(this),s=n.data("ga-category"),o=n.attr("action"),r=n.data("ga-label");if(e)return console.group("Google Analytics Event Data"),console.log("Category: ",s),console.log("Action: ",o),console.log("Label: ",r),console.groupEnd(),void i.preventDefault();ga("send","event",s,o,r)})},scrollDepthTracking:function(){new ScrollDepth(".js-original-content-body","Original Content Body",[0,50,100])},honeyPotHelper:function(){var e=(new Date).getFullYear();t(".js-pedestal-current-year-check").val(e)},lazyLoad:function(){t(".content-wrapper").on("click",".js-yt-placeholder-link",function(e){var i=t(this),n=i.data("youtube-id");if(n){var s="https://www.youtube.com/embed/",o="<iframe ";o+='src="'+(s+=n+"?showinfo=0&autoplay=1")+'" ',o+='frameborder="0" ',o+="allowfullscreen ",o+="/>",i.parents(".js-yt-placeholder").append(o),i.fadeOut(750,function(){i.remove()}),e.preventDefault()}})}};t(document).ready(function(){e.init()})}(jQuery);