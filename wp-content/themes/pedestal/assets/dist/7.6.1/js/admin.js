!function(c){var t={init:function(){for(var t in this.clusterMap={stories:"pedestal_story",topics:"pedestal_topic",people:"pedestal_person",organizations:"pedestal_org",places:"pedestal_place",localities:"pedestal_locality"},this.connections={entities_to_clusters:{from:"entities",to:["stories","topics","people","organizations","places","localities"]},stories_to_clusters:{from:"stories",to:["topics","people","organizations","places","localities"]}},this.siteURL=window.location.protocol+"//"+window.location.hostname,this.connections)this.manageConnectionsMetaboxes(this.connections[t],t);setTimeout(function(){c(".p2p-toggle-tabs a").click().hide()},1500),this.toggleIOTDEmbedField(),this.handleEventUI(),this.makeHierarchicalTermsFilterable(),this.setupSummaryButtons(),this.disableDraggingDistributionMetaboxes()},extractDomain:function(t){var e;return(e=-1<t.indexOf("://")?t.split("/")[2]:t.split("/")[0]).replace("www.",""),e=e.split(":")[0]},manageConnectionsMetaboxes:function(t,e){for(var i,n=t.to,a=n.length-1;0<=a;a--){i=n[a];var o=t.from+"_to_"+i,s=c("[data-p2p_type="+o+"].p2p-box"),r=c("#fm-pedestal_"+e+"_connections-0-"+i+"-0-tab .fm-group-inner");0!==s.length&&0!==r.length&&(r.append(s.parent().html()),s.closest(".postbox").remove())}},toggleIOTDEmbedField:function(){c(".post-type-pedestal_embed #fm-embed_url-0").on("blur",function(t){var e=c(t.target),i=c("#fm_meta_box_daily_insta_date");-1!==e.val().indexOf("instagr")?i.show():i.hide(),fm.datepicker.add_datepicker(t)}).blur()},handleEventUI:function(){c("#fm-event_details-0-all_day-0").on("change",function(){c(".fm-start_time-wrapper .fm-datepicker-time-wrapper, .fm-end_time-wrapper .fm-datepicker-time-wrapper").toggle(!this.checked)}).change();c("#fm-event_details-0-start_time-0, #fm-event_details-0-end_time-0").on("change keyup copy paste cut",function(){var t=c(this);0===t.val().length&&t.closest(".fm-item").find(".fm-datepicker-time").val("")})},makeHierarchicalTermsFilterable:function(){var t=c(".categorydiv");if(!(t.length<1)){jQuery.expr[":"].contains=function(t,e,i){var n=(t.textContent||t.innerText||"").toUpperCase(),a=i[3].toUpperCase();return 0<=n.indexOf(a)};var e="categorydiv-filter",i="."+e,a=c('<input type="search" />').addClass(e).css("width","100%");t.each(function(t,e){var i=c(e);if(!(i.find(".categorychecklist li").length<10)){var n=i.parent().siblings(".hndle").text();a.attr("placeholder","Filter "+n),i.prepend(a)}}),c("#post").on("keyup",i,function(){var t=c(this),e=t.parent().find(".categorychecklist li");t.val().length<2?e.show():e.hide().find(".selectit:contains("+t.val()+")").each(function(t,e){c(e).parent().show()})}).on("keydown",i,function(t){if(13==t.keyCode)return t.preventDefault(),!1}).on("click",i,function(){var t=c(this);setTimeout(function(){t.trigger("keyup")},100,t)})}},setupSummaryButtons:function(){var n=c("#fm-homepage_settings-0-summary-0");n.length<1||(c(".js-pedestal-summary-copy-subhead").on("click",function(){var t=c("textarea#excerpt").val();""!==t&&n.val(t)}),c(".js-pedestal-summary-copy-first-graf").on("click",function(){var t=tinyMCE.get("content").getContent(),e=c(t).filter("p").not(function(){return this.innerHTML.match(/\[([^\s\]]+)([^\]]+)?\]([^[]*)?(\[\/(\S+?)\])?/)});if(!(e.length<=0)){var i=e.first().html();n.val(i)}}))},disableDraggingDistributionMetaboxes:function(){var t=c("#distribution-sortables");t.sortable({disabled:!0}),t.find(".postbox .hndle").css("cursor","pointer")}};c(document).ready(function(){t.init()})}(jQuery);