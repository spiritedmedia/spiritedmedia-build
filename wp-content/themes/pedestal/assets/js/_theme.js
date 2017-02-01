(function($) {

  var Pedestal = {

    init: function() {

      // Libraries
      $(document).foundation();
      new FastClick(document.body);

      this.bindEvents();
      this.responsiveIframes();
      this.disabledAnchors();
      this.revealModalClose();
      this.revealModalPreventScrolling();
      this.analyticsEventTracking();
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

      var subscriptionForms = [
        '#follow-cluster',
        '#subscribe-to-newsletter',
        '#subscribe-to-newsletter-page',
        '.widget_pedestal_signup_newsletter'
      ];

      $(subscriptionForms.join(', ')).find('form').on('submit', function(e) {
        e.preventDefault();
        var el        = $(this);
        var confirmId = el.data('confirm-id');
        var $submitBtn = el.find('.js-form-submit');
        var $spinner = $submitBtn.find('.js-spinner');
        var buttonWidth = $submitBtn.width();
        var actionURL = el.attr('action');
        var actionUrlSeparator = actionURL.indexOf('?') >= 0 ? '&' : '?';
        actionURL += actionUrlSeparator + $.param({'ajax-request': 1});

        $submitBtn.width(buttonWidth);
        $submitBtn.css('padding-left', 0);
        $submitBtn.css('padding-right', 0);
        el.find('.alert').remove();
        el.addClass('is-loading');

        $.post(actionURL, el.serialize(), function(response) {
          if (typeof confirmId !== 'undefined') {
            $('#' + confirmId).foundation('reveal', 'open');
          } else if (el.find('.success-message').length) {
            el.removeClass('is-loading');
            el.find('.form-fields').hide();
            el.find('.success-message').show();
          }
        }).fail(function(response) {
          var alert = $('<div data-alert class="alert-box alert"></div>');
          el.removeClass('is-loading');
          alert.text(response.responseText);
          if (el.find('h3').length) {
            el.find('h3').after(alert);
          } else {
            el.prepend(alert);
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
     * Close the modal when a social button has been clicked
     */
    revealModalClose: function()  {

      $(document).on('click', '.js-share-modal-btn', function() {
        $('.js-share-modal').foundation('reveal', 'close');
      });

    },

    /**
     * Prevent body from scrolling when Reveal modal is open
     */
    revealModalPreventScrolling: function() {

      $(document).on('open.fndtn.reveal', '[data-reveal]', function() {
        $('body').addClass('noscroll');
        $('body').bind('touchmove', function(e) {
          e.preventDefault();
        });
      });

      $(document).on('close.fndtn.reveal', '[data-reveal]', function() {
        $('body').removeClass('noscroll');
        $('body').unbind('touchmove');
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
        if (typeof ga === 'undefined' && !debugging) {
          return;
        }
        $('body')
          .on('click', 'a[data-ga-category]', function(e) {
            var $this = $(this);
            var eventCategory = $this.data('ga-category');
            var eventAction = e.currentTarget.href;
            var eventLabel = $this.data('ga-label');
            if (debugging) {
              console.group('Google Analytics Event Data');
              console.log('Category: ', eventCategory);
              console.log('Action: ', eventAction);
              console.log('Label: ', eventLabel);
              console.groupEnd();
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
                console.group('Google Analytics Event Data');
                console.log('Category: ', eventCategory);
                console.log('Action: ', eventAction);
                console.log('Label: ', eventLabel);
                console.groupEnd();
                e.preventDefault();
                return;
              }
              ga('send', 'event', eventCategory, eventAction, eventLabel);
            });

      }, // end analyticsEventTracking()

    honeyPotHelper: function() {
      var fullYear = new Date().getFullYear();
      $('.js-pedestal-current-year-check').val(fullYear);
    }, // end honeyPotHelper()

    lazyLoad: function() {
      $('.content-wrapper').on('click', '.js-yt-placeholder-link', function(e) {
        $this = $(this);
        var youTubeID = $this.data('youtube-id');
        if (!youTubeID) {
          return;
        }
        $parent = $this.parents('.js-yt-placeholder');
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
