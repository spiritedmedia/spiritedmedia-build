/* global PedestalIcons */

const icons = PedestalIcons;

export default class IconButtons {
  constructor(el) {
    this.$el = $(el);
    this.$iconOptions = this.$el.find('.fm-option .fm-element');

    this.createButtons();

    this.$buttons = this.$el.find('.js-ped-icon-button');
    this.$buttons.on('click', (e) => this.onClick(e));
    this.$buttons.on('keydown', (e) => this.onKeydown(e));
  }

  createButtons() {
    this.$iconOptions.each((i, el) => {
      const $label = $(`label[for="${el.id}"]`);
      const labelText = $.trim($label.text());
      const iconName = el.value;
      const checkedClass = el.checked ? ' is-checked' : '';
      const icon = icons[iconName].svg;

      /* eslint-disable max-len */
      const button = `
        <a href="#"
          title="${labelText}"
          class="js-ped-icon-button ped-icon-button button-secondary ${checkedClass}"
          data-message-icon-value="${iconName}"
        >
          ${icon}
        </a>
      `;
      /* eslint-enable max-len */

      el.style.display = 'none';
      $label.hide();
      $(button).insertAfter(el);
    });
  }

  onClick(e) {
    // Using `e.target` breaks functionality when clicking directly on the icon
    const $target = $(e.currentTarget);

    this.$buttons.removeClass('is-checked');
    this.$buttons.find('.fm-element:radio').attr('checked', false);
    $target.addClass('is-checked');
    $target.prev('.fm-element:radio').attr('checked', true);
    e.preventDefault();
  }

  onKeydown(e) {
    const spaceKey = 32;
    if (e.which == spaceKey) {
      $(e.currentTarget).trigger('click');
    }
  }
}
