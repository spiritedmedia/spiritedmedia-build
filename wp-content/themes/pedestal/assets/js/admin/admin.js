import connectionMetabox from 'connectionMetabox';
import filterHierarchicalTerms from 'filterHierarchicalTerms';
import handleEmbedURLChange from 'handleEmbedURLChange';
import handleEventUI from 'handleEventUI';
import requirePostTitle from 'requirePostTitle';
import summaryButtons from 'summaryButtons';

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
        connectionMetabox(this.connections[k], k);
      }

      // Expand and remove the + Create Connection links #uglyButItWorks™
      setTimeout(function() { $('.p2p-toggle-tabs a').click().hide(); }, 1500);

      // Toggle appearance of some Embed metaboxes based on the Embed URL field
      const $embedURLField = $('.post-type-pedestal_embed #fm-embed_url-0');
      $embedURLField.on('blur', handleEmbedURLChange).blur();

      // Prevent publishing with an empty post title field
      $(document).on('click', '#publish', requirePostTitle);

      filterHierarchicalTerms();
      handleEventUI();
      summaryButtons();

      this.disableDraggingDistributionMetaboxes();
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
