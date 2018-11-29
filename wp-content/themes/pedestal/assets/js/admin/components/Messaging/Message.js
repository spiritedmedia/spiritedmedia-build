import Preview from './Preview';

export default class Message {

  /**
   * [constructor]
   *
   * @param {JQuery} $el Fieldmanager group element with class `.fm-group`
   * @param {Object} previewView View for the preview
   * @param {Object} defaults Model defaults to override the default defaults
   */
  constructor($el, previewView, defaults) {
    this.$el = $el;
    this.previewView = previewView;
    this.defaults = defaults;
    this.createPreview();
  }

  createPreview() {
    this.Preview = new Preview(this.$el, this.previewView, this.defaults);
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

  /**
   * Get the preview output iframe jQuery object
   *
   * @returns {jQuery}
   * @memberof Message
   */
  getPreviewFrame() {
    return this.Preview.View.$output;
  }
}
