/* global ga objectFitImages ScrollDepth DonateForm Modal PedUtils */

(function($) {

  var Pedestal = {

    init: function() {

      $(document).foundation();
      $('html').removeClass('no-js').addClass('js');

      // Video controls will be hidden for small screens < 480px wide
      this.showVideoControls = ($(window).width() >= 480);

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
        var $el = $(this);
        var $fields = $el.find('.js-form-fields');
        var $submitBtn = $el.find('.js-form-submit');
        var $submitText = $el.find('.js-form-submit-text');
        var $invalidFeedback = $el.find('.js-fail-message');
        var $parentModal = $el.closest('.js-modal');
        var buttonWidth = $submitBtn.width();
        var actionURL = $el.attr('action');
        var actionUrlSeparator = actionURL.indexOf('?') >= 0 ? '&' : '?';
        actionURL += actionUrlSeparator + $.param({'ajax-request': 1});

        $submitBtn.width(buttonWidth);
        $submitBtn.css('padding-left', 0);
        $submitBtn.css('padding-right', 0);
        $submitText.hide();
        $el.removeClass('is-failed');
        $el.addClass('is-loading');

        if ($parentModal.length) {
          $parentModal.removeClass('has-failed-form');
        }

        $.post(actionURL, $el.serialize(), function() {
          if ($el.find('.js-success-message').length) {
            var $successEmail = $el.find('.js-success-message-email');
            var emailAddress = $el.find('.js-email-input').val();

            $fields.hide();
            $el.removeClass('is-loading');
            $el.addClass('is-success');

            if ($parentModal.length) {
              $parentModal.addClass('has-successful-form');
            }

            // Use email address in success message for user verification
            if (emailAddress && $successEmail.length) {
              $successEmail.text(emailAddress).addClass('u-font-weight--bold');
            }
          }
        }).fail(function(response) {
          var msg = response.responseText;
          $el.removeClass('is-loading');
          $el.addClass('is-failed');
          if ($parentModal.length) {
            $parentModal.addClass('has-failed-form');
          }
          $submitText.show();
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
