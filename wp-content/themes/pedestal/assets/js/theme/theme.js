import 'foundation-sites/js/foundation/foundation';
import { focusAtEnd } from 'utils';

import handleAnalytics from 'analytics';
import handleDonateForm from 'donateForm';
import handleLazyLoad from 'lazyLoad';
import handleModals from 'modals';
import handleScrollDepth from 'scrollDepth';
import handleShareButtons from 'shareButtons';
import handleSubscriptionForms from 'subscriptionForms';
import { handleTargetedMessages, setTargetAudience } from 'targetedMessages';

import adblockerDetection from 'AdblockerDetection';
import contact from 'Contact';

(function($) {

  var Pedestal = {

    init: function() {
      var $document = $(document);
      $document.foundation();
      $('html').removeClass('no-js').addClass('js');

      handleAnalytics();
      handleDonateForm();
      handleModals();
      handleScrollDepth();
      handleShareButtons();

      if (contact.isFrequentReader()) {
        setTargetAudience('frequent-reader');
      }
      $document.on('pedContact:ready', handleTargetedMessages);

      $('.js-signup-email-form').on('submit', handleSubscriptionForms);
      $('.content-wrapper').on(
        'click', '.js-yt-placeholder-link', handleLazyLoad
      );
      focusAtEnd($('#search-standalone-input'));

      this.disabledAnchors();
      this.honeyPotHelper();

      // Adblocker detection event listeners must be added before the event is
      // fired in `AdblockerDetection` to avoid a race condition
      $document.on('pedABD:positive', () => contact.adblocker = true);
      $document.on('pedABD:negative', () => contact.adblocker = false);
      this.AdblockerDetection = adblockerDetection;
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
