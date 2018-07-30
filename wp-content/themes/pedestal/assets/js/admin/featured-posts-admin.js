/* global jQuery, ajaxurl */
jQuery(document).ready(function($) {
  $('body').on('change', '.fm-autocomplete-hidden', function() {
    var $this = $(this);
    var postId = parseInt($this.val());
    var $textarea = $this
      .closest('.fm-group-inner')
      .find('.fm-description textarea');
    if (! postId) {
      $textarea.attr('placeholder', '');
      return;
    }

    var data = {
      'post_id': postId,
      'action': 'pedestal-featured-entities-placeholder'
    };

    $.post(ajaxurl, data, function(response) {
      if (response.data) {
        $textarea.attr('placeholder', response.data);
      }
    });
  });
});
