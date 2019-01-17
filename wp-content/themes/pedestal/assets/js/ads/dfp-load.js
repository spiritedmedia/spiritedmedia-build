import handleSlotRenderEnded from './components/handleSlotRenderEnded';

var googletag = window.googletag || {};
googletag.cmd = googletag.cmd || [];
(function() {
  var gads = document.createElement('script');
  gads.async = true;
  gads.type  = 'text/javascript';
  gads.src   = 'https://www.googletagservices.com/tag/js/gpt.js';
  var node   = document.getElementsByTagName('script')[0];
  node.parentNode.insertBefore(gads, node);
})();

jQuery(function($) {
  // Dynamically load the DFP ID from the data attribute of this script
  var DFP_ID = document
    .getElementById('dfp-load')
    .getAttribute('data-dfp-id');

  // For each ad markup on the page we will get the slot name and accepted
  // sizes for the slot before defining the ad position
  $('.js-dfp').each(function(elIndex, el) {
    var $el = $(el);
    if ($el.css('display') == 'none') {
      // Ad is hidden, don't request an ad
      return;
    }
    var rawSize  = $el.data('dfp-sizes');
    var slotName = $el.data('dfp-name');
    var uniqueId = $el.data('dfp-unique');
    if (!rawSize || !slotName) {
      var msg = 'Pedestal DFP: Slot missing required parameters';
      console.warn(msg, el);
      return;
    }

    // Determine available sizes for the ad unit
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

    googletag.cmd.push(function() {
      googletag
        .defineSlot(path, sizes, id)
        .addService(googletag.pubads());
      googletag.display(id);
    });
  });
});

// We dynamically define ad slots on the page based on the ad markup present
googletag.cmd.push(function() {

  // Additional options
  googletag.pubads().enableSingleRequest();
  googletag.pubads().collapseEmptyDivs(true);

  googletag.pubads().addEventListener('slotRenderEnded', handleSlotRenderEnded);

  googletag.enableServices();
});

window.googletag = googletag;
