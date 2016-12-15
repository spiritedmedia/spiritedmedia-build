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

          ga('send', 'event', 'newsletter', 'subscribe');
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

      var el, position, $storyBarTitle, sourceName, postType, isExternal,
        isOriginal, storyTitle, btnHTML, shareDest;
      var entityLink = '.js-stream-items .js-entity-permalink';
      var storyLink = '.js-stream-item .js-story-bar .js-blox-link';
      $shareButtons    = $('.js-share-modal-btn');

      // Entities in stream
      $('body.home').on('click', entityLink, function(obj) {
        $el        = $(this);
        $entity    = $el.closest('.entity');
        entityHTML = $entity[0];
        sourceName = entityHTML.getAttribute('data-source-name');
        postType   = entityHTML.getAttribute('data-post-type');
        isOriginal = entityHTML.hasAttribute('data-editorial-content');
        isExternal = entityHTML.hasAttribute('data-source-external');
        i          = $entity.index();

        if (!sourceName) {
          sourceName = postType;
        }

        if (isOriginal) {
          sourceName = 'Original content';
        }

        ga('send', {
          'hitType': 'event',
          'eventCategory': postType + '_entity_in_home_stream',
          'eventAction': 'click',
          'eventLabel': sourceName,
          'eventValue': i,
          'nonInteraction': isExternal
        });
      });

      // Story banners in stream
      $('body.home').on('click', storyLink, function(obj) {
        $el        = $(this);
        $entity    = $el.closest('.entity');
        entityHTML = $entity[0];
        i          = $entity.index();

        $storyBarTitle  = $entity.find('.js-story-bar .js-blox-title');
        storyTitle = ($storyBarTitle.text()) ? $storyBarTitle.text() : null;

        ga('send', {
          'hitType': 'event',
          'eventCategory': 'story_banner_in_stream',
          'eventAction': 'click',
          'eventLabel': storyTitle,
          'eventValue': i
        });
      });

      // Share buttons in modal
      $shareButtons.on('click', function(obj) {
        $el       = $(this);
        btnHTML   = $el[0];
        shareDest = btnHTML.getAttribute('data-share-dest');

        ga('send', {
          'hitType': 'event',
          'eventCategory': 'share_button_in_modal',
          'eventAction': 'click',
          'eventLabel': shareDest
        });
      });

    }, // end analyticsEventTracking()

    honeyPotHelper: function() {
      var fullYear = new Date().getFullYear();
      $('.js-pedestal-current-year-check').val(fullYear);
    }

  };

  $(document).ready(function() {
    Pedestal.init();
  });
}(jQuery));
