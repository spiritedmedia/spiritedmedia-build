!function(){"use strict";function s(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function o(t,e){for(var n=0;n<e.length;n++){var o=e[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(t,o.key,o)}}function e(t,e,n){return e&&o(t.prototype,e),n&&o(t,n),t}function i(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function");t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,writable:!0,configurable:!0}}),e&&n(t,e)}function r(t){return(r=Object.setPrototypeOf?Object.getPrototypeOf:function(t){return t.__proto__||Object.getPrototypeOf(t)})(t)}function n(t,e){return(n=Object.setPrototypeOf||function(t,e){return t.__proto__=e,t})(t,e)}function a(t,e){return!e||"object"!=typeof e&&"function"!=typeof e?function(t){if(void 0===t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return t}(t):e}var l=Backbone.View.extend({listeningToEditor:!1,widthButtonLabels:{toDesktop:"Switch to desktop preview",toMobile:"Switch to mobile preview"},initialize:function(){var e=this;this.editor=null,this.editorID=!1,this.$templateProto=this.$el.find(".js-message-spot-template-proto"),this.template=Twig.twig({id:this.id,data:this.$templateProto.html()}),this.setupFrame(),this.$output.on("load",function(){var t=e.$el.find(".fm-body .fm-element");t.hasClass("fm-tinymce")&&(e.editorID=t.attr("id"),e.listenToBodyEditor()),e.render()}),this.listenTo(this.model,"change",this.render),this.$output.contents().on("focus",".js-message-spot",function(){e.$output.focus()})},render:function(){var t=this.model.toJSON();switch(t.additional_classes=this.getVariantClass(),t.type){case"standard":t.title=!1,t.button_label=!1;break;case"with_title":case"override":t.button_label=!1;break;case"with_button":t.icon=!1,t.title=!1}t.body||(t.body=this.model.get("postTitle")||this.model.defaults.body);var e=this.template.render(t);return this.$output.contents().find("body").html(e),this},events:{"change .fm-type .fm-element":function(t){this.model.save("type",t.target.value)},"keyup .fm-body .fm-element":function(t){this.model.save("body",t.target.value)},"input .fm-url .fm-element":function(t){this.model.save("url",t.target.value)},"input .fm-title .fm-element":function(t){this.model.save("title",t.target.value)},"input .fm-button_label .fm-element":function(t){this.model.save("button_label",t.target.value)},"click .js-message-icon-button":"onIconButtonClick","keydown .js-message-icon-button":"onIconButtonKeydown","click .js-message-spot-preview-toggle-width":"onToggleWidthClick"},setupFrame:function(){var t=this.$el.find(".fm-id .fm-element").val();this.$outputContainer=this.$el.find(".js-message-spot-preview-container"),this.$outputContainer.html('\n      <iframe src="'.concat(pedestalPreviewURL).concat(t,'/"\n        class="message-spot-preview js-message-spot-preview"\n        width="100%"></iframe>\n    ')),this.$output=this.$outputContainer.find(".js-message-spot-preview"),this.$toggleWidthButton=$('\n      <button\n        title="Change preview width"\n        class="js-message-spot-preview-toggle-width button-secondary"\n      >'.concat(this.widthButtonLabels.toDesktop,"</button>\n    ")),this.$toggleWidthButton.insertAfter(this.$outputContainer)},onIconButtonClick:function(t){var e=$(t.currentTarget),n=this.$el.find(".js-message-icon-button");n.removeClass("is-checked"),n.find(".fm-element:radio").attr("checked",!1),e.addClass("is-checked"),e.prev(".fm-element:radio").attr("checked",!0),t.preventDefault(),this.model.save("icon",e.data("message-icon-value"))},onIconButtonKeydown:function(t){32==t.which&&$(t.currentTarget).trigger("click")},onToggleWidthClick:function(t){var e=$(t.target),n="message-spot-preview-container--large";e.text()===this.widthButtonLabels.toDesktop?(e.text(this.widthButtonLabels.toMobile),this.$outputContainer.addClass(n)):(e.text(this.widthButtonLabels.toDesktop),this.$outputContainer.removeClass(n)),t.preventDefault()},listenToBodyEditor:function(){var n=this;if("undefined"!=typeof tinyMCE&&this.editorID){var o=function(){n.editor.on("keyup",function(){n.model.save("body",n.editor.getContent())}),n.listeningToEditor=!0};tinyMCE.hasOwnProperty("editors")&&$.each(tinyMCE.editors,function(t,e){!n.listeningToEditor&&e.hasOwnProperty("id")&&e.id.trim()===n.editorID&&(n.editor=e,o())}),tinyMCE.on("AddEditor",function(t){n.listeningToEditor||t.editor.id!==n.editorID||(n.editor=t.editor,o())})}},getVariantClass:function(){var t=this.model.get("type");if("standard"===t)return"";var e="message-spot--".concat(t.replace("_","-"));return"override"===t&&(e+=" message-spot--with-title"),e},destroy:function(){this.undelegateEvents(),this.editor&&"off"in this.editor&&this.editor.off(),this.$el.removeData().unbind(),this.$output.remove(),this.$toggleWidthButton.remove()}}),t=function(){function i(t,e){if(s(this,i),this.$el=t,this.$modelStorage=this.$el.find(".fm-preview_model .fm-element"),this.modelDefaults=e,0<this.$modelStorage.length){var n=decodeURIComponent(this.$modelStorage.val());n&&(this.modelDefaults=JSON.parse(n))}var o=Backbone.Model.extend({defaults:this.modelDefaults,$storage:this.$modelStorage,sync:function(){var t=encodeURIComponent(JSON.stringify(this));this.$storage.val(t)}});this.Model=new o,this.View=new l({el:this.$el,model:this.Model})}return e(i,[{key:"destroy",value:function(){this.View.destroy()}}]),i}(),u=function(){function o(t,e){s(this,o),this.$el=t;var n=messagePreviewDefaults.standard;this.defaults=Object.assign({},n,e),this.createPreview()}return e(o,[{key:"createPreview",value:function(){this.Preview=new t(this.$el,this.defaults)}},{key:"destroyPreview",value:function(){"destroy"in this.Preview&&this.Preview.destroy(),this.Preview=null}},{key:"setPreviewAttribute",value:function(t,e){this.Preview.View.model.save(t,e)}}]),o}(),d=function(t){function o(t){var e;s(this,o);var n=(e=a(this,r(o).call(this,t))).$el.parent();return n.one("sortstart",function(){return e.destroyPreview()}),n.one("sortstop",function(){return e.createPreview()}),e}return i(o,u),o}(),c=function(t){function n(){var e;return s(this,n),(e=a(this,r(n).call(this,$(".fm-override"),messagePreviewDefaults.override))).post=null,e.$el.on("change",".fm-autocomplete-hidden",function(t){return e.onPostSelection(t)}),e}return i(n,u),e(n,[{key:"onPostSelection",value:function(t){var n=this,e=$(t.target),o=parseInt(e.val()),i=e.closest(".fm-group-inner"),s=i.find(".fm-body .fm-element"),r=i.find(".fm-url .fm-element");if(!o)return s.val(""),void r.val("");var a={post_id:o,action:"pedestal-message-spot-override"};$.post(ajaxurl,a,function(t){if(t.data){n.post=t.data,s.val(n.post.title),n.setPreviewAttribute("body",n.post.title),i.find(".fm-post_title .fm-element").val(n.post.title),n.setPreviewAttribute("postTitle",n.post.title);var e=encodeURI(n.post.url);r.val(e),n.setPreviewAttribute("url",e)}})}}]),n}();jQuery(document).ready(function(o){Twig.extendFunction("ped_icon",function(t,e){if(t=t.trim(),!PedestalIcons.hasOwnProperty(t))throw'[Message Spot] The icon "'.concat(t,"\" doesn't seem to exist!");var n=o(PedestalIcons[t].svg);return n.addClass(e),n[0].outerHTML}),o(".fm-icon .fm-option .fm-element").each(function(t,e){return n=e,o=$('label[for="'.concat(n.id,'"]')),i=$.trim(o.text()),s=n.value,r=n.checked?" is-checked":"",a=PedestalIcons[s].svg,l='\n    <a href="#"\n      title="'.concat(i,'"\n      class="js-message-icon-button message-icon-button button-secondary ').concat(r,'"\n      data-message-icon-value="').concat(s,'"\n    >\n      ').concat(a,"\n    </a>\n  "),n.style.display="none",o.hide(),void $(l).insertAfter(n);var n,o,i,s,r,a,l}),o(".fm-message:not(.fmjs-proto) .fm-group-label-wrapper").each(function(){new d(o(this).parent())}),o(document).on("fm_added_element",function(t){var e=o(t.target),n=function(){for(var t=0<arguments.length&&void 0!==arguments[0]?arguments[0]:8,e="",n="23456789abdegjkmnpqrvwxyz",o=0;o<t;o++)e+=n.charAt(Math.floor(Math.random()*n.length));return e}();e.find(".fm-id .fm-element").val(n),new d(e)});var e=function(t){var e=o(t),n=e.closest(".fm-group-inner").find(".fm-wrapper:not(.fm-enabled-wrapper)");"true"===e.val()?(window.MessageSpotOverride=new c,n.show()):(n.hide(),window.MessageSpotOverride instanceof c&&window.MessageSpotOverride.destroyPreview(),window.MessageSpotOverride=null)};e(".fm-enabled .fm-element:checked"),o(document).on("change",".fm-enabled .fm-element",function(t){return e(t.target)})})}();