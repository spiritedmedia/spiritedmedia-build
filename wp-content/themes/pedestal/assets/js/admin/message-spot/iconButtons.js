/* global PedestalIcons */

const iconButtons = (el) => {
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

  el.style.display = 'none';
  $label.hide();
  $(button).insertAfter(el);
};

export default iconButtons;
