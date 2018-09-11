/*
global jQuery, Backbone, Twig, PedestalIcons, tinyMCE,
pedestalPreviewURL, PedUtils, messagePreviewModelDefaults
*/

let Message = {

  Preview: {},

  loadPreview($el) {
    const $modelStorage = $el.find('.fm-preview_model .fm-element');

    let data = {};
    if ($modelStorage.length > 0) {
      data = $modelStorage.val();
      data = data ? JSON.parse(data) : {};
    }

    let model = new Message.Preview.Model(data);
    model.$storage = $modelStorage;

    const view = new Message.Preview.View({
      el: $el,
      model
    });

    // Destroy this preview upon drag and recreate it upon drop
    const $sortableContainer = $el.parent();
    $sortableContainer.one('sortstart', () => view.destroy());
    $sortableContainer.one('sortstop', () => Message.loadPreview($el));
  },

  createIconButton: function(el) {
    const $label = $(`label[for="${el.id}"]`);
    const labelText = $.trim($label.text());
    const iconName = el.value;
    const checkedClass = el.checked ? ' is-checked' : '';
    const icon = PedestalIcons[iconName].svg;

    /* eslint-disable max-len */
    const button = `
      <a href="#"
        title="${labelText}"
        class="js-message-icon-button message-icon-button button-secondary ${checkedClass}"
        data-message-icon-value="${iconName}"
      >
        ${icon}
      </a>
    `;
    /* eslint-enable max-len */

    $label.hide();
    $(button).insertAfter(el);
  }

};

Message.Preview.Model = Backbone.Model.extend({
  defaults: messagePreviewModelDefaults,
  sync() {
    this.$storage.val(JSON.stringify(this.toJSON()));
  }
});

Message.Preview.View = Backbone.View.extend({
  model: Message.Preview.Model,
  listeningToEditor: false,

  widthButtonLabels: {
    toDesktop: 'Switch to desktop preview',
    toMobile: 'Switch to mobile preview',
  },

  initialize() {
    this.$templateProto = this.$el.find('.js-message-spot-template-proto');
    this.template = Twig.twig({
      id: this.id,
      data: this.$templateProto.html(),
    });

    this.setupFrame();

    this.$output.on('load', () => {
      this.editorID = this.$el.find('.fm-body .fm-element').attr('id');
      this.listenToBodyEditor();
      this.render();
    });

    this.listenTo(this.model, 'change', this.render);

    // Focus the preview iframe properly -- prevents the component's normal
    // focus behavior from causing the preview to resize
    this.$output.contents().on('focus', '.js-message-spot', () => {
      this.$output.focus();
    });
  },

  render() {
    let context = this.model.toJSON();
    context.additional_classes = this.getVariantClass();
    switch (context.type) {
      case 'standard':
        context.title = false;
        context.button_label = false;
        break;
      case 'with_title':
        context.button_label = false;
        break;
      case 'with_button':
        context.icon = false;
        context.title = false;
        break;
    }

    if (!context.body) {
      context.body = this.model.defaults.body;
    }

    const html = this.template.render(context);
    this.$output.contents().find('body').html(html);

    return this;
  },

  events: {
    'change .fm-type .fm-element': function(e) {
      this.model.save('type', e.target.value);
    },
    'keyup .fm-body .fm-tinymce': function(e) {
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
    'click .js-message-icon-button': 'onIconButtonClick',
    'keydown .js-message-icon-button': 'onIconButtonKeydown',
    'click .js-message-spot-preview-toggle-width': 'onToggleWidthClick',
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
    this.$outputContainer = this.$el.find('.js-message-spot-preview-container');
    this.$outputContainer.html(`
      <iframe src="${pedestalPreviewURL}${messageID}"
        class="message-spot-preview js-message-spot-preview"
        width="100%"></iframe>
    `);
    this.$output = this.$outputContainer.find('.js-message-spot-preview');

    const $button = $(`
      <button
        title="Change preview width"
        class="js-message-spot-preview-toggle-width button-secondary"
      >${this.widthButtonLabels.toDesktop}</button>
    `);
    $button.insertAfter(this.$outputContainer);
  },

  /**
   * Handle clicking on icon buttons
   *
   * The buttons need to act as a proxy for the real radio inputs.
   *
   * @param {Event} e
   */
  onIconButtonClick(e) {
    // Using `e.target` breaks functionality when clicking directly on the icon
    const $this = $(e.currentTarget);

    const $others = this.$el.find('.js-message-icon-button');
    $others.removeClass('is-checked');
    $others.find('.fm-element:radio').attr('checked', false);
    $this.addClass('is-checked');
    $this.prev('.fm-element:radio').attr('checked', true);
    e.preventDefault();

    this.model.save('icon', $this.data('message-icon-value'));
  },

  /**
   * Trigger a click event when the space key is pressed
   *
   * @param {Event} e
   */
  onIconButtonKeydown(e) {
    const spaceKey = 32;
    if (e.which == spaceKey) {
      $(e.currentTarget).trigger('click');
    }
  },

  /**
   * Handle clicking on the button to resize the preview
   *
   * @param {Event} e
   */
  onToggleWidthClick(e) {
    const $this = $(e.target);
    const largeClass = 'message-spot-preview-container--large';
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
    if (typeof tinyMCE === 'undefined'){
      return;
    }

    const listen = () => {
      this.editor.on('keyup', () => {
        this.model.save('body', this.editor.getContent());
      });
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
   * Get the CSS class for this variation of the message spot component
   */
  getVariantClass() {
    const type = this.model.get('type');
    if (type === 'standard') {
      return '';
    }
    return `message-spot--${type.replace('_', '-')}`;
  },

  /**
   * Completely remove the output iframe from the DOM and unbind view events
   *
   * {@link https://stackoverflow.com/a/11534056/1801260}
   */
  destroy() {
    this.undelegateEvents();
    this.editor.off();
    this.$el.removeData().unbind();
    this.$output.remove();
  }
});


jQuery(document).ready(function($) {
  // Set up the `ped_icon()` Twig function
  Twig.extendFunction('ped_icon', function(name, classes) {
    name = name.trim();
    if (!PedestalIcons.hasOwnProperty(name)) {
      throw (`[Message Spot] The icon "${name}" doesn't seem to exist!`);
    }
    const $icon = $(PedestalIcons[name].svg);
    $icon.addClass(classes);
    return $icon[0].outerHTML;
  });

  // Set up the icon buttons for the proto element and the existing messages
  $('.fm-icon .fm-option .fm-element').each(function(i, el) {
    el.style.display = 'none';
    Message.createIconButton(el);
  });

  // Set up existing messages
  $('.fm-message:not(.fmjs-proto) .fm-group-label-wrapper').each(function(i) {
    if (i === 0) {
      $('.js-message-spot-prompt').addClass('has-messages');
    }
    Message.loadPreview($(this).parent());
  });

  // Set up newly created messages as they're created
  $(document).on('fm_added_element', (e) => {
    const $this = $(e.target);

    // Save a "unique" identifier for loading the preview on next page load
    const messageID = PedUtils.genStr();
    $this.find('.fm-id .fm-element').val(messageID);

    $('.js-message-spot-prompt').addClass('has-messages');
    Message.loadPreview($this);
  });
});


