"use strict";jQuery(document).ready(function(e){var t={},n={minLength:2,source:function(n,c){var i=n.term;if(i in t)c(t[i]);else{var o={action:"recent-content-widget-cluster-autocomplete",term:i};e.post(ajaxurl,o,function(e){t[i]=e.data,c(e.data)})}},select:function(t,n){e(".js-recent-content-widget-clusters").append(n.item.selected_item),e(this).val(""),t.preventDefault()}};e("#widgets-right").on("change",".js-recent-content-widget-filter-trigger",function(){var t=e(this),n=t.parents("fieldset"),c=n.find(".js-recent-content-widget-filter"),i=n.find(".js-recent-content-widget-clusters");1==t.val()?(c.show(),i.show()):(c.hide(),i.hide().html(""))}).on("click",".js-recent-content-widget-remove-cluster",function(t){e(this).parent().remove(),t.preventDefault()}),e(".js-cluster-autocomplete").autocomplete(n),e(document).on("widget-added widget-updated",function(t,c){e(c).find(".js-cluster-autocomplete").autocomplete(n)})});