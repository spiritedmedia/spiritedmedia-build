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

// Dynamically load the DFP ID from the data attribute of this script
var DFP_SCRIPT     = document.getElementById('dfp-load');
var DFP_PATH       = DFP_SCRIPT.getAttribute('data-dfp-path');
var DFP_SITE       = DFP_SCRIPT.getAttribute('data-dfp-site');
var DFP_ARTICLE_ID = DFP_SCRIPT.getAttribute('data-dfp-article-id');
var DFP_TOPICS     = DFP_SCRIPT.getAttribute('data-dfp-topics');

jQuery(function($) {

  var screenSize = 'desktop';
  if ('none' === $('.js-rail').css('display')) {
    screenSize = 'mobile';
  }
  // For each ad markup on the page we will get the slot name and accepted
  // sizes for the slot before defining the ad position
  $('.js-dfp').each(function(elIndex, el) {
    var $el = $(el);
    if ($el.css('display') == 'none') {
      // Ad is hidden, don't request an ad
      return;
    }
    var rawSize    = $el.data('dfp-sizes');
    var slotName   = $el.data('dfp-name');
    var uniqueId   = $el.data('dfp-unique');
    var slotTarget = $el.data('dfp-slottarget');
    if (!slotTarget && uniqueId) {
      slotTarget = uniqueId;
    }
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

    // Rename artclbox1, artclbox2 targets to m1, m2 etc. for mobile
    if ('mobile' == screenSize) {
      slotTarget = slotTarget.replace(/artclbox/ig,'m');
    }

    var path = '/' + DFP_PATH;
    // We can't differentiate mobile or desktop from the server so we need to
    // generate the id attributes dynamically here
    var id = DFP_SITE + '-' + screenSize + '-' + slotTarget;
    $el.attr('id', id);

    googletag.cmd.push(function() {
      googletag
        .defineSlot(path, sizes, id)
        .setTargeting('slot', [slotTarget])
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
  // Displaying ads happens in the prebid script
  // with a call to googletag.pubads().refresh();
  googletag.pubads().disableInitialLoad();

  googletag.pubads().addEventListener('slotRenderEnded', handleSlotRenderEnded);

  // Page level targeting
  if (DFP_ARTICLE_ID) {
    googletag.pubads().setTargeting('sm_article', [DFP_ARTICLE_ID]);
  }
  if (DFP_TOPICS) {
    googletag.pubads().setTargeting('sm_topic', DFP_TOPICS.split(' '));
  }

  googletag.enableServices();
});

window.googletag = googletag;
