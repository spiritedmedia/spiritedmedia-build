import { throttle } from 'utils';

function ShareButtons($) {
  this.result = false;

  var $buttonsContainerTop = $('.js-share-buttons-top');
  var $buttonsContainerBottom = $('.js-share-buttons-bottom');
  var $bottomPlaceholder = $('.js-share-buttons-bottom-placeholder');

  if (!$buttonsContainerTop.length || !$buttonsContainerBottom.length) {
    return;
  }

  var $win = $(window);

  var $bottomButtons = $buttonsContainerBottom.find('.js-share-button');

  this.getCutoffs = function() {
    var $adminBar = $('#wpadminbar');
    var topOffset = $buttonsContainerTop.offset();
    var top = topOffset ? topOffset.top + $buttonsContainerTop.height() : 0;
    var bottomOffset = $bottomPlaceholder.offset();
    var bottom = bottomOffset ? bottomOffset.top - $win.height() : 0;
    if ($adminBar.length > 0) {
      top -= $adminBar.height();
    }
    return { top, bottom };
  };

  this.cutoffs = this.getCutoffs();

  this.scroll = function() {
    var currentPosition = $win.scrollTop();

    if (currentPosition < this.cutoffs.top) {
      $buttonsContainerBottom.hide();
    } else {
      $buttonsContainerBottom.show();

      if (currentPosition < this.cutoffs.bottom) {
        this.updateBottomButtonsLabel('sticky');
      } else {
        this.updateBottomButtonsLabel('bottom');
      }
    }
  };

  this.resize = function() {
    this.cutoffs = this.getCutoffs();
  };

  this.updateBottomButtonsLabel = function(position) {
    $bottomButtons
      .attr('data-ga-label', position)
      .data('ga-label', position);
  };

  this.result = true;
}

export default function() {
  jQuery(window).on('load', function() {
    var $ = jQuery;
    var $win = $(window);
    var obj = new ShareButtons($);
    var result = obj.result;
    if (!result) {
      return false;
    }

    obj.updateBottomButtonsLabel('sticky');

    var scrollCb = $.proxy(obj.scroll, obj);
    $win.on('scroll', throttle(scrollCb, 50));

    var resizeCb = $.proxy(obj.resize, obj);
    $win.on('resize', throttle(resizeCb, 250));

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
}
