/* global Backbone */

export default class Preview {

  /**
   * [constructor]
   *
   * @param {JQuery} $el Message group element with class `.fm-group`
   * @param {Object} View
   * @param {Object} defaults Default attributes for the model
   */
  constructor($el, View, defaults) {
    this.$el = $el;
    this.$modelStorage = this.$el.find('.fm-preview_model .fm-element');

    this.modelDefaults = defaults;
    if (this.$modelStorage.length > 0) {
      const stored = decodeURIComponent(this.$modelStorage.val());
      if (stored) {
        this.modelDefaults = JSON.parse(stored);
      }
    }

    const Model = Backbone.Model.extend({
      defaults: this.modelDefaults,
      $storage: this.$modelStorage,
      sync() {
        const encodedData = encodeURIComponent(JSON.stringify(this));
        this.$storage.val(encodedData);
      }
    });
    this.Model = new Model;

    this.View = new View({
      el: this.$el,
      model: this.Model
    });
  }

  /**
   * Destroy the active view
   */
  destroy() {
    this.View.destroy();
  }
}
