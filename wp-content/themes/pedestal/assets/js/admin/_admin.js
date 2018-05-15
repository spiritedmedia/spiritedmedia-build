/* global fm, tinyMCE */

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

      this.toggleIOTDEmbedField();
      this.makeHierarchicalTermsFilterable();
      this.setupSummaryButtons();
      this.disableDraggingDistributionMetaboxes();
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
      for (var i = toClusters.length - 1; i >= 0; i--) {
        toType = toClusters[i];
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
        fm.datepicker.add_datepicker(e);
      }).blur();
    },

    /**
     * Add input field to hierarchical term metaboxes so terms can be filtered
     * as you type.
     */
    makeHierarchicalTermsFilterable: function() {
      var $boxes = $('.categorydiv');
      if ($boxes.length < 1) {
        return;
      }

      // Extend :contains to be case-insensitive
      // via http://stackoverflow.com/questions/187537/
      jQuery.expr[':'].contains = function(a,i,m) {
        var haystack = (a.textContent || a.innerText || '').toUpperCase();
        var needle = m[3].toUpperCase();
        return haystack.indexOf(needle) >= 0;
      };

      var filterClass = 'categorydiv-filter';
      var filterSelector = '.' + filterClass;

      // Create our filter input element
      var $filterInputElement = $('<input type="search" />')
        .addClass(filterClass)
        .css('width', '100%');

      $boxes.each(function(index, box) {
        var $box = $(box);
        if ($box.find('.categorychecklist li').length < 10) {
          return;
        }
        var boxTitle = $box.parent().siblings('.hndle').text();
        $filterInputElement.attr('placeholder', 'Filter ' + boxTitle);
        $box.prepend($filterInputElement);
      });

      // Attach events to the main <form> of the edit screen
      $('#post')
        // Keyup happens after the character has been entered
        // which we need for counting purposes
        .on('keyup', filterSelector, function() {
          var $this = $(this);
          var $checklists = $this.parent().find('.categorychecklist li');
          if($this.val().length < 2) {
            $checklists.show();
          } else {
            $checklists
              .hide()
              .find('.selectit:contains(' + $this.val() + ')')
              .each(function(index, label) {
                $(label).parent().show();
              });
          }
        })
        // Keydown happens before the character is entered
        // so we can catch the return key being pressed
        // and cancel the event which submits the form
        .on('keydown', filterSelector, function(e) {
          // Prevent the return key from submitting the form
          if(e.keyCode == 13) {
            e.preventDefault();
            return false;
          }
        })
        // Clumsy way to detect when the x in the search input is
        // activated clearing the field and resetting the filter
        .on('click', filterSelector, function() {
          var $this = $(this);
          // Without a small delay the keyup event
          // thinks there are more than 0 characters
          setTimeout(function() {
            $this.trigger('keyup');
          }, 100, $this);
        });
    },

    /**
     * Set up the buttons for the Summary field
     *
     * We load the TinyMCE summary field editor instance separately within each
     * button's event listener. If we load it outside of these event listeners
     * then TinyMCE will not have loaded the editors yet, and we won't be able
     * to call `setContent()`.
     */
    setupSummaryButtons: function() {
      const summaryID = 'fm-homepage_settings-0-summary-0';
      const $btnSubhead = $('.js-pedestal-summary-copy-subhead');
      const $btnGraf = $('.js-pedestal-summary-copy-first-graf');

      // Copy the subhead to the summary field
      $btnSubhead.on('click', function() {
        const subhead = $('textarea#excerpt').val();
        if ('' === subhead) {
          return;
        }
        const summary = tinyMCE.get(summaryID);
        summary.setContent(subhead);
      });

      // Copy the first paragraph in the main content field to the summary field
      $btnGraf.on('click', function() {
        const contentHTML = tinyMCE.get('content').getContent();

        // We only want the grafs that don't contain shortcodes
        const $normalGrafs = $(contentHTML).filter('p').not(function() {
          // Using this old pattern from Shortcake v0.5.0 instead of the WP core
          // pattern as Shortcake uses in current versions because there's no
          // simple method for getting a catch-all shortcode pattern like this
          //
          // See https://github.com/wp-shortcake/shortcake/blob/v0.5.0/js/src/utils/shortcode-view-constructor.js#L135
          const regexp = /\[([^\s\]]+)([^\]]+)?\]([^[]*)?(\[\/(\S+?)\])?/;
          return this.innerHTML.match(regexp);
        });

        if (0 >= $normalGrafs.length) {
          return;
        }

        const graf = $normalGrafs.first().html();
        const summary = tinyMCE.get(summaryID);
        summary.setContent(graf);
      });
    },

    /**
     * Disable dragging of the post metaboxes in the Distribution section
     */
    disableDraggingDistributionMetaboxes: function() {
      const $distSection = $('#distribution-sortables');

      $distSection.sortable({
        disabled: true
      });

      $distSection.find('.postbox .hndle').css('cursor', 'pointer');
    }
  };

  $(document).ready(function() {
    PedestalAdmin.init();
  });

}(jQuery));
//# sourceMappingURL=admin.js.map
