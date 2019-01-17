import { siteAssetsURL } from 'utils';

/**
 * Detect the presence of content blockers
 *
 * Pass the URL argument `?debug-abd` to enable logging.
 *
 * Pass the URL argument `?show-dev-ads` to do a sanity check with an ad that
 * will always load.
 */
class AdblockerDetection {
  constructor() {
    this.log('constructor', 'Launched!');

    this.detecting = false;
    this.loopIteration = 0;
    this.loopInterval = 50;
    this.loopMaxNumber = 10;
    this._bait = null;

    const loadBait = document.createElement('script');
    loadBait.src = siteAssetsURL() + '/js/ads.js';
    // If ads.js loads successfully, then do further detection
    loadBait.onload = () => this.detect();
    // If there's an error loading ads.js then assume an adblocker is present
    loadBait.onerror = () => {
      this.log('constructor', 'ads.js did not load successfully');
      this.triggerEvent(true);
    };
    document.head.appendChild(loadBait);
  }

  /**
   * Output a log message to the console and to every ad unit container in the
   * document when debug mode is enabled.
   *
   * @param {string} caller Function where this is called, for tracing purposes
   * @param {string} message Message to display
   * @memberof AdblockerDetection
   */
  log(caller, message) {
    /* eslint-disable no-console */
    const debug = location.search.indexOf('debug-abd') > -1;
    if (!debug) {
      return;
    }
    console.log(`[PedestalABD][${caller}] ${message}`);
    // Log to the document's ad message containers for easier mobile debugging
    const containers = document.querySelectorAll('.js-abd-debug');
    if (containers.length === 0) {
      console.log('ABD debug message containers not found!');
      return;
    }
    for (const div of containers) {
      const content = document.createTextNode(message);
      const elem = document.createElement('p');
      elem.appendChild(content);
      div.appendChild(elem);
      div.style.display = 'block';
    }
    /* eslint-enable no-console */
  }

  /**
   * Initiate the detection process
   *
   * @memberof AdblockerDetection
   */
  detect() {
    this.log('detect', 'Detecting...');

    if (this.detecting === true) {
      // eslint-disable-next-line max-len
      this.log('detect', 'Detection was cancelled because there\'s already an ongoing check!');
      return false;
    }
    this.detecting = true;

    this.loop = setInterval(() => {
      this.log('detect', 'A check is in progress...');
      this.checkBait();
      this.loopIteration++;
    }, this.loopInterval);
  }

  /**
   * Check the visual status of the bait element and clean up if adblocker is
   * positively detected or if detection loop is complete (assumed undetected).
   *
   * Adblockers who function by visually hiding DOM elements should be detected.
   *
   * @memberof AdblockerDetection
   */
  checkBait() {
    let detected = false;

    if (this.bait) {
      if (
        document.body.getAttribute('abp') !== null
        || this.bait.offsetParent === null
        || this.bait.offsetHeight == 0
        || this.bait.offsetLeft == 0
        || this.bait.offsetTop == 0
        || this.bait.offsetWidth == 0
        || this.bait.clientHeight == 0
        || this.bait.clientWidth == 0
      ) {
        detected = true;
      }
      const baitTemp = window.getComputedStyle(this.bait);
      if (baitTemp && (
        baitTemp.getPropertyValue('display') == 'none'
        || baitTemp.getPropertyValue('visibility') == 'hidden'
      )) {
        detected = true;
      }
    } else {
      detected = true;
    }

    if (this.loopIteration >= this.loopMaxNumber) {
      this.stopLoop();
    } else {
      /* eslint-disable max-len */
      const loopCount   = this.loopIteration + 1;
      const timeElapsed = loopCount * this.loopInterval;
      const status      = `${loopCount}/${this.loopMaxNumber} ~${timeElapsed}ms`;
      const resultStr   = (detected === true) ? 'positive' : 'negative';
      const message     = `A check (${status}) was conducted and detection is ${resultStr}`;
      this.log('checkBait', message);
      /* eslint-enable max-len */
    }

    if (detected === true) {
      this.stopLoop();
      this.destroyBait();
      this.triggerEvent(true);
      document.body.classList.add('has-abd-positive');
    } else if (this.loop === null) {
      this.destroyBait();
      this.triggerEvent(false);
    }

    this.detecting = false;
  }

  /**
   * Get the bait element
   *
   * If `this._bait` is `null`, attempt to find the element in the document. If
   * the element cannot be found in the document, it's reasonable to assume an
   * ad blocker is interfering.
   *
   * @returns {?Element} Value of `this.bait`
   * @memberof AdblockerDetection
   */
  get bait() {
    if (this._bait === null) {
      const bait = document.querySelector('.adsbox');
      if (bait === null) {
        this.log('getBait', 'Bait not found');
        return false;
      }
      this._bait = bait;
      this.log('get:bait', 'Bait found');
    }
    return this._bait;
  }

  /**
   * Set the bait
   *
   * @memberof AdblockerDetection
   */
  set bait(bait) { this._bait = bait; }

  /**
   * Remove the bait if it exists
   *
   * @memberof AdblockerDetection
   */
  destroyBait() {
    if (this.bait) {
      document.body.removeChild(this.bait);
      this.bait = null;
      this.log('destroyBait', 'Bait has been removed');
    }
  }

  /**
   * Stop the detection loop and clean up
   *
   * @memberof AdblockerDetection
   */
  stopLoop() {
    clearInterval(this.loop);
    this.loop = null;
    this.loopIteration = 0;
    this.log('stopLoop', 'A loop has been stopped');
  }

  /**
   * Trigger a positive/negative event based on the boolean value of the
   * parameter.
   *
   * The events will be namespaced with the prefix `pedABD:`.
   *
   * Since there can only be two states of detection -- detected or not detected
   * -- we only need to create two events: `pedABD:positive` and
   * `pedABD:negative`.
   *
   * @param {boolean} result Result of a detection check
   * @memberof AdblockerDetection
   */
  triggerEvent(result) {
    const resultStr = (result === true) ? 'positive' : 'negative';
    const name = 'pedABD:' + resultStr;
    const event = new CustomEvent(name);
    document.dispatchEvent(event);
    // eslint-disable-next-line max-len
    this.log('triggerEvent', `An event with a ${resultStr} detection was fired`);
  }
}

export default new AdblockerDetection();
