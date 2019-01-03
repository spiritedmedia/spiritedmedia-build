/* global wpApiSettings */

import { default as PreviewViewBase } from '../PreviewView';

const PreviewView = PreviewViewBase.extend({

  initialize() {
    this.setupFrameWidthToggle();

    // Focus the preview iframe properly -- prevents the component's normal
    // focus behavior from causing the preview to resize
    this.$output.contents().on('focus', '.js-message-spot', () => {
      this.$output.focus();
    });
  },

  render() {
    // eslint-disable-next-line max-len
    const endpoint = wpApiSettings.root + 'pedestal/v1/message-spot/render';
    const context = this.model.toJSON();
    $.get(endpoint, context).done((data) => {
      this.$output.contents().find('body').html(data);
    });
    return this;
  },

  events() {
    return this.debounceEvents({
      'change .fm-type .fm-element': function(e) {
        this.model.save('type', e.target.value);
      },
      'keyup .fm-body .fm-element': function(e) {
        this.model.save('body', e.target.value);
      },
      'input .fm-url .fm-element': function(e) {
        this.model.save('url', e.target.value);
      },
      'input .fm-title .fm-element': function(e) {
        this.model.save('title', e.target.value);
      },
      'input .fm-button_label .fm-element': function(e) {
        this.model.save('button_label', e.target.value);
      },
      'click .js-ped-icon-button': function(e) {
        this.model.save('icon', $(e.currentTarget).data('message-icon-value'));
      },
      'click .js-message-preview-toggle-width': function(e) {
        this.onToggleWidthClick(e);
      },
    });
  },

  /**
   * Get the CSS class for this variation of the message spot component
   */
  getVariantClass() {
    const type = this.model.get('type');
    if (type === 'standard') {
      return '';
    }
    let classStr = `message-spot--${type.replace('_', '-')}`;
    if (type === 'override') {
      classStr += ' message-spot--with-title';
    }
    return classStr;
  },
});

export default PreviewView;

