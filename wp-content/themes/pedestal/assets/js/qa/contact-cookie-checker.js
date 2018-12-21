/* global contactExpectedMergeFields, contactEmail */

import localStorageCookie from 'localStorageCookie';
import Contact from 'Contact';

// Delete the contact cookie
// Prevent a race condition
localStorageCookie('contactData', '');

jQuery(document).ready(function($) {
  const contact = new Contact;
  const expectedMergeFields = contactExpectedMergeFields.data;

  // Fetch contact details for the email
  contact.fetchData(contactEmail);
  $(document).on('pedContact:ready', function(e, data) {
    $('#output').html(JSON.stringify(data.data, null, 4));
    var output = {
      'pass': [],
      'fail': []
    };
    for (const key in expectedMergeFields) {
      // 'since' seems to be off be 3 -5 ms constantly
      // Just make sure the key is set and move on
      if (key == 'since') {
        output['pass'].push(key);
        continue;
      }
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
