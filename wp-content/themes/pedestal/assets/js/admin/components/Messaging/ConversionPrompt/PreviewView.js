/* global wpApiSettings */

import { resizeIframe } from 'utils';
import { default as PreviewViewBase } from '../PreviewView';

const PreviewView = PreviewViewBase.extend({

  render() {
    // eslint-disable-next-line max-len
    const endpoint = wpApiSettings.root + 'pedestal/v1/conversion-prompt/render';
    const context = this.model.toJSON();
    $.get(endpoint, context).done((data) => {
      this.$output.contents().find('body').html(data);
      resizeIframe(this.$output);
    });
    return this;
  },

  events() {
    return this.debounceEvents({
      'input .fm-title .fm-element': function(e) {
        this.model.save('title', e.target.value);
      },
      'keyup .fm-body .fm-element': function(e) {
        this.model.save('body', e.target.value);
      },
      'change .fm-type .fm-element': function(e) {
        this.model.save('type', e.target.value);
      },
      'input .fm-button_text .fm-element': function(e) {
        this.model.save('button_text', e.target.value);
      },
      'input .fm-button_url .fm-element': function(e) {
        this.model.save('button_url', e.target.value);
      },
      'change .fm-style .fm-element': function(e) {
        this.model.save('style', e.target.value);
      },
      'click .js-ped-icon-button': function(e) {
        this.listenToIconButton(e);
      },
    });
  }
});

export default PreviewView;

