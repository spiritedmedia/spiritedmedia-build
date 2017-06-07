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
 // eslint-disable-next-line no-unused-vars
function showNewShareButtons() {
  // If jQuery isn't available then bail!
  if ( ! window.jQuery ) {
    return false;
  }

  // Set $ to jQuery
  var $ = window.jQuery;
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

  if ( typeof MutationObserver === 'function' ) {
    var observer = new MutationObserver(function(mutations) {

      // For the sake of...observation...let's output the mutation to console to see how this all works
      mutations.forEach(function() {
        // console.log(mutation.type);
        resizeCb.call();
      });
    });

    // Notify me of everything!
    var observerConfig = {
      attributes: false,
      childList: true,
      characterData: false
    };

    // Node, config
    // In this case we'll listen to all changes to body and child nodes
    // var targetNode = $('.c-main__content')[0];
    var targetNode = document.body;
    observer.observe(targetNode, observerConfig);
  }
  return result;
}
