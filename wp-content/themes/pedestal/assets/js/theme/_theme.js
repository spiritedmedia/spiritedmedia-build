/* global ga objectFitImages ScrollDepth */

(function($) {

  var Pedestal = {

    init: function() {

      $(document).foundation();
      $('html').removeClass('no-js').addClass('js');

      // CSS object-fit polyfill
      // https://github.com/bfred-it/object-fit-images/
      objectFitImages('.js-stream-item-img img', {watchMQ: true});

      this.bindEvents();
      this.handleSubscriptionForms();
      this.responsiveIframes();
      this.disabledAnchors();
      this.analyticsEventTracking();
      this.scrollDepthTracking();
      this.honeyPotHelper();
      this.lazyLoad();
    },

    /**
     * Bind all events
     */
    bindEvents: function() {
      var delayedResizeTimer = false;
      $(window).resize($.proxy(function() {
        if (delayedResizeTimer) {
          clearTimeout(delayedResizeTimer);
        }
        delayedResizeTimer = setTimeout($.proxy(function() {
          this.responsiveIframes();
        }, this), 30);
      }, this));
    },

    /**
     * Handle email subscription form submission and errors
     */
    handleSubscriptionForms: function() {
      var subscriptionForms = [
        '.js-follow-this-form-container',
        '#subscribe-to-newsletter-page',
        '.widget_pedestal_signup_newsletter'
      ];

      $(subscriptionForms.join(', ')).find('form').on('submit', function(e) {
        e.preventDefault();
        var $el = $(this);
        var $fields = $el.find('.js-form-fields');
        var $submitBtn = $el.find('.js-form-submit');
        var $invalidFeedback = $el.find('.js-fail-message');
        var buttonWidth = $submitBtn.width();
        var actionURL = $el.attr('action');
        var actionUrlSeparator = actionURL.indexOf('?') >= 0 ? '&' : '?';
        actionURL += actionUrlSeparator + $.param({'ajax-request': 1});

        $submitBtn.width(buttonWidth);
        $submitBtn.css('padding-left', 0);
        $submitBtn.css('padding-right', 0);
        $el.removeClass('is-failed');
        $el.addClass('is-loading');

        $.post(actionURL, $el.serialize(), function() {
          if ($el.find('.js-success-message').length) {
            $fields.hide();
            $el.removeClass('is-loading');
            $el.addClass('is-success');
          }
        }).fail(function(response) {
          var msg = response.responseText;
          $el.removeClass('is-loading');
          $el.addClass('is-failed');
          if ($invalidFeedback.length && msg.length) {
            $invalidFeedback.text(msg);
          } else {
            $submitBtn.before(msg);
          }
        });
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

    /**
     * Google Analytics event tracking
     *
     * https://developers.google.com/analytics/devguides/collection/analyticsjs/events
     */
    analyticsEventTracking: function() {
      var debugging = false;
      if ($('body').is('.js-debug-ga')) {
        debugging = true;
      }
      if (typeof ga !== 'function' && !debugging) {
        return;
      }
      $('body')
        .on('click', 'a[data-ga-category]', function(e) {
          var $this = $(this);
          var eventCategory = $this.data('ga-category');
          var eventAction = e.currentTarget.href;
          var eventLabel = $this.data('ga-label');
          if (debugging) {
            /* eslint-disable no-console */
            console.group('Google Analytics Event Data');
            console.log('Category: ', eventCategory);
            console.log('Action: ', eventAction);
            console.log('Label: ', eventLabel);
            console.groupEnd();
            /* eslint-enable no-console */
            e.preventDefault();
            return;
          }
          ga('send', 'event', eventCategory, eventAction, eventLabel);
        })
        .on('submit', 'form[data-ga-category]', function(e) {
          var $this = $(this);
          var eventCategory = $this.data('ga-category');
          var eventAction = $this.attr('action');
          var eventLabel = $this.data('ga-label');
          if (debugging) {
            /* eslint-disable no-console */
            console.group('Google Analytics Event Data');
            console.log('Category: ', eventCategory);
            console.log('Action: ', eventAction);
            console.log('Label: ', eventLabel);
            console.groupEnd();
            /* eslint-enable no-console */
            e.preventDefault();
            return;
          }
          ga('send', 'event', eventCategory, eventAction, eventLabel);
        });

    }, // end analyticsEventTracking()

    scrollDepthTracking: function() {
      new ScrollDepth(
        '.js-original-content-body',
        'Original Content Body',
        [0, 50, 100]
      );
    },

    honeyPotHelper: function() {
      var fullYear = new Date().getFullYear();
      $('.js-pedestal-current-year-check').val(fullYear);
    }, // end honeyPotHelper()

    lazyLoad: function() {
      $('.content-wrapper').on('click', '.js-yt-placeholder-link', function(e) {
        var $this = $(this);
        var youTubeID = $this.data('youtube-id');
        if (!youTubeID) {
          return;
        }
        var $parent = $this.parents('.js-yt-placeholder');
        var iframeURL = 'https://www.youtube.com/embed/';
        iframeURL += youTubeID + '?showinfo=0&autoplay=1';
        var youTubeIframe = '<iframe ';
        youTubeIframe += 'src="' + iframeURL + '" ';
        youTubeIframe += 'frameborder="0" ';
        youTubeIframe += 'allowfullscreen ';
        youTubeIframe += '/>';
        // Append the iFrame so it can load a little bit
        $parent.append(youTubeIframe);
        // Fadeout the play icon and link
        $this.fadeOut(750, function() {
          $this.remove();
        });
        e.preventDefault();
      });
    }
  };

  $(document).ready(function() {
    Pedestal.init();
  });
}(jQuery));
