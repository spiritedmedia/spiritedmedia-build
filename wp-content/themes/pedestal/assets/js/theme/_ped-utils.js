/**
 * Helper methods that can be used throughout our codebase
 * @type {Object}
 */

// eslint-disable-next-line no-unused-vars
var PedUtils = {

  /**
   * Execute a function only once after a defined interval of time
   * Example: for firing an event after xms after typing in an input
   *
   * @see https://john-dugan.com/javascript-debounce/
   * @param  function func      A callback to be fired
   * @param  int      wait      How long to delay firing the callback
   * @param  bool     immediate Whether to fire the callback immediatly
   */
  debounce: function(func, wait, immediate) {
    var timeout;
    return function() {
      var context = this;
      var args = arguments;
      var later = function() {
        timeout = null;
        if ( !immediate ) {
          func.apply(context, args);
        }
      };
      var callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait || 200);
      if ( callNow ) {
        func.apply(context, args);
      }
    };
  },

  /**
   * Throttle a function when you want it to execute periodically with an interval in between each execution
   * Example: Scrolling, resizing events
   * @see http://sampsonblog.com/749/simple-throttle-function
   * @param  function callback  A callback to be fired
   * @param  init     limit     The delay between executions
   */
  throttle: function(callback, limit) {
    var wait = false;
    return function () {
      if (!wait) {
        callback.call();
        wait = true;
        setTimeout(function () {
          wait = false;
        }, limit);
      }
    };
  }

};
