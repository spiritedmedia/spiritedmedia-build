!function(){"use strict";var e=PedestalIcons;jQuery(document).ready(function(a){a(".js-pedestal-icon-picker").autocomplete({minLength:0,source:Object.values(e),focus:function(e,t){a(this).val(t.item.label),e.preventDefault()},select:function(e,t){var n=a(this);n.val(t.item.label),n.siblings(".js-pedestal-icon-preview").html(t.item.svg),e.preventDefault()},close:function(e){var t=a(this);t.val()||t.siblings(".js-pedestal-icon-preview").html(""),e.preventDefault()},create:function(){a(this).data("ui-autocomplete")._renderItem=function(e,t){return a("<li>").addClass("pedestal-menu-icon-item").append(t.svg+" <span>"+t.label+"</span>").appendTo(e)}}})})}();