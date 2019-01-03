/* global ga */

/**
 * Replace `a.spirited.media` with a new host name for the given element and its
 * given attribute
 *
 * @param {string} tagName Element name
 * @param {string} attr Attribute name
 * @param {string} newHostName New host name
 */
function changeElems(tagName, attr, newHostName) {
  const elems = document.getElementsByTagName(tagName);
  for (let i = 0; i < elems.length; i++) {
    const elem = elems[i];
    const attrVal = elem.getAttribute(attr);
    if (attrVal) {
      const newAttrVal = attrVal.replace(/a\.spirited\.media/ig, newHostName);
      if (attrVal !== newAttrVal) {
        elem.setAttribute(attr, newAttrVal);
      }
    }
  }
}

/**
 * If the <body> doesn't have the font-family set to Overpass or Merriweather,
 * then assume the stylesheet failed to download
 */
const computedFontFamily = window.getComputedStyle(document.body).getPropertyValue('font-family'); // eslint-disable-line max-len
if (
  -1 === computedFontFamily.indexOf('Overpass')
  && -1 === computedFontFamily.indexOf('Merriweather')
) {
  const newHostName = window.location.host;
  const newImgHostName = 'd9nsjsuh3e2lm.cloudfront.net';
  changeElems('link', 'href', newHostName);
  changeElems('img', 'srcset', newImgHostName);
  changeElems('img', 'src', newImgHostName);
  changeElems('script', 'src', newHostName);
  if (typeof ga === 'function') {
    ga('send', 'event', 'Error', '', 'CDN failed to load');
  }
}
