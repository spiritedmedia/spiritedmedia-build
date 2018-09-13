/* global messagePreviewDefaults */

import Preview from './Preview';

class Message {

  /**
   * [constructor]
   *
   * @param {JQuery} $el Fieldmanager group element with class `.fm-group`
   * @param {Object} defaults Model defaults to override the default defaults
   */
  constructor($el, defaults) {
    this.$el = $el;
    const standardDefaults = messagePreviewDefaults.standard;
    this.defaults = Object.assign({}, standardDefaults, defaults);

    this.createPreview();
  }

  /**
   * Create the Preview
   */
  createPreview() {
    this.Preview = new Preview(this.$el, this.defaults);
  }

  /**
   * Destroy an existing Preview
   */
  destroyPreview() {
    if ('destroy' in this.Preview) {
      this.Preview.destroy();
    }
    this.Preview = null;
  }

  /**
   * Set an attribute on the Preview's model
   *
   * @param {string} attr
   * @param {*} val
   */
  setPreviewAttribute(attr, val) {
    this.Preview.View.model.save(attr, val);
  }
}

export default Message;
