/* global ga */

/**
 * Google Analytics event tracking
 *
 * https://developers.google.com/analytics/devguides/collection/analyticsjs/events
 */
export default function analytics() {

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

}
