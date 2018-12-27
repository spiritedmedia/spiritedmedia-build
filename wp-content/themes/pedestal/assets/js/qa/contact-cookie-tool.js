import localStorageCookie from 'localStorageCookie';
import Contact from 'Contact';

// Serialize a form to JSON
(function ($) {
  $.fn.serializeFormJSON = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
      if (o[this.name]) {
        if (!o[this.name].push) {
          o[this.name] = [o[this.name]];
        }
        o[this.name].push(this.value || '');
      } else {
        o[this.name] = this.value || '';
      }
    });
    return o;
  };
})(jQuery);

jQuery(document).ready(function($) {
  const contact = new Contact;
  const data = localStorageCookie(contact.dataStorageKey);
  const $status = $('#status');

  function outputRawCookieData() {
    var rawData = localStorageCookie(contact.dataStorageKey);
    $('#raw-data-output').text(JSON.stringify(rawData, null, 4));
  }

  if (data && 'data' in data) {
    $status.text('Importing values from cookie');
    outputRawCookieData();
    for (var key in data.data) {
      var val = data.data[key];

      switch (typeof val) {
        case 'boolean':
          // Cast boolean values to strings
          val = val ? 'true' : 'false';
          break;
      }
      $('#' + key).val(val).change();
    }
  }

  $('#target-audiences').on('change', function() {
    var val = $(this).val();
    var fieldsToChange = {
      newsletter_subscriber: true,
      current_member: false,
      donate_365: false,
    };
    switch (val) {
      case 'unidentified':
        fieldsToChange['newsletter_subscriber'] = false;
        break;

      case 'contact':
        break;

      case 'donor':
        fieldsToChange['donate_365'] = true;
        break;

      case 'member':
        fieldsToChange['current_member'] = true;
        break;
    }

    for (key in fieldsToChange) {
      var fieldVal = String(fieldsToChange[key]);
      $('#' + key).val(fieldVal);
    }
    $('.the-form input').trigger('change');
    // eslint-disable-next-line max-len
    $status.html('Set cookie to <code>' + val + '</code> target audience');
  });

  $('.the-form').on('change', 'input, select', function() {
    var $this = $(this);
    var $form = $this.parents('form');
    var newData = $form.serializeFormJSON();

    // Convert stringified booleans and numbers to actual booleans and numbers
    $.each(newData, function(i, item) {
      if (item === '') {
        return;
      }
      if (!isNaN(item * 1)) {
        newData[i] = item * 1;
      } else if ('false' === item || 'true' === item) {
        newData[i] = item == 'true';
      }
    });

    localStorageCookie(contact.dataStorageKey, {
      'version': 3,
      'updated': new Date().toISOString(),
      'data': newData
    });

    // eslint-disable-next-line max-len
    $status.html('Updated cookie: <code>' + $this.attr('name') + '</code> set to <code>' + $this.val() +  '</code>');
    outputRawCookieData();
  });
});
