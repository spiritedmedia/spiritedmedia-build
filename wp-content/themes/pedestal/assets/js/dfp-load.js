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
  // Dynamically load the DFP ID from the data attribute of this script
  var DFP_ID = document.getElementById('dfp-load')
    .getAttribute('data-dfp-id');
  // The ad slots we will tell Google Tag about
  var slots = [];
  (function($) {
    // For each ad markup on the page we will get the slot name and accepted
    // sizes for the slot before defining the ad position
    $('.js-dfp').each(function(elIndex, el) {
      var $el = $(el);
      if ($el.css('display') == 'none') {
        // Ad is hidden, don't request an ad
        return;
      }
      var rawSize = $el.data('dfp-sizes');
      var slotName = $el.data('dfp-name');
      var uniqueId = $el.data('dfp-unique');
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
      var id = 'div-gpt-ad-' + slotName + '-' + uniqueId;

      slots.push(googletag
        .defineSlot(path, sizes, id)
        .addService(googletag.pubads())
      );
    });
  }(jQuery));

  // Additional options
  googletag.pubads().enableSingleRequest();
  googletag.pubads().collapseEmptyDivs(true);

  /**
   * Get the ID of the ad unit so it can be selected in the DOM and manipulated
   *
   * @param  {object} slot Slot object returned from the slotRenderEnded
   *                       event listener
   * @return {stirng}      HTML ID of the slot
   */
  function getGoogleDFPUnitID(slot) {
    if (typeof slot !== 'object') {
      return;
    }
    for (var prop in slot) {
      var item = slot[prop];
      if (typeof item == 'object' && item) {
        for (var childProp in item) {
          if (
            typeof item[childProp] == 'string' &&
            item[childProp].indexOf('div-') > -1
          ) {
            return item[childProp];
          }
        }
      }
    }
    return false;
  }
  // Add 'ADVERTISEMENT' disclaimer text before all DFP units
  googletag.pubads().addEventListener('slotRenderEnded', function(e) {
    var div, html;
    if (false === e.isEmpty) {
      var id = getGoogleDFPUnitID(e.slot);
      div = document.getElementById(id);
      html = '<div class="dfp-disclaimer">ADVERTISEMENT</div>';
      div.insertAdjacentHTML('afterbegin', html);
    }
  });
  googletag.enableServices();
});
