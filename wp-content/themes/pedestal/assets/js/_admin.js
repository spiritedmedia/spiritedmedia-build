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

      // Expand and remove the + Create Connection links #uglyButItWorks™
      setTimeout(function() { $('.p2p-toggle-tabs a').click().hide(); }, 1500);

      this.bindColorPickers();
      this.reorderExcerptBox();
      this.toggleIOTDEmbedField();
    },

    /**
     * Extract the domain from a URL string
     *
     * http://stackoverflow.com/a/23945027
     */
    extractDomain: function(url) {
      var domain;
      if (url.indexOf('://') > -1) {
        domain = url.split('/')[2];
      } else {
        domain = url.split('/')[0];
      }
      domain.replace('www.', '');
      domain = domain.split(':')[0];
      return domain;
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
      var $excerpt = $('#postexcerpt');
      // Hide the metabox description claiming that the excerpt is optional.
      $excerpt.find('p').hide();
      // Move and restyle the excerpt metabox
      $excerpt.insertAfter('#titlediv').css('margin-top', 20);
      // Set-up a one-time focus event to remove the editor-focus event added
      // by wp-admin/js/post.js
      // We want to be able to tab from the title field to the excerpt
      $('#title').one('focus', function() {
        $(this).off('.editor-focus');
      });
    },

    /**
     * Hide the Instagram of the Day date selection field by default
     *
     * Show if the `embed_url` field contains a domain matching `instagr*`.
     */
    toggleIOTDEmbedField: function() {
      var $embedURLField = $('.post-type-pedestal_embed #fm-embed_url-0');
      $embedURLField.on('blur',function(e) {
        var $target = $(e.target);
        var $dailyInsta = $('#fm_meta_box_daily_insta_date');
        var url = $target.val();
        if (url.indexOf('instagr') !== -1) {
          $dailyInsta.show();
        } else {
          $dailyInsta.hide();
        }
        // Reload the Fieldmanager datepicker
        // jscs:disable requireCamelCaseOrUpperCaseIdentifiers
        fm.datepicker.add_datepicker(e);
        // jscs:enable requireCamelCaseOrUpperCaseIdentifiers
      }).blur();
    }
  };

  $(document).ready(function() {
    PedestalAdmin.init();
  });

}(jQuery));
//# sourceMappingURL=admin.js.map
