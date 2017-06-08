/* global PedUtils */
function NewShareButtons($) {
  this.result = false;
  // Check if there is a follow buton on the page, if so bail!
  if ( $('[data-reveal-id="follow-cluster"]').length > 0 ) {
    return this.result;
  }

  var $win = $(window);
  var $body = $('body');
  $body.addClass('show-new-share-buttons');

  this.getCuttoffs = function() {
    var $adminBar = $('#wpadminbar');
    var top = $('.c-main__header').offset().top + $('.c-main__header').height();
    if ( $adminBar.length > 0 ) {
      top -= $adminBar.height();
    }
    var bottom = $('.c-main__footer').offset().top - $win.height();
    // console.log( 'getCuttoffs:', top, bottom );
    return {
      top: top,
      bottom: bottom
    };
  };
  this.cutoffs = this.getCuttoffs();

   // eslint-disable-next-line no-unused-vars
  this.scroll = function() {
    var currentPosition = $win.scrollTop();
    // console.log( 'Scroll:', currentPosition );

    if (currentPosition > this.cutoffs.top && currentPosition < this.cutoffs.bottom) {
      $body.addClass('make-new-share-buttons-sticky');
    } else {
      $body.removeClass('make-new-share-buttons-sticky');
    }
  };

  this.resize = function() {
    this.cutoffs = this.getCuttoffs();
  };

  this.result = true;
  return this.result;
}

/**
 * Activate new share buttons and hide the top-bar/action bar
 */
jQuery(document).ready(function($) {
  // Look for hidden styles applied by Google Optimize
  // If not found then the test isn't running and bail
  if ( $('.c-overview__title').css('borderBottomWidth') !== '2px' ) {
    return false;
  }

  var $win = $(window);
  var obj = new NewShareButtons($);
  var result = obj.result;
  if ( !result ) {
    return false;
  }

  var scrollCb = $.proxy(obj.scroll, obj);
  $win.on( 'scroll', PedUtils.throttle( scrollCb, 50 ) );

  var resizeCb = $.proxy(obj.resize, obj);
  $win.on( 'resize', PedUtils.throttle( resizeCb, 250 ) );

  var mutationCb = $.proxy(obj.resize, obj);
  if ( typeof MutationObserver === 'function' ) {
    var observer = new MutationObserver(function(mutations) {
      mutations.forEach(function() {
        mutationCb.call();
      });
    });

    // Notify me of everything!
    var observerConfig = {
      attributes: false,
      childList: true,
      characterData: false
    };

    // Listen to all changes to body and child nodes
    var targetNode = document.body;
    observer.observe(targetNode, observerConfig);
  }
  return result;
});
