/* global PedVars, localStorageCookie */

import { getURLParams } from 'utils';

/**
 * Audience/Contact functionality for the frontend
 */
export default class Contact {

  constructor() {
    /**
     * The name we use to store/get data
     * @type {String}
     */
    this.storageKey = 'contactData';

    /**
     * Versioning so we can force clients to update their data if need be
     * @type {Number}
     */
    this.version = 3;

    // Make sure we trigger our ready event late enough for other events to
    // listen for it. If the cookie is already present, the ready event is
    // called before other scripts are ready to listen for it.
    $(document).on('ready', () => {
      // Migrate from subscriberData cookie to contactData cookie
      var oldContactData = localStorageCookie('subscriberData');
      if (oldContactData && 'data' in oldContactData) {
        localStorageCookie('subscriberData', '');
        localStorageCookie(this.storageKey, oldContactData);
      }
      var contactData = localStorageCookie(this.storageKey);
      if (contactData && 'data' in contactData) {
        this.triggerEvent('ready', contactData);
      }
    });

    // Capture email address signups to refresh the cookie
    $('.js-signup-email-form').on('pedFormSubmission:success',
      (e, data) => this.listenForEmailSignups(e, data)
    );
    this.maybeRefresh();
  }

  /**
   * Check if our stored data needs to be refreshed from the server or not
   *
   * * @return {bool} Whether it was determined to refresh the data or not
   */
  maybeRefresh() {
    // MailChimp's Unique Email Identifier
    var mcEid = getURLParams('mc_eid');
    var contactData = localStorageCookie(this.storageKey);
    if (contactData && 'data' in contactData) {

      var oldId = false;
      if ('mc_id' in contactData.data) {
        oldId = contactData.data.mc_id;
      }

      // If the cookie version is out of date
      if ('version' in contactData) {
        if (contactData.version != this.version) {
          this.fetchData(oldId);
          return true;
        }
      }

      // If the cookie is stale...
      if ('updated' in contactData) {
        var validNumberOfDays = 14;
        // Get now in seconds since epoch
        var now = new Date().getTime() / 1000;
        // Get our last updated time in seconds since epoch
        var updatedCutOff = new Date(contactData.updated).getTime() / 1000;
        // Add the amount of seconds to determine our cutoff timestamp
        updatedCutOff += 60 * 60 * 24 * validNumberOfDays;

        // If the cutoff date is in the past we need to refresh the data
        if (now >= updatedCutOff) {
          this.fetchData(oldId);
          return true;
        }
      }

      // Looks like the data is still good
      this.triggerEvent('ready', contactData);
    // If there is no cookie but we have a MailChimp ID
    } else if (mcEid) {
      this.fetchData(mcEid);
      return true;
    }

    return false;
  }

  /**
   * Make an AJAX request to fetch data from the server
   * @param  {String} id MailChimp Unique ID or email address to
   * pass to server
   * @param {Boolean} Whether to trigger the ready event that other
   * code can hook into
   */
  fetchData(id, triggerReadyEvent = true) {
    var ajaxData = {
      action: 'get_contact_data',
      contactID: id
    };
    var storageKey = this.storageKey;
    $.post(PedVars.ajaxurl, ajaxData, (resp) => {
      if (! resp.success) {
        return;
      }
      localStorageCookie(storageKey, resp.data);
      if (triggerReadyEvent) {
        this.triggerEvent('ready', resp.data);
      }
    });
  }

  /**
   * Whenver we capture an email address we need to update the cookie
   * @param  {Object} e    Event data
   * @param  {Object} data Email address sucessfully submited
   */
  listenForEmailSignups(e, data) {
    if (!('emailAddress' in data)) {
      return;
    }
    this.fetchData(data.emailAddress, false);
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
