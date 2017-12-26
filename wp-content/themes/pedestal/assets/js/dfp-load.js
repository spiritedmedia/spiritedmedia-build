var googletag = googletag || {};
googletag.cmd = googletag.cmd || [];
(function() {
  var gads = document.createElement('script');
  gads.async = true;
  gads.type = 'text/javascript';
  var useSSL = 'https:' === document.location.protocol;
  gads.src = (useSSL ? 'https:' : 'http:') +
  '//www.googletagservices.com/tag/js/gpt.js';
  var node = document.getElementsByTagName('script')[0];
  node.parentNode.insertBefore(gads, node);
})();

// We dynamically define ad slots on the page based on the ad markup present
googletag.cmd.push(function() {
  // Dyanmically load the DFP ID from the data attribute of this script 
  var DFP_ID = document.getElementById( 'dfp-load' )
    .getAttribute( 'data-dfp-id' );
  // The ad slots we will tell Google Tag about
  var slots = [];
  (function($) {
    // For each ad markup on the page we will get the slot name and accepted
    // sizes for the slot before defining the ad position
    $('.js-dfp').each(function(elIndex, el) {
      var $el = $(el);
      var rawSize = $el.data('dfp-sizes');
      var slotName = $el.data('dfp-name');
      if (!rawSize || !slotName) {
        var msg = 'Pedestal DFP: Slot missing required parameters';
        console.warn(msg, el);
        return;
      }
      var sizes = [];
      $.each(rawSize.split(','), function(sizeIndex, item) {
        item = $.trim(item);
        if (!item) {
          return;
        }
        var dimensions = item.split('x');
        if (dimensions.length !== 2) {
          var msg = 'Pedestal DFP: Bad dimensions!';
          console.warn(msg, item, el);
          return;
        }
        for (var i = 0; i < dimensions.length; i++) {
          dimensions[i] = parseInt(dimensions[i]);
        }
        sizes.push(dimensions);
      });
      var path = '/' + DFP_ID + '/' + slotName;
      var id = 'div-gpt-ad-' + slotName + '-0';
      slots.push(googletag
        .defineSlot(path, sizes, id)
        .addService(googletag.pubads())
      );
    });
  }(jQuery));

  // Additional options
  googletag.pubads().enableSingleRequest();
  googletag.pubads().collapseEmptyDivs(true);

  // Add 'ADVERTISEMENT' disclaimer text after all DFP units
  googletag.pubads().addEventListener('slotRenderEnded', function(e) {
    var id, div, html;
    if (false === e.isEmpty) {
      // e.slot.C should be like /104495818/BP_Header_300x50_M
      id = 'google_ads_iframe_' + e.slot.C + '_0__container__';
      div = document.getElementById(id);
      html = '<div class="dfp-disclaimer">ADVERTISEMENT</div>';
      div.insertAdjacentHTML('beforeend', html);
    }
  });
  googletag.enableServices();
});
