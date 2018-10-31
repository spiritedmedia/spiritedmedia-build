/* global subscriberExpectedMergeFields, subscriberEmail */

import localStorageCookie from 'localStorageCookie';
import Subscriber from 'Subscriber';

jQuery(document).ready(function($) {
  const subscriber = new Subscriber;
  const expectedMergeFields = subscriberExpectedMergeFields.data;

  // Delete the subscriber cookie
  localStorageCookie(subscriber.storageKey, null);

  // Fetch subscriber details for the email
  subscriber.fetchData(subscriberEmail);
  $(this).on('pedSubscriber:ready', function(e, data) {
    $('#output').html(JSON.stringify(data.data, null, 4));
    var output = {
      'pass': [],
      'fail': []
    };
    for (const key in expectedMergeFields) {
      var valueToCheck = data.data[key];
      var expectedValue = expectedMergeFields[key];
      if (valueToCheck !== expectedValue) {
        output['fail'].push({
          key: key,
          expected: expectedValue,
          actualValue: valueToCheck
        });
      } else {
        output['pass'].push(key);
      }
    }

    if (output['fail'].length <= 0) {
      // Our checks passed!
      $('#pass').show();
    } else {
      // Our checks failed
      $('#fail').show();
      $('#fail-output').html(JSON.stringify(output['fail'], null, 4));
    }

    // Clean up
    $.get('?cleanup', () => {});
  });
});
