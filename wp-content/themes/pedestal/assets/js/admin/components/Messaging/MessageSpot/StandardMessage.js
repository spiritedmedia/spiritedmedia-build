/* global messagePreviewDefaults */

import Message from '../Message';
import PreviewView from './PreviewView';
import IconButtons from 'IconButtons';

export default class StandardMessage extends Message {
  constructor($el) {
    super($el, PreviewView, messagePreviewDefaults.standard);

    // Set up the icon buttons for the proto element and the existing messages
    this.iconButtons = new IconButtons($el.find('.fm-icon'));
  }
}
