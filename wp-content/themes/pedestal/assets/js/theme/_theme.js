/* global ga ScrollDepth DonateForm Modal PedUtils */

(function($) {

  var Pedestal = {

    init: function() {

      $(document).foundation();
      $('html').removeClass('no-js').addClass('js');

      // Video controls will be hidden for small screens < 480px wide
      this.showVideoControls = ($(window).width() >= 480);

      this.bindEvents();
      this.handleSubscriptionForms();
      this.responsiveIframes();
      this.disabledAnchors();
      this.analyticsEventTracking();
      this.scrollDepthTracking();
      this.lazyLoad();
      this.setupModals();

      DonateForm();

      PedUtils.focusAtEnd($('#search-standalone-input'));
    },

    /**
     * Show video controls?
     */
    showVideoControls: true,

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
      $('.js-signup-email-form').on('submit', function(e) {
        e.preventDefault();
        var $el                = $(this);
        var $submitBtn         = $el.find('.js-form-submit');
        var $invalidFeedback   = $el.find('.js-fail-message');
        var actionURL          = $el.attr('action');
        var actionUrlSeparator = actionURL.indexOf('?') >= 0 ? '&' : '?';
        actionURL += actionUrlSeparator + $.param({ 'ajax-request': 1 });

        $el.removeClass('is-failed');
        $el.addClass('is-loading');
        $submitBtn.prop('disabled', true);

        $.post(actionURL, $el.serialize(), function() {
          if ($el.find('.js-success-message').length) {
            var $successEmail = $el.find('.js-success-message-email');
            var emailAddress = $el.find('.js-email-input').val();

            $el.removeClass('is-loading');
            $el.addClass('is-success');

            // Let other functions know a form submission with an email
            // address happened
            $el.trigger('pedFormSubmission:success', [{
              'emailAddress': emailAddress
            }]);

            // Use email address in success message for user verification
            if (emailAddress && $successEmail.length) {
              $successEmail.text(emailAddress).addClass('u-font-weight--bold');
            }
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
        }).always(function() {
          $submitBtn.prop('disabled', false);
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

      function debugEvent(eventCategory, eventAction, eventLabel, eventValue) {
        /* eslint-disable no-console */
        console.group('Google Analytics Event Data');
        console.log('Category: ', eventCategory);
        console.log('Action: ', eventAction);
        console.log('Label: ', eventLabel);
        if (eventValue) {
          console.log('Value: ', eventValue);
        }
        console.groupEnd();
        /* eslint-enable no-console */
      }

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
          var eventAction = $this.data('ga-action') || e.currentTarget.href;
          var eventLabel = $this.data('ga-label');
          var eventValue = parseInt($this.data('ga-value')) || null;
          if (debugging) {
            debugEvent(eventCategory, eventAction, eventLabel, eventValue);
            e.preventDefault();
            return;
          }
          ga(
            'send',
            'event',
            eventCategory,
            eventAction,
            eventLabel,
            eventValue
          );
        })
        .on('submit', 'form[data-ga-category]', function(e) {
          var $this = $(this);
          var eventCategory = $this.data('ga-category');
          var eventAction = $this.data('ga-action') || $this.attr('action');
          var eventLabel = $this.data('ga-label');
          var eventValue = null;

          // Donate form is slightly different
          if ($this.is('.js-donate-form')) {
            eventAction = $this.find('.js-donate-form-frequency:checked').val();
            if (! eventAction) {
              eventAction = 'once';
            }
            eventValue = $this.find('.js-donate-form-amount').val();
          }

          if (debugging) {
            debugEvent(eventCategory, eventAction, eventLabel, eventValue);
            e.preventDefault();
            return;
          }
          ga(
            'send',
            'event',
            eventCategory,
            eventAction,
            eventLabel,
            eventValue
          );
        });

      $('.js-main').on('click', 'a', function(e) {
        var $this = $(this);
        var eventCategory = $this.data('ga-category');
        if (eventCategory) {
          // Let the other event handler handle the tracking
          return;
        }

        eventCategory = 'post-content';
        var eventAction = $this.data('ga-action') || e.currentTarget.href;
        var eventLabel = $this.data('ga-label') || 'link';
        var eventValue = parseInt($this.data('ga-value')) || null;
        if (debugging) {
          debugEvent(eventCategory, eventAction, eventLabel, eventValue);
          e.preventDefault();
          return;
        }
        ga('send', 'event', eventCategory, eventAction, eventLabel, eventValue);
      });

      $('.js-rail').on('click', 'a', function(e) {
        var $this = $(this);
        var eventCategory = $this.data('ga-category');
        if (eventCategory) {
          // Let the other event handler handle the tracking
          return;
        }

        eventCategory = 'sidebar';
        var eventAction = $this.data('ga-action') || e.currentTarget.href;
        var eventLabel = $this.data('ga-label') || 'link';
        var eventValue = parseInt($this.data('ga-value')) || null;
        if (debugging) {
          debugEvent(eventCategory, eventAction, eventLabel, eventValue);
          e.preventDefault();
          return;
        }
        ga('send', 'event', eventCategory, eventAction, eventLabel, eventValue);
      });

      // Send events when subscribers/members/donors view a page
      $(document).on('pedSubscriber:ready', function(e, data) {
        var eventCategory = 'reader-cookie-set';
        var eventAction = window.location.href;
        var eventLabel = 'subscriber';
        if (data.data.current_member) {
          eventLabel = 'member';
        } else if (data.data.donate_365 > 0) {
          eventLabel = 'donor';
        }
        if (debugging) {
          /* eslint-disable no-console */
          console.group('Subscriber Google Analytics Event Data');
          console.log('Category: ', eventCategory);
          console.log('Action: ', eventAction);
          console.log('Label: ', eventLabel);
          console.groupEnd();
          /* eslint-enable no-console */
          return;
        }
        ga('send', 'event', eventCategory, eventAction, eventLabel);
      });

    }, // end analyticsEventTracking()

    scrollDepthTracking: function() {
      new ScrollDepth(
        '.js-original-content-body',
        '',
        [0, 50, 100]
      );
    },

    lazyLoad: function() {
      const controls = this.showVideoControls ? 1 : 0;

      $('.content-wrapper').on('click', '.js-yt-placeholder-link', function(e) {
        const $this = $(this);
        const youTubeID = $this.data('youtube-id');

        if (!youTubeID) {
          return;
        }

        const $parent = $this.parents('.js-yt-placeholder');
        const params = {
          autoplay: 1,
          cc_load_policy: 1,
          color: 'white',
          controls: controls,
          rel: 0,
          showinfo: 0
        };
        const query = $.param(params);
        const iframeURL = `https://www.youtube.com/embed/${youTubeID}?${query}`;

        let youTubeIframe = '<iframe ';
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
    },

    /**
     * A helper for creating modals purely from markup
     *
     * 1. Add the class `.js-modal-trigger` to the element you want to have
     *    trigger the modal
     * 2. Add the modal's inner content to a template, preferably in
     *    `views/partials/modals/`
     * 3. Give its outermost element an ID attribute, preferably with `modal`
     *    somewhere in the ID for the sake of clarifying its purpose.
     * 4. Also give the outermost element a `data-modal-class` attribute
     *    containing the classes you want applied to the root modal element
     * 5. Add a `data-modal-id` attribute to the modal trigger element --
     *    set the target element's ID as the value
     *
     * @example
     * <button class="js-modal-trigger" data-modal-id="foo-modal">
     *   Show Modal
     * </button>
     * <div id="foo-modal" data-modal-class="modal--foo">My modal content</div>
     */
    setupModals: function() {
      $('.js-modal-trigger').each(function(index, elem) {
        var $elem = $(elem);
        var target = $elem.data('modal-id');
        if (!target) {
          return;
        }

        var theModal = new Modal({
          target: target
        });

        // When search modal is opened set focus to the search field
        if (target == 'search-modal') {
          theModal.on('modal:opened', function() {
            const $theField = $('.js-modal-search-field');
            PedUtils.focusAtEnd($theField);
          });
        }

        $elem.on('click', theModal, function(e) {
          var modal = e.data;
          modal.open();
          e.preventDefault();
        });
      });
    }
  };

  $(document).ready(function() {
    Pedestal.init();
  });
}(jQuery));
