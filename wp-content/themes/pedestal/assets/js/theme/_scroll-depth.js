/* global PedUtils */
/* exported ScrollDepth */

/**
 * Scroll Depth
 *
 * @see https://github.com/robflaherty/jquery-scrolldepth/blob/master/jquery.scrolldepth.js
 * @param  {string} selector Element to track
 * @param  {string} label    Label for GA and event namespace
 * @param  {array}  percs    Percentage whole numbers with no percent symbol
 * @constructor
 */
function ScrollDepth(selector, label, percs) {
  var $ = jQuery;
  var $window = $(window);

  /**
   * Cached elements and marks per element
   *
   * Prevents repeated event sending
   *
   * @type {Array}
   */
  var cache = [];

  /**
   * Selector for the element to track
   *
   * @type {String}
   */
  this.selector = selector;

  /**
   * Label
   *
   * @type {String}
   */
  this.label = label;

  /**
   * Percentages to send events for
   *
   * @type {Array}
   */
  this.percs = percs;

  /**
   * Element to track
   *
   * @type {Object}
   */
  this.$element = $(this.selector);

  /**
   * jQuery scroll event with namespace based on label
   *
   * @type {String}
   */
  this.eventNamespace = 'scroll.depth' + this.label.toCamelCase().capFirst();

  // Bail entirely if the element is absent
  if (!this.$element.length) {
    return;
  }

  /**
   * Send event to Google Analytics
   *
   * @param  {string} action
   * @param  {string} label
   * @param  {int}    value
   */
  function sendEvent(action, label, value) {
    if (typeof window.ga === 'undefined') {
      return;
    }
    window.ga('send', 'event', 'Scroll Depth', action, label, value, {
      'nonInteraction': true
    });
  }

  /**
   * Calculate percentage marks per element
   *
   * @return {object}     Map of percentages => coordinates
   */
  var calculateMarks = $.proxy(function() {
    var perc;
    var markKey;
    var marks = {};
    var percs = this.percs;
    var offset = this.$element.offset().top;
    var height = this.$element.height();

    // Sort percentages numerically
    percs.sort((a, b) => { return a - b; });

    for (var i = 0; i < percs.length; i++) {
      perc = percs[i];
      markKey = perc + '%';
      switch (perc) {
        case 0:
          marks[markKey] = offset;
          break;
        case 100:
          // Cushion to trigger 100% event in iOS
          marks[markKey] = height - 5 + offset;
          break;
        default:
          marks[markKey] = parseInt(height * (perc * 0.01), 10) + offset;
      }
    }

    return marks;
  }, this);

  /**
   * Check specified DOM elements and send events for each tracked mark
   *
   * Recalculates marks on each event in case anything has changed.
   *
   * @param  {int} depth Window scroll depth
   */
  var checkElements = $.proxy(function(depth) {
    if (this.$element.length && depth >= this.$element.offset().top) {
      var percInt;
      // Send events for each mark in the element
      $.each(calculateMarks(), $.proxy(function(perc, coordY) {
        if ($.inArray(perc, cache) === -1 && depth >= coordY) {
          // GA requires values to be integers
          percInt = Math.round(parseFloat(perc));
          sendEvent(perc, this.label, percInt);
          cache.push(perc);
        }
      }, this));
    }
  }, this);

  /**
   * Handle scroll event
   *
   * We calculate document and window height on each scroll event to
   * account for dynamic DOM changes.
   */
  $window.on(this.eventNamespace, PedUtils.throttle($.proxy(function() {
    var winHeight = window.innerHeight || $window.height();
    var scrollDepth = $window.scrollTop() + winHeight;

    // If all marks already hit or element is missing, unbind scroll event
    if (cache.length >= this.percs.length || !this.$element.length) {
      $window.off(this.eventNamespace);
      return;
    }

    // Send the events to GA if they're not cached
    checkElements(scrollDepth);
  }, this), 750));

}
