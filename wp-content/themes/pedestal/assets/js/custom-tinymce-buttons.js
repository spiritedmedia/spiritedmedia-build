/*globals window, document, $, jQuery, _, Backbone */
(function($, _, Backbone) {
tinymce.create('tinymce.plugins.Pedestal', {
    init : function(ed, url) {

        ed.addButton('insertPostElement', {
            // Displayed as tooltip when hovering over icon
            title : 'Insert Post Element',
            // The name of the command to trigger
            cmd : 'insert-post-element',
            // String appended as mce-i-insert-post-element for CSS styling
            icon: 'insert-post-element'
          });

        ed.addCommand('insert-post-element', function() {
            // Instatiate a wp.media object
            // Set the state to 'shortcode-ui' to activate the tab
            // Open the media modal
            wp.media({
              frame: 'post',
              state: 'shortcode-ui'
            }).open();
          });
      },
  });

// Register plugin
tinymce.PluginManager.add('Pedestal', tinymce.plugins.Pedestal);
})(jQuery, _, Backbone);
