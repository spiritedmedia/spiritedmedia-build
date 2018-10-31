/* global tinyMCE */

/**
 * Set up the buttons for the Summary field
 *
 * We load the TinyMCE summary field editor instance separately within each
 * button's event listener. If we load it outside of these event listeners
 * then TinyMCE will not have loaded the editors yet, and we won't be able
 * to call `setContent()`.
 */
export default function summaryButtons() {
  const $summary = $('#fm-homepage_settings-0-summary-0');
  if ($summary.length < 1) {
    // No summary field on the page so don't setup event listeners
    return;
  }

  // Copy the subhead to the summary field
  $('.js-pedestal-summary-copy-subhead').on('click', function() {
    const subhead = $('textarea#excerpt').val();
    if ('' === subhead) {
      return;
    }
    $summary.val(subhead);
  });

  // Copy the first paragraph in the main content field to the summary field
  $('.js-pedestal-summary-copy-first-graf').on('click', function() {
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
    $summary.val(graf);
  });
}
