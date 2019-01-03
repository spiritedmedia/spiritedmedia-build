/* global messagePreviewDefaults */

import { resizeIframe } from 'utils';
import Message from '../Message';
import PreviewView from './PreviewView';
import IconButtons from 'IconButtons';

export default class ConversionMessage extends Message {
  constructor($el) {
    super($el, PreviewView, messagePreviewDefaults.standard);

    // Set up the icon buttons for the proto element and the existing messages
    this.iconButtons = new IconButtons($el.find('.fm-icon_name'));

    // Preview frame sizing needs to be adjusted because `width()` doesn't work
    // when the group is collapsed
    const $sortableContainer = this.$el.parent();
    const iframe = this.getPreviewFrame();
    $sortableContainer.on('fm_collapsible_toggle', () => resizeIframe(iframe));
  }
}
