/**
 * Convert a string to camelCase
 *
 * {@link http://stackoverflow.com/a/32604073}
 * @param {string} string
 * @return {string} camelCase string
 */
export function toCamelCase(string) {
  return string
    .replace(/\s(.)/g, function(s) { return s.toUpperCase(); })
    .replace(/\s/g, '')
    .replace(/^(.)/, function(s) { return s.toLowerCase(); });
}

/**
 * Capitalize the first letter of a string
 *
 * @param {string} string
 * @return {string}
 */
export function capFirst(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

/**
 * Escape a string for use in HTML attributes
 *
 * {@link https://stackoverflow.com/a/9756789}
 *
 * @param {string} string
 * @param {boolean} preserveCR
 */
export function escAttr(string, preserveCR) {
  preserveCR = preserveCR ? '&#13;' : '\n';
  return ('' + string) // Forces the conversion to string
    .replace(/&/g, '&amp;') // This must be the 1st replacement
    .replace(/'/g, '&apos;') // The 4 other predefined entities, required
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\r\n/g, preserveCR) // Must be before the next replacement
    .replace(/[\r\n]/g, preserveCR)
  ;
}

/**
 * Format an object as a string of HTML attributes
 *
 * @param {object} object The object to convert to an attributes string
 * @return {string} String formatted as `key1="val1" key2="val2"`
 */
export function toAttsString(object) {
  let string = '';
  for (const key in object) {
    if (object.hasOwnProperty(key)) {
      let value = object[key];
      if (Array.isArray(value)) {
        value = value.join(' ');
      }
      if (typeof value === 'string' || value instanceof String) {
        string += `${key}="${escAttr(value)}" `;
      }
    }
  }
  return string;
}

/**
 * Execute a function only once after a defined interval of time
 *
 * Example: for firing an event after xms after typing in an input
 *
 * {@link https://medium.com/@TCAS3/debounce-deep-dive-javascript-es6-e6f8d983b7a1}
 *
 * @param  function fn        A callback to be fired
 * @param  int      wait      [300] How long to delay firing the callback in ms
 * @param  bool     immediate [false] Whether to fire the callback immediatly
 */
export function debounce(fn, wait = 300, immediate = false) {
  let timeout;
  return function() {
    const functionCall = () => fn.apply(this, arguments);
    const callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(functionCall, wait);
    if (callNow) {
      functionCall();
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
export function throttle(callback, limit) {
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
export function removeHash() {
  history.pushState('', document.title, window.location.pathname
    + window.location.search);
}

/**
 * Focus an input and place the caret at the end of the text
 *
 * @see https://stackoverflow.com/a/4609476
 * @param {jQuery} $elem Input element to focus
 */
export function focusAtEnd($elem) {
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
export function genStr(length = 8) {
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
export function getURLParams(key = '', url = '') {
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

/**
 * Handle semi-responsive iframe sizing
 *
 * Adjust width and height of the iframe based on its parent's width and the
 * aspect ratio specified by `true-height` and `true-width` data atttributes.
 *
 * @export
 * @param {Element} el An iframe element
 */
export function resizeIframe(el) {
  const $el         = $(el);
  let parentWidth = $el.parent().width();

  // Inside an iframe
  if (window.self !== window.top) {
    parentWidth = parent.innerWidth;
  }

  const trueHeight = $el.data('true-height') || 360;
  const trueWidth = $el.data('true-width') || 640;
  const newHeight = (parentWidth / trueWidth) * trueHeight;
  $el.css('height', newHeight + 'px')
    .css('width', parentWidth + 'px');
}

/**
 * Check if a value is equivalent to true
 *
 * {@link https://stackoverflow.com/a/21976486/1801260}
 *
 * @export
 * @param {*} value
 * @returns {boolean}
 */
export function isTrue(value) {
  if (typeof(value) === 'string'){
    value = value.trim().toLowerCase();
  }
  switch (value) {
    case true:
    case 'true':
    case 1:
    case '1':
    case 'on':
    case 'yes':
      return true;
    default:
      return false;
  }
}
