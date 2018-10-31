import 'foundation-sites/js/foundation/foundation';
import { focusAtEnd } from 'PedUtils';

import handleAnalytics from 'analytics';
import handleDonateForm from 'donateForm';
import handleLazyLoad from 'lazyLoad';
import handleModals from 'modals';
import handleScrollDepth from 'scrollDepth';
import handleShareButtons from 'shareButtons';
import handleSubscriptionForms from 'subscriptionForms';

import Subscriber from 'Subscriber';

(function($) {

  var Pedestal = {

    init: function() {
      $(document).foundation();
      $('html').removeClass('no-js').addClass('js');

      handleAnalytics();
      handleDonateForm();
      handleModals();
      handleScrollDepth();
      handleShareButtons();

      this.Subscriber = new Subscriber;

      $('.js-signup-email-form').on('submit', handleSubscriptionForms);
      $('.content-wrapper').on(
        'click', '.js-yt-placeholder-link', handleLazyLoad
      );
      focusAtEnd($('#search-standalone-input'));

      this.handleWindowResize();
      this.responsiveIframes();
      this.disabledAnchors();
      this.honeyPotHelper();
    },

    /**
     * Handle window resizing
     */
    handleWindowResize: function() {
      var delayedResizeTimer = false;
      $(window).resize(() => {
        if (delayedResizeTimer) {
          clearTimeout(delayedResizeTimer);
        }
        delayedResizeTimer = setTimeout(() => {
          this.responsiveIframes();
        }, 30);
      });
    },

    /**
     * Make some iframes responsive
     */
    responsiveIframes: function() {
      $('.pedestal-responsive').each(function() {
        var el          = $(this);
        var parentWidth = el.parent().width();

        // Inside an iframe
        if (window.self !== window.top) {
          parentWidth = parent.innerWidth;
        }

        var trueHeight = el.data('true-height') ? el.data('true-height') : 360;
        var trueWidth = el.data('true-width') ? el.data('true-width') : 640;
        var newHeight = (parentWidth / trueWidth) * trueHeight;
        $(this).css('height', newHeight + 'px')
          .css('width', parentWidth + 'px');
      });
    },

    /**
     * Disable clicking on anchor tags with the `.disabled` class
     */
    disabledAnchors: function() {
      $('a.disabled').click(function(e) {
        e.preventDefault();
      });
    },

    honeyPotHelper: function() {
      var fullYear = new Date().getFullYear();
      $('.js-pedestal-current-year-check').val(fullYear);
    }
  };

  $(document).ready(function() {
    Pedestal.init();
  });
}(jQuery));
