import { genStr } from 'utils';
import Message from 'Messaging/ConversionPrompt/ConversionMessage';

jQuery(document).ready(($) => {
  // Set up existing messages
  $('.fm-message:not(.fmjs-proto)').each(function() {
    new Message($(this));
  });

  // Set up newly created messages as they're created
  $(document).on('fm_added_element', (e) => {
    const $this = $(e.target);

    // Save a "unique" identifier for loading the preview on next page load
    const messageID = genStr();
    $this.find('.fm-id .fm-element').val(messageID);

    new Message($this);
  });
});
