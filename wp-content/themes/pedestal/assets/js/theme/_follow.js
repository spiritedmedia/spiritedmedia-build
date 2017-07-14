/**
 * Activate follow functionality
 */

jQuery(document).ready(function($) {
  $('.js-follow-this-anchor').on('click', function(e) {
    var anchor = $(this).attr('href').split('#')[1];
    var anchorOffset = $('#' + anchor).offset();
    var scrollOffset = anchorOffset ? anchorOffset.top : 0;
    $('html, body').animate({
      scrollTop: scrollOffset
    }, 750);
    $('.js-follow-this-email').focus();
    window.location.hash = anchor;
    e.preventDefault();
  });
});
