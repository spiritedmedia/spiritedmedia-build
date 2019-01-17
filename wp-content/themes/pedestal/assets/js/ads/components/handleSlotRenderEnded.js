export default function handleSlotRenderEnded(e) {
  if (e.isEmpty) {
    return;
  }

  const id = e.slot.getSlotElementId();
  const adElem = document.getElementById(id);

  if (!adElem) {
    return;
  }

  // Add 'ADVERTISEMENT' disclaimer text if it hasn't already been added
  if (adElem.querySelector('.js-dfp-disclaimer') === null) {
    // eslint-disable-next-line max-len
    const html = '<div class="dfp-disclaimer js-dfp-disclaimer">ADVERTISEMENT</div>';
    adElem.insertAdjacentHTML('afterbegin', html);
  }
}
