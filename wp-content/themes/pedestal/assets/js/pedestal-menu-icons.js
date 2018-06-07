/* global pedestalMenuIcons */
jQuery(document).ready(function($) {
  $('.js-pedestal-icon-picker').autocomplete({
    minLength: 0,
    source: Object.values(pedestalMenuIcons),

    focus: function(e, ui) {
      $(this).val(ui.item.label);
      e.preventDefault();
    },

    select: function(e, ui) {
      var $this = $(this);
      $this.val(ui.item.label);
      $this.siblings('.js-pedestal-icon-preview').html(ui.item.svg);
      e.preventDefault();
    },

    close: function(e) {
      var $this = $(this);
      var val = $this.val();
      if (! val) {
        $this.siblings('.js-pedestal-icon-preview').html('');
      }
      e.preventDefault();
    },

    create: function() {
      $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
        return $('<li>')
          .addClass('pedestal-menu-icon-item')
          .append(item.svg + ' ' + '<span>' + item.label + '</span>')
          .appendTo(ul);
      };
    }
  });
});
