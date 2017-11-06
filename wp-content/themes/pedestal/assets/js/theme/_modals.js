/* exported PedestalModal */

/**
 * Handler for modals
 *
 * Currently assumes only one modal per page
 */
function PedestalModal() {
  const $ = jQuery;
  const focusableSelectors = [
    'a[href]',
    'area[href]',
    'input:not([disabled]):not([type="hidden"]):not([readonly])',
    'select:not([disabled]):not([type="hidden"]):not([readonly])',
    'textarea:not([disabled]):not([type="hidden"]):not([readonly])',
    'button:not([disabled]):not([type="hidden"]):not([readonly])',
    'iframe',
    'object',
    'embed',
    '*[tabindex]',
    '*[contenteditable]'
  ].join();
  // eslint-disable-next-line max-len
  const animationEnd = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
  const $openModalBtn = $('.js-modal-open');
  const $closeModalBtn = $('.js-modal-close');
  const $body = $('body');
  const $site = $('.js-site');
  const $modal = $('.js-modal');
  const $modalContent = $('.js-modal-content');
  const $modalInput = $modalContent.find('.js-email-input');
  const $modalFocus = $modalContent.find('.js-modal-focus');
  const $overlay = $('.js-modal-overlay');
  const $elFadeIn = $modal.add($overlay);

  $openModalBtn.on('click', open);
  $closeModalBtn.on('click', close);
  $overlay.on('click', close);

  // Prevent mobile keyboard overlap
  $modalInput.on('focus', () => {
    $modal.css({position:'absolute'});
  });
  $modalInput.on('blur', () => {
    $modal.css({position:'fixed'});
  });

  // Close modal upon hitting the escape key
  $modal.on('keydown', (e) => {
    if (e.which == 27) {
      close();
      e.preventDefault();
    }
  });

  /**
   * Open the modal
   */
  function open(e) {
    $body.addClass('has-modal-open');

    $elFadeIn.addClass('is-animated--fade-in');
    $elFadeIn.one(animationEnd, function() {
      $(this).removeClass('is-animated--fade-in');
    });
    $modalContent.addClass('is-animated--zoom-in');
    $modalContent.one(animationEnd, function() {
      $(this).removeClass('is-animated--zoom-in');
    });

    // Focus the modal to enable escape key closing
    $modalFocus.focus();

    // Trap the tab focus by disable tabbing on all elements outside modal
    //
    // Because the modal is a sibling of site, this is easier.
    // Make sure to check if the element is visible,
    // or already has a tabindex so you can restore it when you untrap.
    $site.find(focusableSelectors).attr('tabindex', '-1');

    // Trap the screen reader focus as well with aria roles
    //
    // This is much easier as our site and modal elements are siblings,
    // otherwise you'd have to set aria-hidden on every screen reader focusable
    // element not in the modal.
    $modal.removeAttr('aria-hidden');
    $site.attr('aria-hidden', 'true');

    e.preventDefault();
  }

  /**
   * Close the modal
   */
  function close() {
    $modal.addClass('is-animated--fade-out');
    $modal.one(animationEnd, function() {
      $(this).removeClass('is-animated--fade-out');
    });
    $overlay.addClass('is-animated--fade-out');
    $overlay.one(animationEnd, function() {
      $(this).removeClass('is-animated--fade-out');
      $body.removeClass('has-modal-open');
    });

    // Untrap the tab focus by removing tabindex=-1
    //
    // You should restore previous values if an element had them.
    $site.find(focusableSelectors).removeAttr('tabindex');

    // Untrap screen reader focus
    $modal.attr('aria-hidden', 'true');
    $site.removeAttr('aria-hidden');

    // Restore focus to the triggering element
    $openModalBtn.focus();
  }
}
