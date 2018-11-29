/**
 * Handle email subscription form submission and errors
 */
export default function(e) {
  e.preventDefault();
  var $el                = $(this);
  var $parent            = $el.parent();
  var $submitBtn         = $el.find('.js-form-submit');
  var $invalidFeedback   = $el.find('.js-fail-message');
  var actionURL          = $el.attr('action');
  var actionUrlSeparator = actionURL.indexOf('?') >= 0 ? '&' : '?';
  actionURL += actionUrlSeparator + $.param({ 'ajax-request': 1 });

  $parent.removeClass('is-failed').addClass('is-loading');
  // Clear the aria role alert if the form previously failed
  $invalidFeedback.removeAttr('role');

  $.post(actionURL, $el.serialize(), function() {
    if ($el.find('.js-success-message').length) {
      var $successEmail = $el.find('.js-success-message-email');
      var emailAddress = $el.find('.js-email-input').val();

      $parent.removeClass('is-loading').addClass('is-success');

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
    $parent.removeClass('is-loading').addClass('is-failed');
    if ($invalidFeedback.length && msg.length) {
      $invalidFeedback.attr('role', 'alert').text(msg);
    } else {
      $submitBtn.before(msg);
    }
  });
}
