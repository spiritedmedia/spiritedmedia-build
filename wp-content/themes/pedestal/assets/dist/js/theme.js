function ShareButtons(t){if(this.result=!1,t(".js-share-buttons").length){var e=t(window),i=t("body"),n=t(".js-main-header");this.getCutoffs=function(){var i=t("#wpadminbar"),s=n.offset(),a=t(".js-main-footer").offset(),r=s?s.top+n.height():0,o=a?a.top-e.height():0;return i.length>0&&(r-=i.height()),{top:r,bottom:o}},this.cutoffs=this.getCutoffs(),this.scroll=function(){var t=e.scrollTop();t>this.cutoffs.top&&t<this.cutoffs.bottom?i.addClass("has-sticky-share-buttons"):i.removeClass("has-sticky-share-buttons")},this.resize=function(){this.cutoffs=this.getCutoffs()},this.result=!0}}var objectFitImages=function(){"use strict";function t(t,e){return"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='"+t+"' height='"+e+"'%3E%3C/svg%3E"}function e(t){if(t.srcset&&!m&&window.picturefill){var e=window.picturefill._;t[e.ns]&&t[e.ns].evaled||e.fillImg(t,{reselect:!0}),t[e.ns].curSrc||(t[e.ns].supported=!1,e.fillImg(t,{reselect:!0})),t.currentSrc=t[e.ns].curSrc||t.src}}function i(t){for(var e,i=getComputedStyle(t).fontFamily,n={};null!==(e=u.exec(i));)n[e[1]]=e[2];return n}function n(e,i,n){var s=t(i||1,n||0);g.call(e,"src")!==s&&p.call(e,"src",s)}function s(t,e){t.naturalWidth?e(t):setTimeout(s,100,t,e)}function a(t){var a=i(t),o=t[l];if(a["object-fit"]=a["object-fit"]||"fill",!o.img){if("fill"===a["object-fit"])return;if(!o.skipTest&&f&&!a["object-position"])return}if(!o.img){o.img=new Image(t.width,t.height),o.img.srcset=g.call(t,"data-ofi-srcset")||t.srcset,o.img.src=g.call(t,"data-ofi-src")||t.src,p.call(t,"data-ofi-src",t.src),t.srcset&&p.call(t,"data-ofi-srcset",t.srcset),n(t,t.naturalWidth||t.width,t.naturalHeight||t.height),t.srcset&&(t.srcset="");try{r(t)}catch(t){window.console&&console.warn("https://bit.ly/ofi-old-browser")}}e(o.img),t.style.backgroundImage='url("'+(o.img.currentSrc||o.img.src).replace(/"/g,'\\"')+'")',t.style.backgroundPosition=a["object-position"]||"center",t.style.backgroundRepeat="no-repeat",t.style.backgroundOrigin="content-box",/scale-down/.test(a["object-fit"])?s(o.img,function(){o.img.naturalWidth>t.width||o.img.naturalHeight>t.height?t.style.backgroundSize="contain":t.style.backgroundSize="auto"}):t.style.backgroundSize=a["object-fit"].replace("none","auto").replace("fill","100% 100%"),s(o.img,function(e){n(t,e.naturalWidth,e.naturalHeight)})}function r(t){var e={get:function(e){return t[l].img[e||"src"]},set:function(e,i){return t[l].img[i||"src"]=e,p.call(t,"data-ofi-"+i,e),a(t),e}};Object.defineProperty(t,"src",e),Object.defineProperty(t,"currentSrc",{get:function(){return e.get("currentSrc")}}),Object.defineProperty(t,"srcset",{get:function(){return e.get("srcset")},set:function(t){return e.set(t,"srcset")}})}function o(t,e){var i=!y&&!t;if(e=e||{},t=t||"img",d&&!e.skipTest||!h)return!1;"img"===t?t=document.getElementsByTagName("img"):"string"==typeof t?t=document.querySelectorAll(t):"length"in t||(t=[t]);for(var n=0;n<t.length;n++)t[n][l]=t[n][l]||{skipTest:e.skipTest},a(t[n]);i&&(document.body.addEventListener("load",function(t){"IMG"===t.target.tagName&&o(t.target,{skipTest:e.skipTest})},!0),y=!0,t="img"),e.watchMQ&&window.addEventListener("resize",o.bind(null,t,{skipTest:e.skipTest}))}var l="bfred-it:object-fit-images",u=/(object-fit|object-position)\s*:\s*([-\w\s%]+)/g,c="undefined"==typeof Image?{style:{"object-position":1}}:new Image,f="object-fit"in c.style,d="object-position"in c.style,h="background-size"in c.style,m="string"==typeof c.currentSrc,g=c.getAttribute,p=c.setAttribute,y=!1;return o.supportsObjectFit=f,o.supportsObjectPosition=d,function(){function t(t,e){return t[l]&&t[l].img&&("src"===e||"srcset"===e)?t[l].img:t}d||(HTMLImageElement.prototype.getAttribute=function(e){return g.call(t(this,e),e)},HTMLImageElement.prototype.setAttribute=function(e,i){return p.call(t(this,e),e,String(i))})}(),o}();!function(t,e,i,n){"use strict";function s(t){return("string"==typeof t||t instanceof String)&&(t=t.replace(/^['\\/"]+|(;\s?})+|['\\/"]+$/g,"")),t}function a(t){this.selector=t,this.query=""}!function(e){var i=t("head");i.prepend(t.map(e,function(t){if(0===i.has("."+t).length)return'<meta class="'+t+'" />'}))}(["foundation-mq-small","foundation-mq-small-only","foundation-mq-medium","foundation-mq-medium-only","foundation-mq-large","foundation-mq-large-only","foundation-mq-xlarge","foundation-mq-xlarge-only","foundation-mq-xxlarge","foundation-data-attribute-namespace"]),t(function(){"undefined"!=typeof FastClick&&void 0!==i.body&&FastClick.attach(i.body)});var r=function(e,n){if("string"==typeof e){if(n){var s;if(n.jquery){if(!(s=n[0]))return n}else s=n;return t(s.querySelectorAll(e))}return t(i.querySelectorAll(e))}return t(e,n)},o=function(t){var e=[];return t||e.push("data"),this.namespace.length>0&&e.push(this.namespace),e.push(this.name),e.join("-")},l=function(t){for(var e=t.split("-"),i=e.length,n=[];i--;)0!==i?n.push(e[i]):this.namespace.length>0?n.push(this.namespace,e[i]):n.push(e[i]);return n.reverse().join("-")},u=function(e,i){var n=this,s=function(){var s=r(this),a=!s.data(n.attr_name(!0)+"-init");s.data(n.attr_name(!0)+"-init",t.extend({},n.settings,i||e,n.data_options(s))),a&&n.events(this)};if(r(this.scope).is("["+this.attr_name()+"]")?s.call(this.scope):r("["+this.attr_name()+"]",this.scope).each(s),"string"==typeof e)return this[e].call(this,i)},c=function(t,e){function i(){e(t[0])}t.attr("src")?t[0].complete||4===t[0].readyState?i():function(){if(this.one("load",i),/MSIE (\d+\.\d+);/.test(navigator.userAgent)){var t=this.attr("src"),e=t.match(/\?/)?"&":"?";e+="random="+(new Date).getTime(),this.attr("src",t+e)}}.call(t):i()};e.matchMedia||(e.matchMedia=function(){var t=e.styleMedia||e.media;if(!t){var n=i.createElement("style"),s=i.getElementsByTagName("script")[0],a=null;n.type="text/css",n.id="matchmediajs-test",s.parentNode.insertBefore(n,s),a="getComputedStyle"in e&&e.getComputedStyle(n,null)||n.currentStyle,t={matchMedium:function(t){var e="@media "+t+"{ #matchmediajs-test { width: 1px; } }";return n.styleSheet?n.styleSheet.cssText=e:n.textContent=e,"1px"===a.width}}}return function(e){return{matches:t.matchMedium(e||"all"),media:e||"all"}}}()),function(t){function i(){n&&(r(i),l&&t.fx.tick())}for(var n,s=0,a=["webkit","moz"],r=e.requestAnimationFrame,o=e.cancelAnimationFrame,l=void 0!==t.fx;s<a.length&&!r;s++)r=e[a[s]+"RequestAnimationFrame"],o=o||e[a[s]+"CancelAnimationFrame"]||e[a[s]+"CancelRequestAnimationFrame"];r?(e.requestAnimationFrame=r,e.cancelAnimationFrame=o,l&&(t.fx.timer=function(e){e()&&t.timers.push(e)&&!n&&(n=!0,i())},t.fx.stop=function(){n=!1})):(e.requestAnimationFrame=function(t){var i=(new Date).getTime(),n=Math.max(0,16-(i-s)),a=e.setTimeout(function(){t(i+n)},n);return s=i+n,a},e.cancelAnimationFrame=function(t){clearTimeout(t)})}(t),a.prototype.toString=function(){return this.query||(this.query=r(this.selector).css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g,""))},e.Foundation={name:"Foundation",version:"5.5.3",media_queries:{small:new a(".foundation-mq-small"),"small-only":new a(".foundation-mq-small-only"),medium:new a(".foundation-mq-medium"),"medium-only":new a(".foundation-mq-medium-only"),large:new a(".foundation-mq-large"),"large-only":new a(".foundation-mq-large-only"),xlarge:new a(".foundation-mq-xlarge"),"xlarge-only":new a(".foundation-mq-xlarge-only"),xxlarge:new a(".foundation-mq-xxlarge")},stylesheet:t("<style></style>").appendTo("head")[0].sheet,global:{namespace:n},init:function(t,i,n,s,a){var o=[t,n,s,a],l=[];if(this.rtl=/rtl/i.test(r("html").attr("dir")),this.scope=t||this.scope,this.set_namespace(),i&&"string"==typeof i&&!/reflow/i.test(i))this.libs.hasOwnProperty(i)&&l.push(this.init_lib(i,o));else for(var u in this.libs)l.push(this.init_lib(u,i));return r(e).load(function(){r(e).trigger("resize.fndtn.clearing").trigger("resize.fndtn.dropdown").trigger("resize.fndtn.equalizer").trigger("resize.fndtn.interchange").trigger("resize.fndtn.joyride").trigger("resize.fndtn.magellan").trigger("resize.fndtn.topbar").trigger("resize.fndtn.slider")}),t},init_lib:function(e,i){return this.libs.hasOwnProperty(e)?(this.patch(this.libs[e]),i&&i.hasOwnProperty(e)?(void 0!==this.libs[e].settings?t.extend(!0,this.libs[e].settings,i[e]):void 0!==this.libs[e].defaults&&t.extend(!0,this.libs[e].defaults,i[e]),this.libs[e].init.apply(this.libs[e],[this.scope,i[e]])):(i=i instanceof Array?i:new Array(i),this.libs[e].init.apply(this.libs[e],i))):function(){}},patch:function(t){t.scope=this.scope,t.namespace=this.global.namespace,t.rtl=this.rtl,t.data_options=this.utils.data_options,t.attr_name=o,t.add_namespace=l,t.bindings=u,t.S=this.utils.S},inherit:function(t,e){for(var i=e.split(" "),n=i.length;n--;)this.utils.hasOwnProperty(i[n])&&(t[i[n]]=this.utils[i[n]])},set_namespace:function(){var e=this.global.namespace===n?t(".foundation-data-attribute-namespace").css("font-family"):this.global.namespace;this.global.namespace=e===n||/false/i.test(e)?"":e},libs:{},utils:{S:r,throttle:function(t,e){var i=null;return function(){var n=this,s=arguments;null==i&&(i=setTimeout(function(){t.apply(n,s),i=null},e))}},debounce:function(t,e,i){var n,s;return function(){var a=this,r=arguments,o=i&&!n;return clearTimeout(n),n=setTimeout(function(){n=null,i||(s=t.apply(a,r))},e),o&&(s=t.apply(a,r)),s}},data_options:function(e,i){function n(e){return"string"==typeof e?t.trim(e):e}i=i||"options";var s,a,r,o={},l=function(t){var e=Foundation.global.namespace;return e.length>0?t.data(e+"-"+i):t.data(i)}(e);if("object"==typeof l)return l;for(s=(r=(l||":").split(";")).length;s--;)a=[(a=r[s].split(":"))[0],a.slice(1).join(":")],/true/i.test(a[1])&&(a[1]=!0),/false/i.test(a[1])&&(a[1]=!1),function(t){return!isNaN(t-0)&&null!==t&&""!==t&&!1!==t&&!0!==t}(a[1])&&(-1===a[1].indexOf(".")?a[1]=parseInt(a[1],10):a[1]=parseFloat(a[1])),2===a.length&&a[0].length>0&&(o[n(a[0])]=n(a[1]));return o},register_media:function(e,i){Foundation.media_queries[e]===n&&(t("head").append('<meta class="'+i+'"/>'),Foundation.media_queries[e]=s(t("."+i).css("font-family")))},add_custom_rule:function(t,e){e===n&&Foundation.stylesheet?Foundation.stylesheet.insertRule(t,Foundation.stylesheet.cssRules.length):Foundation.media_queries[e]!==n&&Foundation.stylesheet.insertRule("@media "+Foundation.media_queries[e]+"{ "+t+" }",Foundation.stylesheet.cssRules.length)},image_loaded:function(t,e){var i=this,s=t.length;(0===s||function(t){for(var e=t.length-1;e>=0;e--)if(t.attr("height")===n)return!1;return!0}(t))&&e(t),t.each(function(){c(i.S(this),function(){0===(s-=1)&&e(t)})})},random_str:function(){return this.fidx||(this.fidx=0),this.prefix=this.prefix||[this.name||"F",(+new Date).toString(36)].join("-"),this.prefix+(this.fidx++).toString(36)},match:function(t){return e.matchMedia(t).matches},is_small_up:function(){return this.match(Foundation.media_queries.small)},is_medium_up:function(){return this.match(Foundation.media_queries.medium)},is_large_up:function(){return this.match(Foundation.media_queries.large)},is_xlarge_up:function(){return this.match(Foundation.media_queries.xlarge)},is_xxlarge_up:function(){return this.match(Foundation.media_queries.xxlarge)},is_small_only:function(){return!(this.is_medium_up()||this.is_large_up()||this.is_xlarge_up()||this.is_xxlarge_up())},is_medium_only:function(){return this.is_medium_up()&&!this.is_large_up()&&!this.is_xlarge_up()&&!this.is_xxlarge_up()},is_large_only:function(){return this.is_medium_up()&&this.is_large_up()&&!this.is_xlarge_up()&&!this.is_xxlarge_up()},is_xlarge_only:function(){return this.is_medium_up()&&this.is_large_up()&&this.is_xlarge_up()&&!this.is_xxlarge_up()},is_xxlarge_only:function(){return this.is_medium_up()&&this.is_large_up()&&this.is_xlarge_up()&&this.is_xxlarge_up()}}},t.fn.foundation=function(){var t=Array.prototype.slice.call(arguments,0);return this.each(function(){return Foundation.init.apply(Foundation,[this].concat(t)),this})}}(jQuery,window,window.document),jQuery(document).ready(function(t){t(".js-follow-this-anchor").on("click",function(e){var i=t(this).attr("href").split("#")[1],n=t("#"+i).offset(),s=n?n.top:0;t("html, body").animate({scrollTop:s},750),t(".js-follow-this-email").focus(),window.location.hash=i,e.preventDefault()})});var PedUtils={debounce:function(t,e,i){var n;return function(){var s=this,a=arguments,r=i&&!n;clearTimeout(n),n=setTimeout(function(){n=null,i||t.apply(s,a)},e||200),r&&t.apply(s,a)}},throttle:function(t,e){var i=!1;return function(){i||(t.call(),i=!0,setTimeout(function(){i=!1},e))}}};jQuery(document).ready(function(t){function e(){var e=u.offset().top;t(window).scrollTop()<e||t("html, body").animate({scrollTop:e},250)}function i(){u.find(".js-search-input").addClass("is-loading"),l.addClass("is-loading"),f.show(),e()}function n(){u.find(".is-loading").removeClass("is-loading"),l.removeClass("is-loading"),f.removeAttr("style")}function s(){r.removeClass("is-search-open")}function a(i){if(!o)return i?void t.ajax({type:"GET",dataType:"html",url:i,beforeSend:function(){o=!0},success:function(e){var s="",a=(e=t.trim(e)).match(/<title>(.+)<\/title>/);a[1]&&(s=a[1]);var r=t(e),o=r.find(".js-stream").html();o=t.trim(o);var u=r.find(".js-search-tools").html();u=t.trim(u),c.html(o),t(".js-search-tools").html(u),l.addClass("is-active-search"),window.history.pushState({spiritedSearch:!0},s,i),"function"==typeof ga&&ga("send",{hitType:"pageview",title:s,location:i}),n()},error:function(){n()}}).always(function(){o=!1,e()}):(n(),!1)}var r=t("body"),o=!1,l=t(".js-main"),u=t(".js-search-form"),c=t(".js-stream"),f=t(".js-spinner");r.on("click keyup",".js-sitewide-search-icon",function(e){if("keyup"!==e.type||13===e.which){r.toggleClass("is-search-open");var i=t(this).attr("for");t("#"+i).attr("tabindex",1).select()}}),window.history&&history.pushState&&(l.on("click","a.js-pagination-item",function(e){if(l.is(".is-active-search")){var n=t(this);n.is(".js-is-disabled")||(i(),a(n.attr("href")),e.preventDefault())}}),u.on("submit",function(t){if(i(),c.length){var e=u.attr("action");if(e=e.replace(/\/?$/,"/"),e+="?"+u.serialize(),e=e.replace(/&orderby=relevance/gi,""),window.location.href===e)return setTimeout(function(){n()},450),void t.preventDefault();u.find(".js-search-field").blur(),a(e),t.preventDefault()}}),u.on("click",".js-search-icon-close",function(t){s(),t.preventDefault()}),t(".js-search-tools").on("change",".js-search-filters-radio",function(){var e=t(this);e.is("selected")||(e.closest(".js-search-filters").find(".is-active").removeClass("is-active js-btn"),e.parent().addClass("is-active js-btn"),u.submit())}),t(window).bind("popstate",function(t){t.originalEvent.state&&t.originalEvent.state.spiritedSearch&&window.location.href!==document.referrer&&(i(),window.location=window.location.href)}))}),jQuery(document).ready(function(t){var e=t(window),i=new ShareButtons(t),n=i.result;if(!n)return!1;var s=t.proxy(i.scroll,i);e.on("scroll",PedUtils.throttle(s,50));var a=t.proxy(i.resize,i);e.on("resize",PedUtils.throttle(a,250));var r=a;return"function"==typeof MutationObserver&&new MutationObserver(function(t){t.forEach(function(){r.call()})}).observe(document.body,{attributes:!1,childList:!0,characterData:!1}),n}),function(t){var e={init:function(){t(document).foundation(),t("html").removeClass("no-js").addClass("js"),objectFitImages(".js-stream-item-img img",{watchMQ:!0}),this.bindEvents(),this.handleSubscriptionForms(),this.responsiveIframes(),this.disabledAnchors(),this.analyticsEventTracking(),this.honeyPotHelper(),this.lazyLoad()},bindEvents:function(){var e=!1;t(window).resize(t.proxy(function(){e&&clearTimeout(e),e=setTimeout(t.proxy(function(){this.responsiveIframes()},this),30)},this))},handleSubscriptionForms:function(){t([".js-follow-this-form-container","#subscribe-to-newsletter-page",".widget_pedestal_signup_newsletter"].join(", ")).find("form").on("submit",function(e){e.preventDefault();var i=t(this),n=i.find(".js-form-submit"),s=t('<div data-alert class="alert-box alert js-form-alert"></div>'),a=n.width(),r=i.attr("action");r+=(r.indexOf("?")>=0?"&":"?")+t.param({"ajax-request":1}),n.width(a),n.css("padding-left",0),n.css("padding-right",0),i.find(".js-form-alert").remove(),i.addClass("is-loading"),t.post(r,i.serialize(),function(){i.find(".js-success-message").length&&(i.removeClass("is-loading"),i.find(".js-form-fields").hide(),i.find(".js-success-message").show())}).fail(function(t){i.removeClass("is-loading"),i.find(".js-fail-message").show(),s.text(t.responseText);var e=i.find(".js-error-message-text");e.length?e.after(s):i.prepend(s)})})},responsiveIframes:function(){t(".pedestal-responsive").each(function(){var e=t(this),i=e.parent().width();window.self!==window.top&&(i=parent.innerWidth);var n=e.data("true-height")?e.data("true-height"):360,s=i/(e.data("true-width")?e.data("true-width"):640)*n;t(this).css("height",s+"px").css("width",i+"px")})},disabledAnchors:function(){t("a.disabled").click(function(t){t.preventDefault()})},analyticsEventTracking:function(){var e=!1;t("body").is(".js-debug-ga")&&(e=!0),("undefined"!=typeof ga||e)&&t("body").on("click","a[data-ga-category]",function(i){var n=t(this),s=n.data("ga-category"),a=i.currentTarget.href,r=n.data("ga-label");if(e)return console.group("Google Analytics Event Data"),console.log("Category: ",s),console.log("Action: ",a),console.log("Label: ",r),console.groupEnd(),void i.preventDefault();ga("send","event",s,a,r)}).on("submit","form[data-ga-category]",function(i){var n=t(this),s=n.data("ga-category"),a=n.attr("action"),r=n.data("ga-label");if(e)return console.group("Google Analytics Event Data"),console.log("Category: ",s),console.log("Action: ",a),console.log("Label: ",r),console.groupEnd(),void i.preventDefault();ga("send","event",s,a,r)})},honeyPotHelper:function(){var e=(new Date).getFullYear();t(".js-pedestal-current-year-check").val(e)},lazyLoad:function(){t(".content-wrapper").on("click",".js-yt-placeholder-link",function(e){var i=t(this),n=i.data("youtube-id");if(n){var s="https://www.youtube.com/embed/",a="<iframe ";a+='src="'+(s+=n+"?showinfo=0&autoplay=1")+'" ',a+='frameborder="0" ',a+="allowfullscreen ",a+="/>",i.parents(".js-yt-placeholder").append(a),i.fadeOut(750,function(){i.remove()}),e.preventDefault()}})}};t(document).ready(function(){e.init()})}(jQuery);