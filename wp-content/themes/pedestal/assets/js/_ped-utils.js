/* global jQuery */
/* exported PedUtils */

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

/**
 * Escape a string for use in HTML attributes
 *
 * {@link https://stackoverflow.com/a/9756789}
 *
 * @param {boolean} preserveCR
 */
String.prototype.escAttr = function(preserveCR) {
  preserveCR = preserveCR ? '&#13;' : '\n';
  return this
    .replace(/&/g, '&amp;') // This must be the 1st replacement
    .replace(/'/g, '&apos;') // The 4 other predefined entities, required
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\r\n/g, preserveCR) // Must be before the next replacement
    .replace(/[\r\n]/g, preserveCR)
  ;
};

/**
 * Format an object as a string of HTML attributes
 *
 * @return {string} String formatted as `key1="val1" key2="val2"`
 */
Object.defineProperty(Object.prototype, 'toAttsString', {
  value: function() {
    let string = '';
    for (const key in this) {
      if (this.hasOwnProperty(key)) {
        let value = this[key];
        if (Array.isArray(value)) {
          value = value.join(' ');
        }
        if (typeof value === 'string' || value instanceof String) {
          string += `${key}="${value.escAttr()}" `;
        }
      }
    }
    return string;
  },
  enumerable: false
});


/**
 * Helper methods that can be used throughout our codebase
 */
window.PedUtils = new class {

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
  debounce(func, wait, immediate) {
    var timeout;
    return function() {
      var context = this;
      var args = arguments;
      var later = function() {
        timeout = null;
        if (!immediate) {
          func.apply(context, args);
        }
      };
      var callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait || 200);
      if (callNow) {
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
  throttle(callback, limit) {
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
  removeHash() {
    history.pushState('', document.title, window.location.pathname
      + window.location.search);
  }

  /**
   * Focus an input and place the caret at the end of the text
   *
   * @see https://stackoverflow.com/a/4609476
   * @param {jQuery} $elem Input element to focus
   */
  focusAtEnd($elem) {
    if ($elem.length > 0) {
      const elem = $elem[0];
      const elemLen = elem.value.length;
      if (elem.selectionStart || elem.selectionStart == '0') {
        elem.selectionStart = elemLen;
        elem.selectionEnd = elemLen;
        elem.focus();
      }
    }
  }

  /**
   * Generate a semi-random ID-like string
   *
   * N.B. This is not guaranteed to be unique!
   *
   * {@link https://gist.github.com/fiznool/73ee9c7a11d1ff80b81c}
   *
   * @param {int} length
   * @return {string} Semi-random string with the specified length
   */
  genStr(length = 8) {
    let out = '';
    const alphabet = '23456789abdegjkmnpqrvwxyz';
    for (let i = 0; i < length; i++) {
      out += alphabet.charAt(Math.floor(Math.random() * alphabet.length));
    }
    return out;
  }

  /**
   * Get query string paramaters from the current URL
   *
   * {@link https://stackoverflow.com/a/26744533/1119655}
   *
   * @param  {String}        key Optional key to return
   *                             otherwise returns all params
   * @param  {String}        url Optional URL to search
   *                             defaults to location.search
   * @return {Object|String}     Object of key-value pairs
   *                             or single value if key provided
   */
  getURLParams(key = '', url = '') {
    var params = {};
    if (! url) {
      url = location.search;
    }
    url.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(match, param, val) {
      params[param] = val;
    });
    if (key) {
      return params[key];
    }
    return params;
  }

};
