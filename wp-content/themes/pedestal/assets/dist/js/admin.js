!function(t){var e={init:function(){this.clusterMap={stories:"pedestal_story",topics:"pedestal_topic",people:"pedestal_person",organizations:"pedestal_org",places:"pedestal_place",localities:"pedestal_locality"},this.connections={entities_to_clusters:{from:"entities",to:["stories","topics","people","organizations","places","localities"]},stories_to_clusters:{from:"stories",to:["topics","people","organizations","places","localities"]}},this.siteURL=window.location.protocol+"//"+window.location.hostname;for(var e in this.connections)this.manageConnectionsMetaboxes(this.connections[e],e);setTimeout(function(){t(".p2p-toggle-tabs a").click().hide()},1500),this.reorderExcerptBox(),this.toggleIOTDEmbedField()},extractDomain:function(t){var e;return(e=t.indexOf("://")>-1?t.split("/")[2]:t.split("/")[0]).replace("www.",""),e=e.split(":")[0]},manageConnectionsMetaboxes:function(e,o){for(var i,n=e.to,s=n.length-1;s>=0;s--){i=n[s];var a=e.from+"_to_"+i,r=t("[data-p2p_type="+a+"].p2p-box"),c=t("#fm-pedestal_"+o+"_connections-0-"+i+"-0-tab .fm-group-inner");0!==r.length&&0!==c.length&&(c.append(r.parent().html()),r.closest(".postbox").remove())}},reorderExcerptBox:function(){var e=t("#postexcerpt");e.find("p").hide(),e.insertAfter("#titlediv").css("margin-top",20),t("#title").one("focus",function(){t(this).off(".editor-focus")})},toggleIOTDEmbedField:function(){t(".post-type-pedestal_embed #fm-embed_url-0").on("blur",function(e){var o=t(e.target),i=t("#fm_meta_box_daily_insta_date");-1!==o.val().indexOf("instagr")?i.show():i.hide(),fm.datepicker.add_datepicker(e)}).blur()}};t(document).ready(function(){e.init()})}(jQuery);