/* exported PedUtils */

/**
 * Helper methods that can be used throughout our codebase
 */
class PedUtils {

  /**
   * Execute a function only once after a defined interval of time
   *
   * Example: for firing an event after xms after typing in an input
   *
   * {@link https://john-dugan.com/javascript-debounce/}
   *
   * @param  function func      A callback to be fired
   * @param  int      wait      How long to delay firing the callback
   * @param  bool     immediate Whether to fire the callback immediatly
   */
  static debounce(func, wait, immediate) {
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
  }

  /**
   * Throttle a function when you want it to execute periodically
   *
   * Example: Scrolling, resizing events
   *
   * {@link http://sampsonblog.com/749/simple-throttle-function}
   *
   * @param  {function} callback  A callback to be fired
   * @param  {int}      limit     The delay between executions
   */
  static throttle(callback, limit) {
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

  /**
   * Remove a hash from the browser location
   *
   * {@link https://stackoverflow.com/a/5298684/1801260}
   */
  static removeHash() {
    history.pushState('', document.title, window.location.pathname
      + window.location.search);
  }
}


/**
 * Convert a string to camelCase
 *
 * @return {string} camelCase string
 */
String.prototype.toCamelCase = function() {
  return this
    .replace(/\s(.)/g, function(s) { return s.toUpperCase(); })
    .replace(/\s/g, '')
    .replace(/^(.)/, function(s) { return s.toLowerCase(); });
};

/**
 * Capitalize the first letter of a string
 *
 * @return {string}
 */
String.prototype.capFirst = function() {
  return this.charAt(0).toUpperCase() + this.slice(1);
};
