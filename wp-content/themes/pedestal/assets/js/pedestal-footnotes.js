jQuery(document).ready(function($) {
  // This allows us to have a consistent scroll duration by adjusting the number
  // of seconds the animation will run given the distance we need to scroll
  function scrollDuration(offset) {
    var pixelsPerSecond = 1200;
    var distance = Math.abs($(document.body).scrollTop() - offset);
    return distance / pixelsPerSecond * 1000;
  }

  $('.js-main').on('click', '.js-footnote-link', function(e) {
    var $entityShareBar = $('.js-entity-share.fixed');

    // Get the anchor target from the HREF
    var targetID = this.href.split('#')[1];
    var $target = $('#' + targetID);

    // We need to account for the share bar stuck at the top of the window
    var offsetPadding = 0;
    if ($entityShareBar.length > 0) {
      offsetPadding = $entityShareBar.height();
    }
    var offset = $target.offset().top - offsetPadding;
    var duration = scrollDuration(offset);

    $('html, body').animate({
      scrollTop: offset
    }, duration);

    // If there was some sort of error before this point then the anchor links
    // will still work
    e.preventDefault();

    // HTML elements can be focusable if they have a positive tabindex attribute
    // 0 achieves this while still leaving tab order == the source order
    $target.attr('tabindex', 0).focus();
  });
});
