jQuery(document).ready(function($) {
  // Bot honeypot
  var currentYear = new Date().getFullYear();
  $('input.js-contact-year').val(currentYear);
  $('.js-contact-year').addClass('hide');
});
