/* global PedUtils, PedestalIcons, Twig, jQuery */

import '../../_ped-utils';
import StandardMessage from './StandardMessage';
import iconButtons from './iconButtons';
import OverrideMessage from './OverrideMessage';

jQuery(document).ready(function($) {
  // Set up the `ped_icon()` Twig function
  Twig.extendFunction('ped_icon', function(name, classes) {
    name = name.trim();
    if (!PedestalIcons.hasOwnProperty(name)) {
      throw (`[Message Spot] The icon "${name}" doesn't seem to exist!`);
    }
    const $icon = $(PedestalIcons[name].svg);
    $icon.addClass(classes);
    return $icon[0].outerHTML;
  });

  // Set up the icon buttons for the proto element and the existing messages
  $('.fm-icon .fm-option .fm-element').each((i, el) => iconButtons(el));

  // Set up existing messages
  $('.fm-message:not(.fmjs-proto) .fm-group-label-wrapper').each(function() {
    new StandardMessage($(this).parent());
  });

  // Set up newly created messages as they're created
  $(document).on('fm_added_element', (e) => {
    const $this = $(e.target);

    // Save a "unique" identifier for loading the preview on next page load
    const messageID = PedUtils.genStr();
    $this.find('.fm-id .fm-element').val(messageID);

    new StandardMessage($this);
  });

  // Set up the message override if it's enabled, otherwise destroy it
  const maybeSetupOverride = (el) => {
    const $el = $(el);
    const $fields = $el
      .closest('.fm-group-inner')
      .find('.fm-wrapper:not(.fm-enabled-wrapper)');

    if ('true' === $el.val()) {
      window.MessageSpotOverride = new OverrideMessage;
      // FM should do be able to do conditional show/hide with `display_if` but
      // something seems to be broken...
      $fields.show();
    } else {
      $fields.hide();
      if (window.MessageSpotOverride instanceof OverrideMessage) {
        window.MessageSpotOverride.destroyPreview();
      }
      window.MessageSpotOverride = null;
    }
  };
  maybeSetupOverride('.fm-enabled .fm-element:checked');
  $(document).on(
    'change',
    '.fm-enabled .fm-element',
    (e) => maybeSetupOverride(e.target)
  );
});


