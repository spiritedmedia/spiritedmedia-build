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

/**
 * Define all of our slots in one go
 * @param  {array} meta Slot meta to define slots
 * @return {array}      Array of DFP slot objects
 */
var globalSlots = function(meta) {
  var slots = [];
  for (var i = meta.length - 1; i >= 0; i--) {
    var slotMeta = meta[i];
    var path = '/104495818/' + slotMeta.name;
    var id = 'div-gpt-ad-' + slotMeta.name + '-0';
    slots.push(googletag
      .defineSlot(path, slotMeta.size, id)
      .addService(googletag.pubads())
    );
  }
  return slots;
};

googletag.cmd.push(function() {
  globalSlots(PedestalChildSlotsConfig);

  // Additional options
  googletag.pubads().enableSingleRequest();
  googletag.pubads().collapseEmptyDivs(true);

  // Add 'ADVERTISEMENT' disclaimer text after all DFP units
  googletag.pubads().addEventListener('slotRenderEnded', function(e) {
    var id, div, html;
    if (false === e.isEmpty) {
      id = e.slot.m.m;
      div = document.getElementById(id);
      html = '<div class="dfp-disclaimer">ADVERTISEMENT</div>';
      div.insertAdjacentHTML('beforeend', html);
    }
  });

  googletag.enableServices();
});
