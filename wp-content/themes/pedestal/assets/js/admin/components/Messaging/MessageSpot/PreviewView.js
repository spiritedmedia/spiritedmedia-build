import twig from 'lib/Twig';
import { default as PreviewViewBase } from '../PreviewView';

const PreviewView = PreviewViewBase.extend({

  initialize() {
    this.$templateProto = this.$el.find('.js-message-template-proto');
    this.template = twig({
      data: this.$templateProto.html(),
    });

    this.setupFrameWidthToggle();

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
      case 'override':
        context.button_label = false;
        break;
      case 'with_button':
        context.icon = false;
        context.title = false;
        break;
    }

    if (!context.body) {
      context.body = this.model.get('postTitle') || this.model.defaults.body;
    }

    const html = this.template.render(context);
    this.$output.contents().find('body').html(html);

    return this;
  },

  events: {
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
    'click .js-message-preview-toggle-width': 'onToggleWidthClick',
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

