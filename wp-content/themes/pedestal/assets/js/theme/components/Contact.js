/* global PedVars, localStorageCookie */

import { getURLParams } from 'utils';

/**
 * Audience/Contact functionality for the frontend
 */
class Contact {

  constructor() {
    /**
     * The name we use to store/get contact data
     * @type {String}
     */
    this.dataStorageKey = 'contactData';

    /**
     * The name we use to store/get contact history data
     * @type {String}
     */
    this.historyStorageKey = 'contactHistory';

    /**
     * The name we use to store/get contact adblocker detection status
     * @type {String}
     */
    this.adblockerStorageKey = 'contactAdblocker';

    /**
     * Versioning so we can force clients to update their data if need be
     * @type {Number}
     */
    this.version = 4;

    this.contactData();
    this.contactHistory();
  }

  contactData() {

    // Capture email address signups to refresh the cookie
    $('.js-signup-email-form').on('pedFormSubmission:success',
      (e, data) => {
        if (!('emailAddress' in data)) {
          return;
        }
        this.fetchData(data.emailAddress, false);
      }
    );

    // Migrate from subscriberData cookie to contactData cookie
    var oldContactData = localStorageCookie('subscriberData');
    if (oldContactData && 'data' in oldContactData) {
      localStorageCookie('subscriberData', '');
      localStorageCookie(this.dataStorageKey, oldContactData);
    }

    var queryStringId = getURLParams('mc_eid');
    var contactData = localStorageCookie(this.dataStorageKey);

    // Bail if we have really bad data
    if (
      !contactData ||
      typeof contactData != 'object' ||
      !('data' in contactData)
    ) {
      this.deleteData();
      this.fetchData(queryStringId);
      return;
    }

    // Bail if we don't have the data we expect
    if (
      !('mc_id' in contactData.data) ||
      !('version' in contactData) ||
      !('updated' in contactData)
    ) {
      this.deleteData();
      this.fetchData(queryStringId);
      return;
    }

    var theId = contactData.data.mc_id;
    // Query string ID takes precedence over ID from cookie
    if (queryStringId) {
      theId = queryStringId;
    }


    // Check if the cookie version is out of date
    if (contactData.version != this.version) {
      this.deleteData();
      this.fetchData(theId);
      return;
    }

    // Check if the cookie is stale...
    var validNumberOfDays = 14;
    // Get now in seconds since epoch
    var now = new Date().getTime() / 1000;
    // Get our last updated time in seconds since epoch
    var updatedCutOff = new Date(contactData.updated).getTime() / 1000;
    // Add the amount of seconds to determine our cutoff timestamp
    updatedCutOff += 60 * 60 * 24 * validNumberOfDays;

    // If the cutoff date is in the past we need to refresh the data
    if (now >= updatedCutOff) {
      this.deleteData();
      this.fetchData(theId);
      return;
    }

    // Make sure we trigger our ready event late enough for other events to
    // listen for it. If the cookie is already present, the ready event is
    // called before other scripts are ready to listen for it.
    $(document).on('ready', () => this.triggerEvent('ready', contactData));
  }

  contactHistory() {
    var history = localStorageCookie(this.historyStorageKey);
    if (! history || ! Array.isArray(history)) {
      history = [];
    }

    // Log the current time stamp and URL path
    history.unshift({
      t: Date.now(),
      u: window.location.pathname
    });

    // Remove items that are too old
    var maximumNumberOfDays = 30;
    var dateCutoff = new Date();
    dateCutoff.setDate(dateCutoff.getDate() - maximumNumberOfDays);
    history = history.filter((item) => {
      return (item.t > dateCutoff.getTime());
    });

    // Store the result
    localStorageCookie(this.historyStorageKey, history);
  }

  /**
   * Determine if contact is a frequent visitor or not
   * @return {Boolean}
   */
  isFrequentReader() {
    var history = localStorageCookie(this.historyStorageKey);
    if (history) {
      // Only count posts
      const posts = history.filter((item) => {
        return (item.u.slice(0, 3) === '/20');
      });
      if (posts.length >= 6) {
        return true;
      }
    }
    return false;
  }

  /**
   * Setter for adblocker detection status
   *
   * @type {?Boolean}
   * @param {Boolean} detected Adblocker detection status to set
   */
  set adblocker(detected) {
    const value = (typeof detected === 'boolean') ? detected : null;
    localStorageCookie(this.adblockerStorageKey, value);
  }

  /**
   * Clear the data for the local cookie key
   */
  deleteData() {
    localStorageCookie(this.dataStorageKey, '');
  }

  /**
   * Make an AJAX request to fetch data from the server
   * @param  {String} id MailChimp Unique ID or email address to
   * pass to server
   * @param {Boolean} Whether to trigger the ready event that other
   * code can hook into
   */
  fetchData(id, triggerReadyEvent = true) {
    // Don't bother to make an AJAX request if our id is false
    if (!id) {
      return;
    }
    var storageKey = this.dataStorageKey;
    var ajaxData = {
      action: 'get_contact_data',
      contactID: id
    };
    $.post(PedVars.ajaxurl, ajaxData, (resp) => {
      if (!resp.success) {
        return;
      }
      localStorageCookie(storageKey, resp.data);
      if (triggerReadyEvent) {
        this.triggerEvent('ready', resp.data);
      }
    });
  }

  /**
   * Trigger a namespaced event
   * @param  {String} eventName The name of the event to trigger
   * @param  {Object} data      Data to pass along to the event listeners
   */
  triggerEvent(eventName, data) {
    var evt = 'pedContact:' + eventName;
    $(document).trigger(evt, [data]);
  }
}

export default new Contact();
