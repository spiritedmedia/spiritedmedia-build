/* global PedestalIcons */

const icons = PedestalIcons;

/**
 * Get the SVG markup for an icon
 *
 * Depends on the global variable `PedestalIcons` which actually contains the
 * requested markup. This function just fetches the value from that object.
 *
 * @param {string} name Icon name
 * @param {string|array} classes Classes to add to the <svg> element
 * @returns {string} Icon SVG HTML
 */
export default function getIconSVG(name, classes) {
  name = name.trim();
  if (!icons.hasOwnProperty(name)) {
    throw (`[getIconSVG] The icon "${name}" doesn't seem to exist!`);
  }
  const $icon = $(icons[name].svg);
  $icon.addClass(classes);
  return $icon[0].outerHTML;
}
