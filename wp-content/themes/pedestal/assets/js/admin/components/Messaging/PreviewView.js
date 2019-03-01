/* global Backbone, pedestalPreviewURL, tinyMCE */

import { debounce } from 'utils';

// https://stackoverflow.com/questions/7735133/backbone-js-view-inheritance

// This function contains everything that would normally be contained within
// `initialize()`, and will be called upon instantiating `PreviewView` and
// anything that extends it
const PreviewView = function(options) {
  this.editor = null;
  this.editorID = false;
  this.$el = $(options.el);
  this.model = options.model;

  this.setupFrame();

  this.$output.on('load', () => {
    const $bodyEl = this.$el.find('.fm-body .fm-element');
    if ($bodyEl.hasClass('fm-richtext')) {
      this.editorID = $bodyEl.attr('id');
      this.listenToBodyEditor();
    }
    this.render();
  });

  this.listenTo(this.model, 'change', this.render);

  // If execution arrives here from the construction of a view extending this
  // one, aka `SuperView`, `Backbone.View` will call `initialize` that belongs
  // to `SuperView`. This happens because here `this` is `SuperView`, and
  // `Backbone.View`, applied with the current `this` calls
  // `this.initialize.apply(this, arguments)`
  Backbone.View.apply(this, arguments);
};

PreviewView.extend = Backbone.View.extend;

// Handle all the other common stuff besides the common `initialize()` -- but if
// we wanted an `initialize()` method for the view extending `PreviewView`, it
// should be placed here
Object.assign(PreviewView.prototype, Backbone.View.prototype, {
  listeningToEditor: false,

  widthButtonLabels: {
    toDesktop: 'Switch to desktop preview',
    toMobile: 'Switch to mobile preview',
  },

  /**
   * Debounce all of the functions in the given events object
   *
   * @param {object} events Backbone-compatible events object
   * @returns {object}
   */
  debounceEvents(events) {
    for (const key in events) {
      if (events.hasOwnProperty(key)) {
        events[key] = debounce(events[key]);
      }
    }
    return events;
  },

  /**
   * Set up the frame and width toggling button
   *
   * Get a "unique" ID for this message for loading the static preview on page
   * load. The message ID is passed to WordPress when loading the iframe, which
   * tells WordPress which set of message data to load.
   *
   * We later override this static preview but in case something goes wrong
   * the browser can fall back to the static preview, as the iframe needs to
   * be populated by *something* in order to load the theme CSS.
   */
  setupFrame() {
    const messageID = this.$el.find('.fm-id .fm-element').val();
    this.$outputContainer = this.$el.find('.js-message-preview-container');
    this.$outputContainer.html(`
      <iframe src="${pedestalPreviewURL}${messageID}/"
        class="message-preview js-message-preview js-responsive-iframe"
        data-true-width="645"
        data-true-height="260"
      ></iframe>
    `);
    this.$output = this.$outputContainer.find('.js-message-preview');
  },

  setupFrameWidthToggle() {
    this.$toggleWidthButton = $(`
      <button
        type="button"
        title="Change preview width"
        class="js-message-preview-toggle-width button-secondary"
      >${this.widthButtonLabels.toDesktop}</button>
    `);
    this.$toggleWidthButton.insertAfter(this.$outputContainer);
  },

  /**
   * Handle clicking on the button to resize the preview
   *
   * @param {Event} e
   */
  onToggleWidthClick(e) {
    const $this = $(e.target);
    const largeClass = 'message-preview-container--large';
    if ($this.text() === this.widthButtonLabels.toDesktop) {
      $this.text(this.widthButtonLabels.toMobile);
      this.$outputContainer.addClass(largeClass);
    } else {
      $this.text(this.widthButtonLabels.toDesktop);
      this.$outputContainer.removeClass(largeClass);
    }
    e.preventDefault();
  },

  /**
   * Handle listening to changes in the TinyMCE editor
   *
   * {@link https://stackoverflow.com/a/42765626/1801260}
   */
  listenToBodyEditor() {
    if (typeof tinyMCE === 'undefined' || ! this.editorID) {
      return;
    }

    const listen = () => {
      this.editor.on('keyup', debounce(() => {
        this.model.save('body', this.editor.getContent());
      }));
      this.listeningToEditor = true;
    };

    if (tinyMCE.hasOwnProperty('editors')) {
      $.each(tinyMCE.editors, (i, ed) => {
        if (
          this.listeningToEditor
          || !ed.hasOwnProperty('id')
          || ed.id.trim() !== this.editorID
        ) {
          return;
        }
        this.editor = ed;
        listen();
      });
    }

    tinyMCE.on('AddEditor', (e) => {
      if (this.listeningToEditor || e.editor.id !== this.editorID) {
        return;
      }
      this.editor = e.editor;
      listen();
    });
  },

  /**
   * Handle changes to the icon buttons with debounce
   *
   * @param {Event} e
   */
  listenToIconButton: function(e) {
    const name = $(e.currentTarget).data('message-icon-value');
    this.model.save('icon_name', name);
  },

  /**
   * Completely remove the output iframe from the DOM and unbind view events
   *
   * {@link https://stackoverflow.com/a/11534056/1801260}
   */
  destroy() {
    this.undelegateEvents();
    if (this.editor && 'off' in this.editor) {
      this.editor.off();
    }
    this.$el.removeData().unbind();
    this.$output.remove();
    if (this.$toggleWidthButton) {
      this.$toggleWidthButton.remove();
    }
  }
});

export default PreviewView;
