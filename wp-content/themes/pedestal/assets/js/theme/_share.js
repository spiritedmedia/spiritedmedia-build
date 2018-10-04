/* global PedUtils */
function ShareButtons($) {
  this.result = false;

  var $buttonsContainer = $('.js-share-buttons');

  if (!$buttonsContainer.length) {
    return;
  }

  var $win = $(window);
  var $body = $('body');
  var $header = $('.js-main-header');

  var $buttons = $buttonsContainer.find('.js-share-button');
  $buttons.attr('data-ga-label', 'top').data('ga-label', 'top');

  this.getCutoffs = function() {
    var $adminBar = $('#wpadminbar');
    var headerOffset = $header.offset();
    var footerOffset = $('.js-main-footer').offset();
    var top = headerOffset ? headerOffset.top + $header.height() : 0;
    var bottom = footerOffset ? footerOffset.top - $win.height() : 0;
    if ($adminBar.length > 0) {
      top -= $adminBar.height();
    }
    return {
      top: top,
      bottom: bottom
    };
  };
  this.cutoffs = this.getCutoffs();

  // eslint-disable-next-line no-unused-vars
  this.scroll = function() {
    var currentPosition = $win.scrollTop();

    if (
      currentPosition > this.cutoffs.top
      && currentPosition < this.cutoffs.bottom
    ) {
      $body.addClass('has-sticky-share-buttons');
      $buttons.attr('data-ga-label', 'sticky').data('ga-label', 'sticky');
    } else {
      $body.removeClass('has-sticky-share-buttons');
      $buttons.attr('data-ga-label', 'top').data('ga-label', 'top');
    }
  };

  this.resize = function() {
    this.cutoffs = this.getCutoffs();
  };

  this.result = true;
}

/**
 * Activate new share buttons
 */
jQuery(document).ready(function($) {
  var $win = $(window);
  var obj = new ShareButtons($);
  var result = obj.result;
  if (!result) {
    return false;
  }

  var scrollCb = $.proxy(obj.scroll, obj);
  $win.on('scroll', PedUtils.throttle(scrollCb, 50));

  var resizeCb = $.proxy(obj.resize, obj);
  $win.on('resize', PedUtils.throttle(resizeCb, 250));

  // Listen to all changes to body and child nodes
  var mutationCb = resizeCb;
  if (typeof MutationObserver === 'function') {
    var observer = new MutationObserver(function(mutations) {
      mutations.forEach(function() {
        mutationCb.call();
      });
    });
    observer.observe(document.body, {
      attributes: false,
      childList: true,
      characterData: false
    });
  }

  return result;
});
