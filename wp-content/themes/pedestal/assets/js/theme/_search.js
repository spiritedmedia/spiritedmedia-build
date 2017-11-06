jQuery(document).ready(function($) {

  var $body = $('body');

  $body.on('click keyup', '.js-sitewide-search', function(e) {
    // Key up is to detect keyboard actions on the search icon for accessibility
    // Ignore any key event other than return/enter (keycode = 13)
    if (e.type === 'keyup' && e.which !== 13) {
      return;
    }

    $body.toggleClass('is-search-open');

    // Set the tab index to 1 so the search form has a natural
    // tab order even though it's at the end of the markup
    var targetID = $(this).attr('for');
    var $target = $('#' + targetID);
    $target.attr('tabindex', 1).focus();
  });

  $('.js-search-form').on('click', '.js-search-icon-close', function(e) {
    e.preventDefault();
    $body.removeClass('is-search-open');
  });
});
