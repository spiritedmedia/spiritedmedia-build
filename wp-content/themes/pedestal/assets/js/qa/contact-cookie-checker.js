/* global contactExpectedMergeFields, contactEmail */

import localStorageCookie from 'localStorageCookie';
import Contact from 'Contact';

jQuery(document).ready(function($) {
  const contact = new Contact;
  const expectedMergeFields = contactExpectedMergeFields.data;

  // Delete the contact cookie
  localStorageCookie(contact.storageKey, null);

  // Fetch contact details for the email
  contact.fetchData(contactEmail);
  $(this).on('pedContact:ready', function(e, data) {
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
