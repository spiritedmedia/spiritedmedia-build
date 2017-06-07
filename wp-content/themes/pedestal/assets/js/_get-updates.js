/**
 * Activate new get updates functionality
 */
 // eslint-disable-next-line no-unused-vars
function showGetUpdates() {
  // If jQuery isn't available then bail!
  if ( ! window.jQuery ) {
    return false;
  }

  // Set $ to jQuery
  var $ = window.jQuery;
  var $body = $('body');
  // Check if there is a follow buton on the page, if not then bail!
  if ( $('[data-reveal-id="follow-cluster"]').length === 0 ) {
    return false;
  }

  $('.original-follow-cluster').remove();
  $body.addClass('show-get-updates');

  $('.js-get-updates-anchor').on('click', function(e) {
    var anchor = $(this).attr('href').split('#')[1];
    var scrollOffset = $('#' + anchor).offset().top;
    // Account for the action/top bar that sticks to the top of the viewport
    scrollOffset = scrollOffset - 62;

    if( $body.is('.show-get-updates') ) {
      $('html, body').animate({
        scrollTop: scrollOffset
      }, 750);
      $('#get-updates-email').focus();
      window.location.hash = anchor;
    }
    e.preventDefault();
  });

  $('#get-updates').on('submit', function(e) {
    e.preventDefault();
    var $this = $(this);
    var actionURL = $this.attr('action');
    var actionUrlSeparator = actionURL.indexOf('?') >= 0 ? '&' : '?';
    actionURL += actionUrlSeparator + $.param({'ajax-request': 1});
    $this.addClass('is-loading');

    $.post(actionURL, $this.serialize(), function() {
      $this.addClass('submitted-success').removeClass('is-loading');
    }).fail(function() {
      $this.addClass('submitted-fail').removeClass('is-loading');
    });
  });

  return true;
}
