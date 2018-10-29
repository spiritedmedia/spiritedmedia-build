var _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e};!function(){"use strict";var r=function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")},t=function(){function o(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}return function(e,t,n){return t&&o(e.prototype,t),n&&o(e,n),e}}(),i=function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+(void 0===t?"undefined":_typeof(t)));e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)},s=function(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!==(void 0===t?"undefined":_typeof(t))&&"function"!=typeof t?e:t};String.prototype.toCamelCase=function(){return this.replace(/\s(.)/g,function(e){return e.toUpperCase()}).replace(/\s/g,"").replace(/^(.)/,function(e){return e.toLowerCase()})},String.prototype.capFirst=function(){return this.charAt(0).toUpperCase()+this.slice(1)},String.prototype.escAttr=function(e){return e=e?"&#13;":"\n",this.replace(/&/g,"&amp;").replace(/'/g,"&apos;").replace(/"/g,"&quot;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\r\n/g,e).replace(/[\r\n]/g,e)},Object.defineProperty(Object.prototype,"toAttsString",{value:function(){var e="";for(var t in this)if(this.hasOwnProperty(t)){var n=this[t];Array.isArray(n)&&(n=n.join(" ")),("string"==typeof n||n instanceof String)&&(e+=t+'="'+n.escAttr()+'" ')}return e},enumerable:!1}),window.PedUtils=new(function(){function e(){r(this,e)}return t(e,[{key:"debounce",value:function(o,i,r){var s;return function(){var e=this,t=arguments,n=r&&!s;clearTimeout(s),s=setTimeout(function(){s=null,r||o.apply(e,t)},i||200),n&&o.apply(e,t)}}},{key:"throttle",value:function(e,t){var n=!1;return function(){n||(e.call(),n=!0,setTimeout(function(){n=!1},t))}}},{key:"removeHash",value:function(){history.pushState("",document.title,window.location.pathname+window.location.search)}},{key:"focusAtEnd",value:function(e){if(0<e.length){var t=e[0],n=t.value.length;(t.selectionStart||"0"==t.selectionStart)&&(t.selectionStart=n,t.selectionEnd=n,t.focus())}}},{key:"genStr",value:function(){for(var e=0<arguments.length&&void 0!==arguments[0]?arguments[0]:8,t="",n="23456789abdegjkmnpqrvwxyz",o=0;o<e;o++)t+=n.charAt(Math.floor(Math.random()*n.length));return t}},{key:"getURLParams",value:function(){var e=0<arguments.length&&void 0!==arguments[0]?arguments[0]:"",t=1<arguments.length&&void 0!==arguments[1]?arguments[1]:"",o={};return t||(t=location.search),t.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(e,t,n){o[t]=n}),e?o[e]:o}}]),e}());var a=Backbone.View.extend({listeningToEditor:!1,widthButtonLabels:{toDesktop:"Switch to desktop preview",toMobile:"Switch to mobile preview"},initialize:function(){var t=this;this.editor=null,this.editorID=!1,this.$templateProto=this.$el.find(".js-message-spot-template-proto"),this.template=Twig.twig({id:this.id,data:this.$templateProto.html()}),this.setupFrame(),this.$output.on("load",function(){var e=t.$el.find(".fm-body .fm-element");e.hasClass("fm-tinymce")&&(t.editorID=e.attr("id"),t.listenToBodyEditor()),t.render()}),this.listenTo(this.model,"change",this.render),this.$output.contents().on("focus",".js-message-spot",function(){t.$output.focus()})},render:function(){var e=this.model.toJSON();switch(e.additional_classes=this.getVariantClass(),e.type){case"standard":e.title=!1,e.button_label=!1;break;case"with_title":case"override":e.button_label=!1;break;case"with_button":e.icon=!1,e.title=!1}e.body||(e.body=this.model.get("postTitle")||this.model.defaults.body);var t=this.template.render(e);return this.$output.contents().find("body").html(t),this},events:{"change .fm-type .fm-element":function(e){this.model.save("type",e.target.value)},"keyup .fm-body .fm-element":function(e){this.model.save("body",e.target.value)},"input .fm-url .fm-element":function(e){this.model.save("url",e.target.value)},"input .fm-title .fm-element":function(e){this.model.save("title",e.target.value)},"input .fm-button_label .fm-element":function(e){this.model.save("button_label",e.target.value)},"click .js-message-icon-button":"onIconButtonClick","keydown .js-message-icon-button":"onIconButtonKeydown","click .js-message-spot-preview-toggle-width":"onToggleWidthClick"},setupFrame:function(){var e=this.$el.find(".fm-id .fm-element").val();this.$outputContainer=this.$el.find(".js-message-spot-preview-container"),this.$outputContainer.html('\n      <iframe src="'+pedestalPreviewURL+e+'/"\n        class="message-spot-preview js-message-spot-preview"\n        width="100%"></iframe>\n    '),this.$output=this.$outputContainer.find(".js-message-spot-preview"),this.$toggleWidthButton=$('\n      <button\n        title="Change preview width"\n        class="js-message-spot-preview-toggle-width button-secondary"\n      >'+this.widthButtonLabels.toDesktop+"</button>\n    "),this.$toggleWidthButton.insertAfter(this.$outputContainer)},onIconButtonClick:function(e){var t=$(e.currentTarget),n=this.$el.find(".js-message-icon-button");n.removeClass("is-checked"),n.find(".fm-element:radio").attr("checked",!1),t.addClass("is-checked"),t.prev(".fm-element:radio").attr("checked",!0),e.preventDefault(),this.model.save("icon",t.data("message-icon-value"))},onIconButtonKeydown:function(e){32==e.which&&$(e.currentTarget).trigger("click")},onToggleWidthClick:function(e){var t=$(e.target),n="message-spot-preview-container--large";t.text()===this.widthButtonLabels.toDesktop?(t.text(this.widthButtonLabels.toMobile),this.$outputContainer.addClass(n)):(t.text(this.widthButtonLabels.toDesktop),this.$outputContainer.removeClass(n)),e.preventDefault()},listenToBodyEditor:function(){var n=this;if("undefined"!=typeof tinyMCE&&this.editorID){var o=function(){n.editor.on("keyup",function(){n.model.save("body",n.editor.getContent())}),n.listeningToEditor=!0};tinyMCE.hasOwnProperty("editors")&&$.each(tinyMCE.editors,function(e,t){!n.listeningToEditor&&t.hasOwnProperty("id")&&t.id.trim()===n.editorID&&(n.editor=t,o())}),tinyMCE.on("AddEditor",function(e){n.listeningToEditor||e.editor.id!==n.editorID||(n.editor=e.editor,o())})}},getVariantClass:function(){var e=this.model.get("type");if("standard"===e)return"";var t="message-spot--"+e.replace("_","-");return"override"===e&&(t+=" message-spot--with-title"),t},destroy:function(){this.undelegateEvents(),this.editor&&"off"in this.editor&&this.editor.off(),this.$el.removeData().unbind(),this.$output.remove(),this.$toggleWidthButton.remove()}}),e=function(){function i(e,t){if(r(this,i),this.$el=e,this.$modelStorage=this.$el.find(".fm-preview_model .fm-element"),this.modelDefaults=t,0<this.$modelStorage.length){var n=decodeURIComponent(this.$modelStorage.val());n&&(this.modelDefaults=JSON.parse(n))}var o=Backbone.Model.extend({defaults:this.modelDefaults,$storage:this.$modelStorage,sync:function(){var e=encodeURIComponent(JSON.stringify(this));this.$storage.val(e)}});this.Model=new o,this.View=new a({el:this.$el,model:this.Model})}return t(i,[{key:"destroy",value:function(){this.View.destroy()}}]),i}(),l=function(){function o(e,t){r(this,o),this.$el=e;var n=messagePreviewDefaults.standard;this.defaults=Object.assign({},n,t),this.createPreview()}return t(o,[{key:"createPreview",value:function(){this.Preview=new e(this.$el,this.defaults)}},{key:"destroyPreview",value:function(){"destroy"in this.Preview&&this.Preview.destroy(),this.Preview=null}},{key:"setPreviewAttribute",value:function(e,t){this.Preview.View.model.save(e,t)}}]),o}(),u=function(e){function o(e){r(this,o);var t=s(this,(o.__proto__||Object.getPrototypeOf(o)).call(this,e)),n=t.$el.parent();return n.one("sortstart",function(){return t.destroyPreview()}),n.one("sortstop",function(){return t.createPreview()}),t}return i(o,l),o}(),c=function(e){function n(){r(this,n);var t=s(this,(n.__proto__||Object.getPrototypeOf(n)).call(this,$(".fm-override"),messagePreviewDefaults.override));return t.post=null,t.$el.on("change",".fm-autocomplete-hidden",function(e){return t.onPostSelection(e)}),t}return i(n,l),t(n,[{key:"onPostSelection",value:function(e){var n=this,t=$(e.target),o=parseInt(t.val()),i=t.closest(".fm-group-inner"),r=i.find(".fm-body .fm-element"),s=i.find(".fm-url .fm-element");if(!o)return r.val(""),void s.val("");var a={post_id:o,action:"pedestal-message-spot-override"};$.post(ajaxurl,a,function(e){if(e.data){n.post=e.data,r.val(n.post.title),n.setPreviewAttribute("body",n.post.title),i.find(".fm-post_title .fm-element").val(n.post.title),n.setPreviewAttribute("postTitle",n.post.title);var t=encodeURI(n.post.url);s.val(t),n.setPreviewAttribute("url",t)}})}}]),n}();jQuery(document).ready(function(o){Twig.extendFunction("ped_icon",function(e,t){if(e=e.trim(),!PedestalIcons.hasOwnProperty(e))throw'[Message Spot] The icon "'+e+"\" doesn't seem to exist!";var n=o(PedestalIcons[e].svg);return n.addClass(t),n[0].outerHTML}),o(".fm-icon .fm-option .fm-element").each(function(e,t){return n=t,o=$('label[for="'+n.id+'"]'),i=$.trim(o.text()),r=n.value,s='\n    <a href="#"\n      title="'+i+'"\n      class="js-message-icon-button message-icon-button button-secondary '+(n.checked?" is-checked":"")+'"\n      data-message-icon-value="'+r+'"\n    >\n      '+PedestalIcons[r].svg+"\n    </a>\n  ",n.style.display="none",o.hide(),void $(s).insertAfter(n);var n,o,i,r,s}),o(".fm-message:not(.fmjs-proto) .fm-group-label-wrapper").each(function(){new u(o(this).parent())}),o(document).on("fm_added_element",function(e){var t=o(e.target),n=PedUtils.genStr();t.find(".fm-id .fm-element").val(n),new u(t)});var t=function(e){var t=o(e),n=t.closest(".fm-group-inner").find(".fm-wrapper:not(.fm-enabled-wrapper)");"true"===t.val()?(window.MessageSpotOverride=new c,n.show()):(n.hide(),window.MessageSpotOverride instanceof c&&window.MessageSpotOverride.destroyPreview(),window.MessageSpotOverride=null)};t(".fm-enabled .fm-element:checked"),o(document).on("change",".fm-enabled .fm-element",function(e){return t(e.target)})})}();