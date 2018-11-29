/* global wpApiSettings */

import { debounce, resizeIframe } from 'utils';
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

  events: {
    'input .fm-title .fm-element': debounce(function(e) {
      this.model.save('title', e.target.value);
    }, 300),
    'keyup .fm-body .fm-element': debounce(function(e) {
      this.model.save('body', e.target.value);
    }, 300),
    'change .fm-type .fm-element': debounce(function(e) {
      this.model.save('type', e.target.value);
    }, 300),
    'input .fm-button_text .fm-element': debounce(function(e) {
      this.model.save('button_text', e.target.value);
    }, 300),
    'input .fm-button_url .fm-element': debounce(function(e) {
      this.model.save('button_url', e.target.value);
    }, 300),
    'change .fm-style .fm-element': debounce(function(e) {
      this.model.save('style', e.target.value);
    }, 300),
    'click .js-ped-icon-button': 'listenToIconButton',
  }
});

export default PreviewView;

