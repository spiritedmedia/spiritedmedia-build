jQuery(document).ready(function(a){function b(b){var c=1200,d=Math.abs(a(document.body).scrollTop()-b);return d/c*1e3}a(".js-main").on("click",".js-footnote-link",function(c){var d=a(".js-entity-share.fixed"),e=this.href.split("#")[1],f=a("#"+e),g=0;d.length>0&&(g=d.height());var h=f.offset().top-g,i=b(h);a("html, body").animate({scrollTop:h},i),c.preventDefault(),f.attr("tabindex",0).focus()})});