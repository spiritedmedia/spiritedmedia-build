!function(a,b,c,d){"use strict";function e(a){return("string"==typeof a||a instanceof String)&&(a=a.replace(/^['\\/"]+|(;\s?})+|['\\/"]+$/g,"")),a}function f(a){this.selector=a,this.query=""}var g=function(b){var c=a("head");c.prepend(a.map(b,function(a){if(0===c.has("."+a).length)return'<meta class="'+a+'" />'}))};g(["foundation-mq-small","foundation-mq-small-only","foundation-mq-medium","foundation-mq-medium-only","foundation-mq-large","foundation-mq-large-only","foundation-mq-xlarge","foundation-mq-xlarge-only","foundation-mq-xxlarge","foundation-data-attribute-namespace"]),a(function(){"undefined"!=typeof FastClick&&"undefined"!=typeof c.body&&FastClick.attach(c.body)});var h=function(b,d){if("string"==typeof b){if(d){var e;if(d.jquery){if(e=d[0],!e)return d}else e=d;return a(e.querySelectorAll(b))}return a(c.querySelectorAll(b))}return a(b,d)},i=function(a){var b=[];return a||b.push("data"),this.namespace.length>0&&b.push(this.namespace),b.push(this.name),b.join("-")},j=function(a){for(var b=a.split("-"),c=b.length,d=[];c--;)0!==c?d.push(b[c]):this.namespace.length>0?d.push(this.namespace,b[c]):d.push(b[c]);return d.reverse().join("-")},k=function(b,c){var d=this,e=function(){var e=h(this),f=!e.data(d.attr_name(!0)+"-init");e.data(d.attr_name(!0)+"-init",a.extend({},d.settings,c||b,d.data_options(e))),f&&d.events(this)};if(h(this.scope).is("["+this.attr_name()+"]")?e.call(this.scope):h("["+this.attr_name()+"]",this.scope).each(e),"string"==typeof b)return this[b].call(this,c)},l=function(a,b){function c(){b(a[0])}function d(){if(this.one("load",c),/MSIE (\d+\.\d+);/.test(navigator.userAgent)){var a=this.attr("src"),b=a.match(/\?/)?"&":"?";b+="random="+(new Date).getTime(),this.attr("src",a+b)}}return a.attr("src")?void(a[0].complete||4===a[0].readyState?c():d.call(a)):void c()};b.matchMedia||(b.matchMedia=function(){var a=b.styleMedia||b.media;if(!a){var d=c.createElement("style"),e=c.getElementsByTagName("script")[0],f=null;d.type="text/css",d.id="matchmediajs-test",e.parentNode.insertBefore(d,e),f="getComputedStyle"in b&&b.getComputedStyle(d,null)||d.currentStyle,a={matchMedium:function(a){var b="@media "+a+"{ #matchmediajs-test { width: 1px; } }";return d.styleSheet?d.styleSheet.cssText=b:d.textContent=b,"1px"===f.width}}}return function(b){return{matches:a.matchMedium(b||"all"),media:b||"all"}}}()),function(a){function c(){d&&(g(c),i&&a.fx.tick())}for(var d,e=0,f=["webkit","moz"],g=b.requestAnimationFrame,h=b.cancelAnimationFrame,i="undefined"!=typeof a.fx;e<f.length&&!g;e++)g=b[f[e]+"RequestAnimationFrame"],h=h||b[f[e]+"CancelAnimationFrame"]||b[f[e]+"CancelRequestAnimationFrame"];g?(b.requestAnimationFrame=g,b.cancelAnimationFrame=h,i&&(a.fx.timer=function(b){b()&&a.timers.push(b)&&!d&&(d=!0,c())},a.fx.stop=function(){d=!1})):(b.requestAnimationFrame=function(a){var c=(new Date).getTime(),d=Math.max(0,16-(c-e)),f=b.setTimeout(function(){a(c+d)},d);return e=c+d,f},b.cancelAnimationFrame=function(a){clearTimeout(a)})}(a),f.prototype.toString=function(){return this.query||(this.query=h(this.selector).css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g,""))},b.Foundation={name:"Foundation",version:"5.5.3",media_queries:{small:new f(".foundation-mq-small"),"small-only":new f(".foundation-mq-small-only"),medium:new f(".foundation-mq-medium"),"medium-only":new f(".foundation-mq-medium-only"),large:new f(".foundation-mq-large"),"large-only":new f(".foundation-mq-large-only"),xlarge:new f(".foundation-mq-xlarge"),"xlarge-only":new f(".foundation-mq-xlarge-only"),xxlarge:new f(".foundation-mq-xxlarge")},stylesheet:a("<style></style>").appendTo("head")[0].sheet,global:{namespace:d},init:function(a,c,d,e,f){var g=[a,d,e,f],i=[];if(this.rtl=/rtl/i.test(h("html").attr("dir")),this.scope=a||this.scope,this.set_namespace(),c&&"string"==typeof c&&!/reflow/i.test(c))this.libs.hasOwnProperty(c)&&i.push(this.init_lib(c,g));else for(var j in this.libs)i.push(this.init_lib(j,c));return h(b).load(function(){h(b).trigger("resize.fndtn.clearing").trigger("resize.fndtn.dropdown").trigger("resize.fndtn.equalizer").trigger("resize.fndtn.interchange").trigger("resize.fndtn.joyride").trigger("resize.fndtn.magellan").trigger("resize.fndtn.topbar").trigger("resize.fndtn.slider")}),a},init_lib:function(b,c){return this.libs.hasOwnProperty(b)?(this.patch(this.libs[b]),c&&c.hasOwnProperty(b)?("undefined"!=typeof this.libs[b].settings?a.extend(!0,this.libs[b].settings,c[b]):"undefined"!=typeof this.libs[b].defaults&&a.extend(!0,this.libs[b].defaults,c[b]),this.libs[b].init.apply(this.libs[b],[this.scope,c[b]])):(c=c instanceof Array?c:new Array(c),this.libs[b].init.apply(this.libs[b],c))):function(){}},patch:function(a){a.scope=this.scope,a.namespace=this.global.namespace,a.rtl=this.rtl,a.data_options=this.utils.data_options,a.attr_name=i,a.add_namespace=j,a.bindings=k,a.S=this.utils.S},inherit:function(a,b){for(var c=b.split(" "),d=c.length;d--;)this.utils.hasOwnProperty(c[d])&&(a[c[d]]=this.utils[c[d]])},set_namespace:function(){var b=this.global.namespace===d?a(".foundation-data-attribute-namespace").css("font-family"):this.global.namespace;this.global.namespace=b===d||/false/i.test(b)?"":b},libs:{},utils:{S:h,throttle:function(a,b){var c=null;return function(){var d=this,e=arguments;null==c&&(c=setTimeout(function(){a.apply(d,e),c=null},b))}},debounce:function(a,b,c){var d,e;return function(){var f=this,g=arguments,h=function(){d=null,c||(e=a.apply(f,g))},i=c&&!d;return clearTimeout(d),d=setTimeout(h,b),i&&(e=a.apply(f,g)),e}},data_options:function(b,c){function d(a){return!isNaN(a-0)&&null!==a&&""!==a&&a!==!1&&a!==!0}function e(b){return"string"==typeof b?a.trim(b):b}c=c||"options";var f,g,h,i={},j=function(a){var b=Foundation.global.namespace;return b.length>0?a.data(b+"-"+c):a.data(c)},k=j(b);if("object"==typeof k)return k;for(h=(k||":").split(";"),f=h.length;f--;)g=h[f].split(":"),g=[g[0],g.slice(1).join(":")],/true/i.test(g[1])&&(g[1]=!0),/false/i.test(g[1])&&(g[1]=!1),d(g[1])&&(g[1].indexOf(".")===-1?g[1]=parseInt(g[1],10):g[1]=parseFloat(g[1])),2===g.length&&g[0].length>0&&(i[e(g[0])]=e(g[1]));return i},register_media:function(b,c){Foundation.media_queries[b]===d&&(a("head").append('<meta class="'+c+'"/>'),Foundation.media_queries[b]=e(a("."+c).css("font-family")))},add_custom_rule:function(a,b){if(b===d&&Foundation.stylesheet)Foundation.stylesheet.insertRule(a,Foundation.stylesheet.cssRules.length);else{var c=Foundation.media_queries[b];c!==d&&Foundation.stylesheet.insertRule("@media "+Foundation.media_queries[b]+"{ "+a+" }",Foundation.stylesheet.cssRules.length)}},image_loaded:function(a,b){function c(a){for(var b=a.length,c=b-1;c>=0;c--)if(a.attr("height")===d)return!1;return!0}var e=this,f=a.length;(0===f||c(a))&&b(a),a.each(function(){l(e.S(this),function(){f-=1,0===f&&b(a)})})},random_str:function(){return this.fidx||(this.fidx=0),this.prefix=this.prefix||[this.name||"F",(+new Date).toString(36)].join("-"),this.prefix+(this.fidx++).toString(36)},match:function(a){return b.matchMedia(a).matches},is_small_up:function(){return this.match(Foundation.media_queries.small)},is_medium_up:function(){return this.match(Foundation.media_queries.medium)},is_large_up:function(){return this.match(Foundation.media_queries.large)},is_xlarge_up:function(){return this.match(Foundation.media_queries.xlarge)},is_xxlarge_up:function(){return this.match(Foundation.media_queries.xxlarge)},is_small_only:function(){return!(this.is_medium_up()||this.is_large_up()||this.is_xlarge_up()||this.is_xxlarge_up())},is_medium_only:function(){return this.is_medium_up()&&!this.is_large_up()&&!this.is_xlarge_up()&&!this.is_xxlarge_up()},is_large_only:function(){return this.is_medium_up()&&this.is_large_up()&&!this.is_xlarge_up()&&!this.is_xxlarge_up()},is_xlarge_only:function(){return this.is_medium_up()&&this.is_large_up()&&this.is_xlarge_up()&&!this.is_xxlarge_up()},is_xxlarge_only:function(){return this.is_medium_up()&&this.is_large_up()&&this.is_xlarge_up()&&this.is_xxlarge_up()}}},a.fn.foundation=function(){var a=Array.prototype.slice.call(arguments,0);return this.each(function(){return Foundation.init.apply(Foundation,[this].concat(a)),this})}}(jQuery,window,window.document),function(a,b,c,d){"use strict";Foundation.libs.offcanvas={name:"offcanvas",version:"5.5.3",settings:{open_method:"move",close_on_click:!1},init:function(a,b,c){this.bindings(b,c)},events:function(){var b=this,c=b.S,d="",e="",f="",g="",h="";"move"===this.settings.open_method?(d="move-",e="right",f="left",g="top",h="bottom"):"overlap_single"===this.settings.open_method?(d="offcanvas-overlap-",e="right",f="left",g="top",h="bottom"):"overlap"===this.settings.open_method&&(d="offcanvas-overlap"),c(this.scope).off(".offcanvas").on("click.fndtn.offcanvas",".left-off-canvas-toggle",function(f){b.click_toggle_class(f,d+e),"overlap"!==b.settings.open_method&&c(".left-submenu").removeClass(d+e),a(".left-off-canvas-toggle").attr("aria-expanded","true")}).on("click.fndtn.offcanvas",".left-off-canvas-menu a",function(f){var g=b.get_settings(f),h=c(this).parent();!g.close_on_click||h.hasClass("has-submenu")||h.hasClass("back")?c(this).parent().hasClass("has-submenu")?(f.preventDefault(),c(this).siblings(".left-submenu").toggleClass(d+e)):h.hasClass("back")&&(f.preventDefault(),h.parent().removeClass(d+e)):(b.hide.call(b,d+e,b.get_wrapper(f)),h.parent().removeClass(d+e)),a(".left-off-canvas-toggle").attr("aria-expanded","true")}).on("click.fndtn.offcanvas",".right-off-canvas-toggle",function(e){b.click_toggle_class(e,d+f),"overlap"!==b.settings.open_method&&c(".right-submenu").removeClass(d+f),a(".right-off-canvas-toggle").attr("aria-expanded","true")}).on("click.fndtn.offcanvas",".right-off-canvas-menu a",function(e){var g=b.get_settings(e),h=c(this).parent();!g.close_on_click||h.hasClass("has-submenu")||h.hasClass("back")?c(this).parent().hasClass("has-submenu")?(e.preventDefault(),c(this).siblings(".right-submenu").toggleClass(d+f)):h.hasClass("back")&&(e.preventDefault(),h.parent().removeClass(d+f)):(b.hide.call(b,d+f,b.get_wrapper(e)),h.parent().removeClass(d+f)),a(".right-off-canvas-toggle").attr("aria-expanded","true")}).on("click.fndtn.offcanvas",".top-off-canvas-toggle",function(e){b.click_toggle_class(e,d+h),"overlap"!==b.settings.open_method&&c(".top-submenu").removeClass(d+h),a(".top-off-canvas-toggle").attr("aria-expanded","true")}).on("click.fndtn.offcanvas",".top-off-canvas-menu a",function(e){var f=b.get_settings(e),g=c(this).parent();!f.close_on_click||g.hasClass("has-submenu")||g.hasClass("back")?c(this).parent().hasClass("has-submenu")?(e.preventDefault(),c(this).siblings(".top-submenu").toggleClass(d+h)):g.hasClass("back")&&(e.preventDefault(),g.parent().removeClass(d+h)):(b.hide.call(b,d+h,b.get_wrapper(e)),g.parent().removeClass(d+h)),a(".top-off-canvas-toggle").attr("aria-expanded","true")}).on("click.fndtn.offcanvas",".bottom-off-canvas-toggle",function(e){b.click_toggle_class(e,d+g),"overlap"!==b.settings.open_method&&c(".bottom-submenu").removeClass(d+g),a(".bottom-off-canvas-toggle").attr("aria-expanded","true")}).on("click.fndtn.offcanvas",".bottom-off-canvas-menu a",function(e){var f=b.get_settings(e),h=c(this).parent();!f.close_on_click||h.hasClass("has-submenu")||h.hasClass("back")?c(this).parent().hasClass("has-submenu")?(e.preventDefault(),c(this).siblings(".bottom-submenu").toggleClass(d+g)):h.hasClass("back")&&(e.preventDefault(),h.parent().removeClass(d+g)):(b.hide.call(b,d+g,b.get_wrapper(e)),h.parent().removeClass(d+g)),a(".bottom-off-canvas-toggle").attr("aria-expanded","true")}).on("click.fndtn.offcanvas",".exit-off-canvas",function(g){b.click_remove_class(g,d+f),c(".right-submenu").removeClass(d+f),e&&(b.click_remove_class(g,d+e),c(".left-submenu").removeClass(d+f)),a(".right-off-canvas-toggle").attr("aria-expanded","true")}).on("click.fndtn.offcanvas",".exit-off-canvas",function(c){b.click_remove_class(c,d+f),a(".left-off-canvas-toggle").attr("aria-expanded","false"),e&&(b.click_remove_class(c,d+e),a(".right-off-canvas-toggle").attr("aria-expanded","false"))}).on("click.fndtn.offcanvas",".exit-off-canvas",function(e){b.click_remove_class(e,d+g),c(".bottom-submenu").removeClass(d+g),h&&(b.click_remove_class(e,d+h),c(".top-submenu").removeClass(d+g)),a(".bottom-off-canvas-toggle").attr("aria-expanded","true")}).on("click.fndtn.offcanvas",".exit-off-canvas",function(c){b.click_remove_class(c,d+g),a(".top-off-canvas-toggle").attr("aria-expanded","false"),h&&(b.click_remove_class(c,d+h),a(".bottom-off-canvas-toggle").attr("aria-expanded","false"))})},toggle:function(a,b){b=b||this.get_wrapper(),b.is("."+a)?this.hide(a,b):this.show(a,b)},show:function(a,b){b=b||this.get_wrapper(),b.trigger("open.fndtn.offcanvas"),b.addClass(a)},hide:function(a,b){b=b||this.get_wrapper(),b.trigger("close.fndtn.offcanvas"),b.removeClass(a)},click_toggle_class:function(a,b){a.preventDefault();var c=this.get_wrapper(a);this.toggle(b,c)},click_remove_class:function(a,b){a.preventDefault();var c=this.get_wrapper(a);this.hide(b,c)},get_settings:function(a){var b=this.S(a.target).closest("["+this.attr_name()+"]");return b.data(this.attr_name(!0)+"-init")||this.settings},get_wrapper:function(a){var b=this.S(a?a.target:this.scope).closest(".off-canvas-wrap");return 0===b.length&&(b=this.S(".off-canvas-wrap")),b},reflow:function(){}}}(jQuery,window,window.document),function(a,b,c,d){"use strict";function e(a){var b=/fade/i.test(a),c=/pop/i.test(a);return{animate:b||c,pop:c,fade:b}}var f=[];Foundation.libs.reveal={name:"reveal",version:"5.5.3",locked:!1,settings:{animation:"fadeAndPop",animation_speed:250,close_on_background_click:!0,close_on_esc:!0,dismiss_modal_class:"close-reveal-modal",multiple_opened:!1,bg_class:"reveal-modal-bg",root_element:"body",open:function(){},opened:function(){},close:function(){},closed:function(){},on_ajax_error:a.noop,bg:a(".reveal-modal-bg"),css:{open:{opacity:0,visibility:"visible",display:"block"},close:{opacity:1,visibility:"hidden",display:"none"}}},init:function(b,c,d){a.extend(!0,this.settings,c,d),this.bindings(c,d)},events:function(a){var b=this,d=b.S;return d(this.scope).off(".reveal").on("click.fndtn.reveal","["+this.add_namespace("data-reveal-id")+"]:not([disabled])",function(a){if(a.preventDefault(),!b.locked){var c=d(this),e=c.data(b.data_attr("reveal-ajax")),f=c.data(b.data_attr("reveal-replace-content"));if(b.locked=!0,"undefined"==typeof e)b.open.call(b,c);else{var g=e===!0?c.attr("href"):e;b.open.call(b,c,{url:g},{replaceContentSel:f})}}}),d(c).on("click.fndtn.reveal",this.close_targets(),function(a){if(a.preventDefault(),!b.locked){var c=d("["+b.attr_name()+"].open").data(b.attr_name(!0)+"-init")||b.settings,e=d(a.target)[0]===d("."+c.bg_class)[0];if(e){if(!c.close_on_background_click)return;a.stopPropagation()}b.locked=!0,b.close.call(b,e?d("["+b.attr_name()+"].open:not(.toback)"):d(this).closest("["+b.attr_name()+"]"))}}),d("["+b.attr_name()+"]",this.scope).length>0?d(this.scope).on("open.fndtn.reveal",this.settings.open).on("opened.fndtn.reveal",this.settings.opened).on("opened.fndtn.reveal",this.open_video).on("close.fndtn.reveal",this.settings.close).on("closed.fndtn.reveal",this.settings.closed).on("closed.fndtn.reveal",this.close_video):d(this.scope).on("open.fndtn.reveal","["+b.attr_name()+"]",this.settings.open).on("opened.fndtn.reveal","["+b.attr_name()+"]",this.settings.opened).on("opened.fndtn.reveal","["+b.attr_name()+"]",this.open_video).on("close.fndtn.reveal","["+b.attr_name()+"]",this.settings.close).on("closed.fndtn.reveal","["+b.attr_name()+"]",this.settings.closed).on("closed.fndtn.reveal","["+b.attr_name()+"]",this.close_video),!0},key_up_on:function(a){var b=this;return b.S("body").off("keyup.fndtn.reveal").on("keyup.fndtn.reveal",function(a){var c=b.S("["+b.attr_name()+"].open"),d=c.data(b.attr_name(!0)+"-init")||b.settings;d&&27===a.which&&d.close_on_esc&&!b.locked&&b.close.call(b,c)}),!0},key_up_off:function(a){return this.S("body").off("keyup.fndtn.reveal"),!0},open:function(c,e){var g,h=this;c?"undefined"!=typeof c.selector?g=h.S("#"+c.data(h.data_attr("reveal-id"))).first():(g=h.S(this.scope),e=c):g=h.S(this.scope);var i=g.data(h.attr_name(!0)+"-init");if(i=i||this.settings,g.hasClass("open")&&c!==d&&c.attr("data-reveal-id")==g.attr("id"))return h.close(g);if(!g.hasClass("open")){var j=h.S("["+h.attr_name()+"].open");"undefined"==typeof g.data("css-top")&&g.data("css-top",parseInt(g.css("top"),10)).data("offset",this.cache_offset(g)),g.attr("tabindex","0").attr("aria-hidden","false"),this.key_up_on(g),g.on("open.fndtn.reveal",function(a){"fndtn.reveal"!==a.namespace}),g.on("open.fndtn.reveal").trigger("open.fndtn.reveal"),j.length<1&&this.toggle_bg(g,!0),"string"==typeof e&&(e={url:e});var k=function(){j.length>0&&(i.multiple_opened?h.to_back(j):h.hide(j,i.css.close)),i.multiple_opened&&f.push(g),h.show(g,i.css.open)};if("undefined"!=typeof e&&e.url){var l="undefined"!=typeof e.success?e.success:null;a.extend(e,{success:function(b,c,d){if(a.isFunction(l)){var e=l(b,c,d);"string"==typeof e&&(b=e)}"undefined"!=typeof options&&"undefined"!=typeof options.replaceContentSel?g.find(options.replaceContentSel).html(b):g.html(b),h.S(g).foundation("section","reflow"),h.S(g).children().foundation(),k()}}),i.on_ajax_error!==a.noop&&a.extend(e,{error:i.on_ajax_error}),a.ajax(e)}else k()}h.S(b).trigger("resize")},close:function(b){var b=b&&b.length?b:this.S(this.scope),c=this.S("["+this.attr_name()+"].open"),d=b.data(this.attr_name(!0)+"-init")||this.settings,e=this;if(c.length>0)if(b.removeAttr("tabindex","0").attr("aria-hidden","true"),this.locked=!0,this.key_up_off(b),b.trigger("close.fndtn.reveal"),(d.multiple_opened&&1===c.length||!d.multiple_opened||b.length>1)&&(e.toggle_bg(b,!1),e.to_front(b)),d.multiple_opened){var g=b.is(":not(.toback)");e.hide(b,d.css.close,d),g?f.pop():f=a.grep(f,function(a){var c=a[0]===b[0];return c&&e.to_front(b),!c}),f.length>0&&e.to_front(f[f.length-1])}else e.hide(c,d.css.close,d)},close_targets:function(){var a="."+this.settings.dismiss_modal_class;return this.settings.close_on_background_click?a+", ."+this.settings.bg_class:a},toggle_bg:function(b,c){0===this.S("."+this.settings.bg_class).length&&(this.settings.bg=a("<div />",{class:this.settings.bg_class}).appendTo("body").hide());var e=this.settings.bg.filter(":visible").length>0;c!=e&&((c==d?e:!c)?this.hide(this.settings.bg):this.show(this.settings.bg))},show:function(c,d){if(d){var f=c.data(this.attr_name(!0)+"-init")||this.settings,g=f.root_element,h=this;if(0===c.parent(g).length){var i=c.wrap('<div style="display: none;" />').parent();c.on("closed.fndtn.reveal.wrapped",function(){c.detach().appendTo(i),c.unwrap().unbind("closed.fndtn.reveal.wrapped")}),c.detach().appendTo(g)}var j=e(f.animation);if(j.animate||(this.locked=!1),j.pop){d.top=a(b).scrollTop()-c.data("offset")+"px";var k={top:a(b).scrollTop()+c.data("css-top")+"px",opacity:1};return setTimeout(function(){return c.css(d).animate(k,f.animation_speed,"linear",function(){h.locked=!1,c.trigger("opened.fndtn.reveal")}).addClass("open")},f.animation_speed/2)}if(d.top=a(b).scrollTop()+c.data("css-top")+"px",j.fade){var k={opacity:1};return setTimeout(function(){return c.css(d).animate(k,f.animation_speed,"linear",function(){h.locked=!1,c.trigger("opened.fndtn.reveal")}).addClass("open")},f.animation_speed/2)}return c.css(d).show().css({opacity:1}).addClass("open").trigger("opened.fndtn.reveal")}var f=this.settings;return e(f.animation).fade?c.fadeIn(f.animation_speed/2):(this.locked=!1,c.show())},to_back:function(a){a.addClass("toback")},to_front:function(a){a.removeClass("toback")},hide:function(c,d){if(d){var f=c.data(this.attr_name(!0)+"-init"),g=this;f=f||this.settings;var h=e(f.animation);if(h.animate||(this.locked=!1),h.pop){var i={top:-a(b).scrollTop()-c.data("offset")+"px",opacity:0};return setTimeout(function(){return c.animate(i,f.animation_speed,"linear",function(){g.locked=!1,c.css(d).trigger("closed.fndtn.reveal")}).removeClass("open")},f.animation_speed/2)}if(h.fade){var i={opacity:0};return setTimeout(function(){return c.animate(i,f.animation_speed,"linear",function(){g.locked=!1,c.css(d).trigger("closed.fndtn.reveal")}).removeClass("open")},f.animation_speed/2)}return c.hide().css(d).removeClass("open").trigger("closed.fndtn.reveal")}var f=this.settings;return e(f.animation).fade?c.fadeOut(f.animation_speed/2):c.hide()},close_video:function(b){var c=a(".flex-video",b.target),d=a("iframe",c);d.length>0&&(d.attr("data-src",d[0].src),d.attr("src",d.attr("src")),c.hide())},open_video:function(b){var c=a(".flex-video",b.target),e=c.find("iframe");if(e.length>0){var f=e.attr("data-src");if("string"==typeof f)e[0].src=e.attr("data-src");else{var g=e[0].src;e[0].src=d,e[0].src=g}c.show()}},data_attr:function(a){return this.namespace.length>0?this.namespace+"-"+a:a},cache_offset:function(a){var b=a.show().height()+parseInt(a.css("top"),10)+a.scrollY;return a.hide(),b},off:function(){a(this.scope).off(".fndtn.reveal")},reflow:function(){}}}(jQuery,window,window.document),function(a,b,c,d){"use strict";Foundation.libs.topbar={name:"topbar",version:"5.5.3",settings:{index:0,start_offset:0,sticky_class:"sticky",custom_back_text:!0,back_text:"Back",mobile_show_parent_link:!0,is_hover:!0,scrolltop:!0,sticky_on:"all",dropdown_autoclose:!0},init:function(b,c,d){Foundation.inherit(this,"add_custom_rule register_media throttle");var e=this;e.register_media("topbar","foundation-mq-topbar"),this.bindings(c,d),e.S("["+this.attr_name()+"]",this.scope).each(function(){var b=a(this),c=b.data(e.attr_name(!0)+"-init");e.S("section, .top-bar-section",this);b.data("index",0);var d=b.parent();d.hasClass("fixed")||e.is_sticky(b,d,c)?(e.settings.sticky_class=c.sticky_class,e.settings.sticky_topbar=b,b.data("height",d.outerHeight()),b.data("stickyoffset",d.offset().top)):b.data("height",b.outerHeight()),c.assembled||e.assemble(b),c.is_hover?e.S(".has-dropdown",b).addClass("not-click"):e.S(".has-dropdown",b).removeClass("not-click"),e.add_custom_rule(".f-topbar-fixed { padding-top: "+b.data("height")+"px }"),d.hasClass("fixed")&&e.S("body").addClass("f-topbar-fixed")})},is_sticky:function(a,b,c){var d=b.hasClass(c.sticky_class),e=matchMedia(Foundation.media_queries.small).matches,f=matchMedia(Foundation.media_queries.medium).matches,g=matchMedia(Foundation.media_queries.large).matches;return!(!d||"all"!==c.sticky_on)||(!(!(d&&this.small()&&c.sticky_on.indexOf("small")!==-1&&e)||f||g)||(!(!(d&&this.medium()&&c.sticky_on.indexOf("medium")!==-1&&e&&f)||g)||!!(d&&this.large()&&c.sticky_on.indexOf("large")!==-1&&e&&f&&g)))},toggle:function(c){var d,e=this;d=c?e.S(c).closest("["+this.attr_name()+"]"):e.S("["+this.attr_name()+"]");var f=d.data(this.attr_name(!0)+"-init"),g=e.S("section, .top-bar-section",d);e.breakpoint()&&(e.rtl?(g.css({right:"0%"}),a(">.name",g).css({right:"100%"})):(g.css({left:"0%"}),a(">.name",g).css({left:"100%"})),e.S("li.moved",g).removeClass("moved"),d.data("index",0),d.toggleClass("expanded").css("height","")),f.scrolltop?d.hasClass("expanded")?d.parent().hasClass("fixed")&&(f.scrolltop?(d.parent().removeClass("fixed"),d.addClass("fixed"),e.S("body").removeClass("f-topbar-fixed"),b.scrollTo(0,0)):d.parent().removeClass("expanded")):d.hasClass("fixed")&&(d.parent().addClass("fixed"),d.removeClass("fixed"),e.S("body").addClass("f-topbar-fixed")):(e.is_sticky(d,d.parent(),f)&&d.parent().addClass("fixed"),d.parent().hasClass("fixed")&&(d.hasClass("expanded")?(d.addClass("fixed"),d.parent().addClass("expanded"),e.S("body").addClass("f-topbar-fixed")):(d.removeClass("fixed"),d.parent().removeClass("expanded"),e.update_sticky_positioning())))},timer:null,events:function(c){var d=this,e=this.S;e(this.scope).off(".topbar").on("click.fndtn.topbar","["+this.attr_name()+"] .toggle-topbar",function(a){a.preventDefault(),d.toggle(this)}).on("click.fndtn.topbar contextmenu.fndtn.topbar",'.top-bar .top-bar-section li a[href^="#"],['+this.attr_name()+'] .top-bar-section li a[href^="#"]',function(b){var c=a(this).closest("li"),e=c.closest("["+d.attr_name()+"]"),f=e.data(d.attr_name(!0)+"-init");if(f.dropdown_autoclose&&f.is_hover){var g=a(this).closest(".hover");g.removeClass("hover")}!d.breakpoint()||c.hasClass("back")||c.hasClass("has-dropdown")||d.toggle()}).on("click.fndtn.topbar","["+this.attr_name()+"] li.has-dropdown",function(b){var c=e(this),f=e(b.target),g=c.closest("["+d.attr_name()+"]"),h=g.data(d.attr_name(!0)+"-init");return f.data("revealId")?void d.toggle():void(d.breakpoint()||h.is_hover&&!Modernizr.touch||(b.stopImmediatePropagation(),c.hasClass("hover")?(c.removeClass("hover").find("li").removeClass("hover"),c.parents("li.hover").removeClass("hover")):(c.addClass("hover"),a(c).siblings().removeClass("hover"),"A"===f[0].nodeName&&f.parent().hasClass("has-dropdown")&&b.preventDefault())))}).on("click.fndtn.topbar","["+this.attr_name()+"] .has-dropdown>a",function(a){if(d.breakpoint()){a.preventDefault();var b=e(this),c=b.closest("["+d.attr_name()+"]"),f=c.find("section, .top-bar-section"),g=(b.next(".dropdown").outerHeight(),b.closest("li"));c.data("index",c.data("index")+1),g.addClass("moved"),d.rtl?(f.css({right:-(100*c.data("index"))+"%"}),f.find(">.name").css({right:100*c.data("index")+"%"})):(f.css({left:-(100*c.data("index"))+"%"}),f.find(">.name").css({left:100*c.data("index")+"%"})),c.css("height",b.siblings("ul").outerHeight(!0)+c.data("height"))}}),e(b).off(".topbar").on("resize.fndtn.topbar",d.throttle(function(){d.resize.call(d)},50)).trigger("resize.fndtn.topbar").load(function(){e(this).trigger("resize.fndtn.topbar")}),e("body").off(".topbar").on("click.fndtn.topbar",function(a){var b=e(a.target).closest("li").closest("li.hover");b.length>0||e("["+d.attr_name()+"] li.hover").removeClass("hover")}),e(this.scope).on("click.fndtn.topbar","["+this.attr_name()+"] .has-dropdown .back",function(a){a.preventDefault();var b=e(this),c=b.closest("["+d.attr_name()+"]"),f=c.find("section, .top-bar-section"),g=(c.data(d.attr_name(!0)+"-init"),b.closest("li.moved")),h=g.parent();c.data("index",c.data("index")-1),d.rtl?(f.css({right:-(100*c.data("index"))+"%"}),f.find(">.name").css({right:100*c.data("index")+"%"})):(f.css({left:-(100*c.data("index"))+"%"}),f.find(">.name").css({left:100*c.data("index")+"%"})),0===c.data("index")?c.css("height",""):c.css("height",h.outerHeight(!0)+c.data("height")),setTimeout(function(){g.removeClass("moved")},300)}),e(this.scope).find(".dropdown a").focus(function(){a(this).parents(".has-dropdown").addClass("hover")}).blur(function(){a(this).parents(".has-dropdown").removeClass("hover")})},resize:function(){var a=this;a.S("["+this.attr_name()+"]").each(function(){var b,d=a.S(this),e=d.data(a.attr_name(!0)+"-init"),f=d.parent("."+a.settings.sticky_class);if(!a.breakpoint()){var g=d.hasClass("expanded");d.css("height","").removeClass("expanded").find("li").removeClass("hover"),g&&a.toggle(d)}a.is_sticky(d,f,e)&&(f.hasClass("fixed")?(f.removeClass("fixed"),b=f.offset().top,a.S(c.body).hasClass("f-topbar-fixed")&&(b-=d.data("height")),d.data("stickyoffset",b),f.addClass("fixed")):(b=f.offset().top,d.data("stickyoffset",b)))})},breakpoint:function(){return!matchMedia(Foundation.media_queries.topbar).matches},small:function(){return matchMedia(Foundation.media_queries.small).matches},medium:function(){return matchMedia(Foundation.media_queries.medium).matches},large:function(){return matchMedia(Foundation.media_queries.large).matches},assemble:function(b){var c=this,d=b.data(this.attr_name(!0)+"-init"),e=c.S("section, .top-bar-section",b);e.detach(),c.S(".has-dropdown>a",e).each(function(){var b,e=c.S(this),f=e.siblings(".dropdown"),g=e.attr("href");f.find(".title.back").length||(b=a(1==d.mobile_show_parent_link&&g?'<li class="title back js-generated"><h5><a href="javascript:void(0)"></a></h5></li><li class="parent-link hide-for-medium-up"><a class="parent-link js-generated" href="'+g+'">'+e.html()+"</a></li>":'<li class="title back js-generated"><h5><a href="javascript:void(0)"></a></h5>'),1==d.custom_back_text?a("h5>a",b).html(d.back_text):a("h5>a",b).html("&laquo; "+e.html()),f.prepend(b))}),e.appendTo(b),this.sticky(),this.assembled(b)},assembled:function(b){b.data(this.attr_name(!0),a.extend({},b.data(this.attr_name(!0)),{assembled:!0}))},height:function(b){var c=0,d=this;return a("> li",b).each(function(){c+=d.S(this).outerHeight(!0)}),c},sticky:function(){var a=this;this.S(b).on("scroll",function(){a.update_sticky_positioning()})},update_sticky_positioning:function(){var a="."+this.settings.sticky_class,c=this.S(b),d=this;if(d.settings.sticky_topbar&&d.is_sticky(this.settings.sticky_topbar,this.settings.sticky_topbar.parent(),this.settings)){var e=this.settings.sticky_topbar.data("stickyoffset")+this.settings.start_offset;d.S(a).hasClass("expanded")||(c.scrollTop()>e?d.S(a).hasClass("fixed")||(d.S(a).addClass("fixed"),d.S("body").addClass("f-topbar-fixed")):c.scrollTop()<=e&&d.S(a).hasClass("fixed")&&(d.S(a).removeClass("fixed"),d.S("body").removeClass("f-topbar-fixed")))}},off:function(){this.S(this.scope).off(".fndtn.topbar"),this.S(b).off(".fndtn.topbar")},reflow:function(){}}}(jQuery,window,window.document),function(a){var b={init:function(){a(document).foundation(),new FastClick(document.body),this.bindEvents(),this.responsiveIframes(),this.disabledAnchors(),this.revealModalClose(),this.revealModalPreventScrolling(),this.analyticsEventTracking(),this.honeyPotHelper(),this.lazyLoad()},bindEvents:function(){var b=!1;a(window).resize(a.proxy(function(){b&&clearTimeout(b),b=setTimeout(a.proxy(function(){this.responsiveIframes()},this),30)},this));var c=["#follow-cluster","#subscribe-to-newsletter","#subscribe-to-newsletter-page",".widget_pedestal_signup_newsletter"];a(c.join(", ")).find("form").on("submit",function(b){b.preventDefault();var c=a(this),d=c.data("confirm-id"),e=c.find(".js-form-submit"),f=(e.find(".js-spinner"),e.width()),g=c.attr("action"),h=g.indexOf("?")>=0?"&":"?";g+=h+a.param({"ajax-request":1}),e.width(f),e.css("padding-left",0),e.css("padding-right",0),c.find(".alert").remove(),c.addClass("is-loading"),a.post(g,c.serialize(),function(b){"undefined"!=typeof d?a("#"+d).foundation("reveal","open"):c.find(".success-message").length&&(c.removeClass("is-loading"),c.find(".form-fields").hide(),c.find(".success-message").show())}).fail(function(b){var d=a('<div data-alert class="alert-box alert"></div>');c.removeClass("is-loading"),d.text(b.responseText),c.find("h3").length?c.find("h3").after(d):c.prepend(d)})})},responsiveIframes:function(){a(".pedestal-responsive").each(function(){var b=a(this),c=b.parent().width();window.self!==window.top&&(c=parent.innerWidth);var d=b.data("true-height")?b.data("true-height"):360,e=b.data("true-width")?b.data("true-width"):640,f=c/e*d;a(this).css("height",f+"px").css("width",c+"px")})},disabledAnchors:function(){a("a.disabled").click(function(a){a.preventDefault()})},revealModalClose:function(){a(document).on("click",".js-share-modal-btn",function(){a(".js-share-modal").foundation("reveal","close")})},revealModalPreventScrolling:function(){a(document).on("open.fndtn.reveal","[data-reveal]",function(){a("body").addClass("noscroll"),a("body").bind("touchmove",function(a){a.preventDefault()})}),a(document).on("close.fndtn.reveal","[data-reveal]",function(){a("body").removeClass("noscroll"),a("body").unbind("touchmove")})},analyticsEventTracking:function(){var b=!1;a("body").is(".js-debug-ga")&&(b=!0),("undefined"!=typeof ga||b)&&a("body").on("click","a[data-ga-category]",function(c){var d=a(this),e=d.data("ga-category"),f=c.currentTarget.href,g=d.data("ga-label");return b?(console.group("Google Analytics Event Data"),console.log("Category: ",e),console.log("Action: ",f),console.log("Label: ",g),console.groupEnd(),void c.preventDefault()):void ga("send","event",e,f,g)}).on("submit","form[data-ga-category]",function(c){var d=a(this),e=d.data("ga-category"),f=d.attr("action"),g=d.data("ga-label");return b?(console.group("Google Analytics Event Data"),console.log("Category: ",e),
console.log("Action: ",f),console.log("Label: ",g),console.groupEnd(),void c.preventDefault()):void ga("send","event",e,f,g)})},honeyPotHelper:function(){var b=(new Date).getFullYear();a(".js-pedestal-current-year-check").val(b)},lazyLoad:function(){a(".content-wrapper").on("click",".js-yt-placeholder-link",function(b){$this=a(this);var c=$this.data("youtube-id");if(c){$parent=$this.parents(".js-yt-placeholder");var d="https://www.youtube.com/embed/";d+=c+"?showinfo=0&autoplay=1";var e="<iframe ";e+='src="'+d+'" ',e+='frameborder="0" ',e+="allowfullscreen ",e+="/>",$parent.append(e),$this.fadeOut(750,function(){$this.remove()}),b.preventDefault()}})}};a(document).ready(function(){b.init()})}(jQuery),jQuery(document).ready(function(a){function b(){return!(!window.history||!history.pushState)}function c(){var b=k.offset().top,c=a(window).scrollTop();c<b||a("html, body").animate({scrollTop:b},250)}function d(){k.find(".js-search-input").addClass("is-loading"),j.addClass("is-loading"),m.show(),c()}function e(){k.find(".is-loading").removeClass("is-loading"),j.removeClass("is-loading"),m.removeAttr("style")}function f(){h.removeClass("is-search-open")}function g(b){if(!i)return b?void a.ajax({type:"GET",dataType:"html",url:b,beforeSend:function(){i=!0},success:function(c){c=a.trim(c);var d="",f=c.match(/<title>(.+)<\/title>/);f[1]&&(d=f[1]);var g=a(c),h=g.find(".js-stream").html();h=a.trim(h);var i=g.find(".js-search-tools").html();i=a.trim(i),l.html(h),a(".js-search-tools").html(i),j.addClass("is-active-search"),window.history.pushState({spiritedSearch:!0},d,b),"function"==typeof ga&&ga("send",{hitType:"pageview",title:d,location:b}),e()},error:function(){e()}}).always(function(){i=!1,c()}):(e(),!1)}var h=a("body"),i=!1,j=a(".js-main"),k=a(".js-search-form"),l=(a(".js-pagination"),a(".js-stream")),m=a(".js-spinner");h.on("click keyup",".js-sitewide-search-icon",function(b){if("keyup"!==b.type||13===b.which){h.toggleClass("is-search-open");var c=a(this).attr("for");$target=a("#"+c),$target.attr("tabindex",1).select()}}),b()&&(j.on("click","a.js-pagination-item",function(b){if(j.is(".is-active-search")){var c=a(this);if(!c.is(".js-is-disabled")){d();var e=c.attr("href");g(e),b.preventDefault()}}}),k.on("submit",function(a){if(d(),l.length){var b=k.attr("action");if(b=b.replace(/\/?$/,"/"),b+="?"+k.serialize(),b=b.replace(/&orderby=relevance/gi,""),window.location.href===b)return setTimeout(function(){e()},450),void a.preventDefault();k.find(".js-search-field").blur(),g(b),a.preventDefault()}}),k.on("click",".js-search-icon-close",function(a){f(),a.preventDefault()}),a(".js-search-tools").on("change",".js-search-filters-radio",function(){$this=a(this),$this.is("selected")||($this.closest(".js-search-filters").find(".is-active").removeClass("is-active button"),$this.parent().addClass("is-active button"),k.submit())}),a(window).bind("popstate",function(a){a.originalEvent.state.spiritedSearch&&window.location.href!==document.referrer&&(d(),window.location=window.location.href)}))});