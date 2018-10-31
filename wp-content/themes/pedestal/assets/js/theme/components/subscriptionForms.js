/**
 * Handle email subscription form submission and errors
 */
export default function(e) {
  e.preventDefault();
  var $el                = $(this);
  var $submitBtn         = $el.find('.js-form-submit');
  var $invalidFeedback   = $el.find('.js-fail-message');
  var actionURL          = $el.attr('action');
  var actionUrlSeparator = actionURL.indexOf('?') >= 0 ? '&' : '?';
  actionURL += actionUrlSeparator + $.param({ 'ajax-request': 1 });

  $el.removeClass('is-failed');
  $el.addClass('is-loading');

  $.post(actionURL, $el.serialize(), function() {
    if ($el.find('.js-success-message').length) {
      var $successEmail = $el.find('.js-success-message-email');
      var emailAddress = $el.find('.js-email-input').val();

      $el.removeClass('is-loading');
      $el.addClass('is-success');

      // Let other functions know a form submission with an email
      // address happened
      $el.trigger('pedFormSubmission:success', [{
        'emailAddress': emailAddress
      }]);

      // Use email address in success message for user verification
      if (emailAddress && $successEmail.length) {
        $successEmail.text(emailAddress).addClass('u-font-weight--bold');
      }
    }
  }).fail(function(response) {
    var msg = response.responseText;
    $el.removeClass('is-loading');
    $el.addClass('is-failed');
    if ($invalidFeedback.length && msg.length) {
      $invalidFeedback.text(msg);
    } else {
      $submitBtn.before(msg);
    }
  });
}
