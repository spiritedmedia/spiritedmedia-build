(function($) {

  var PedestalAdmin = {

    init: function() {

      this.clusterMap = {
        'stories': 'pedestal_story',
        'topics': 'pedestal_topic',
        'people': 'pedestal_person',
        'organizations': 'pedestal_org',
        'places': 'pedestal_place',
        'localities': 'pedestal_locality'
      };

      this.connections = {
        'entities_to_clusters': {
          'from': 'entities',
          'to': [
            'stories',
            'topics',
            'people',
            'organizations',
            'places',
            'localities'
          ]
        },
        'stories_to_clusters': {
          'from': 'stories',
          'to': [
            'topics',
            'people',
            'organizations',
            'places',
            'localities'
          ]
        }
      };

      this.siteURL = window.location.protocol + '//' + window.location.hostname;

      for (var k in this.connections) {
        this.manageConnectionsMetaboxes(this.connections[k], k);
      }

      this.bindColorPickers();
      this.reorderExcerptBox();
    },

    /**
     * Manage P2P connection boxes
     */
    manageConnectionsMetaboxes: function(connection, generalType) {
      var toType;
      var toClusters = connection.to;
      var newClusterURLBase = this.siteURL + '/wp-admin/post-new.php?' +
          'post_type=';
      for (var i = toClusters.length - 1; i >= 0; i--) {
        toType = toClusters[i];
        var clusterPostType = this.clusterMap[toType];
        var specificType = connection.from + '_to_' + toType;
        var $box = $('[data-p2p_type=' + specificType + '].p2p-box');
        var connectionTabPanelSelector = '#fm-pedestal_' + generalType +
            '_connections-0-' + toType + '-0-tab .fm-group-inner';
        var $connectionTabPanel = $(connectionTabPanelSelector);

        // Move box to tab panel
        if ($box.length !== 0 && $connectionTabPanel.length !== 0) {
          $connectionTabPanel.append($box.parent().html());
          $box.closest('.postbox').remove();
        }
      }
    },

    bindColorPickers: function() {
      $backgroundColor = $('[name="story_branding[background_color]"]');
      $foregroundColor = $('[name="story_branding[foreground_color]"]');

      $backgroundColor.spectrum({
        preferredFormat: 'hex',
        showInput: true,
        showPaletteOnly: true,
        togglePaletteOnly: true,
        togglePaletteMoreText: 'more',
        togglePaletteLessText: 'less',
        color: $backgroundColor.val(),
        palette: [
          [
            '#1d3557', '#e63946', '#ff6f59', '#004ba8', '#6c464e', '#41bbd9',
            '#005377', '#d5c67a', '#f18f01', '#3e8914'
          ]
        ]
      });

      $foregroundColor.spectrum({
        preferredFormat: 'hex',
        showPaletteOnly: true,
        togglePaletteOnly: false,
        togglePaletteMoreText: 'more',
        togglePaletteLessText: 'less',
        color: $foregroundColor.val(),
        palette: [
            ['#000','fff']
        ]
      });
    },

    reorderExcerptBox: function() {
      $('#postexcerpt').insertAfter('#titlediv');
      $('#postexcerpt').css('margin-top', 20);
    }
  };

  $(document).ready(function() {
    PedestalAdmin.init();
  });

}(jQuery));
//# sourceMappingURL=admin.js.map
