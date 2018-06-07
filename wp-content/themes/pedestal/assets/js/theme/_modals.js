/* exported Modal */
// NOTE: Requires markup found in /views/partials/modals/modal.twig

(function($) {

  var $body = $('body');
  $body.addClass('has-closed-modal');

  var focusableSelectors = [
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

  var allModals = [];

  window.Modal = class {

    constructor() {
      var defaults = {
        uniqueID: 'modal-' + allModals.length
      };

      if (arguments[0] && typeof arguments[0] === 'object') {
        this.options = $.extend(arguments[0], defaults);
      }

      this.isOpen = false;
      this.$target = $(this.options.target);
      this.$site = $('.js-site');

      this.$modalContent = $('#modal-content');

      this.$modalOverlay = $('#modal-overlay');
      this.$modalOverlay.on('click', this, function(e) {
        var theModal = e.data;
        theModal.close();
      });

      this.$modalFrame = $('#modal-frame');
      this.$modalFrame.on('click', this, function(e) {
        var $target = $(e.target);
        var theModal = e.data;

        // If we try and put the following conditional in an if statement
        // Babel or Uglify will transpile it into something broken so we need
        // to do it separatly like this
        var isCloseButton = (
          $target.is('.js-modal__frame') ||
          $target.is('.js-modal__close-button') ||
          $target.parents('.js-modal__close-button').length
        );
        if (isCloseButton) {
          theModal.close();
        }
      }).on('keydown', this, function(e) {
        // If the escape key is pressed, close the modal
        var theModal = e.data;
        if (e.which == 27) {
          theModal.close();
          e.preventDefault();
        }
      });

      allModals.push(this);

      // Handle a callback passed to the constructor
      if (arguments[1] && typeof arguments[1] === 'function') {
        arguments[1].call(this);
      }

      return this;
    }

    getOptions() {
      return this.options;
    }

    isOpen() {
      return (this.isOpen);
    }

    open() {
      // Capture what element triggered the modal so we can return focus later
      this.$modalTriggerElement = $(document.activeElement);

      // Make sure all modals are closed before opening a new one
      this.closeAll();

      // Construct a placeholder element so we know where to put the modal
      // content back after closing
      this.$placeholder = $('<div></div>')
        .hide();
      this.$target.after(this.$placeholder);
      this.detached = this.$target.detach();
      this.$modalContent.append(this.detached);

      // Fire an event for other scripts to hook into and do something
      // before the modal is opened
      this.trigger('modal:open');

      // Disable tabbing through elements below the modal layer
      this.$site
        .attr('tabindex', '-1')
        .attr('aria-hidden', true)
        .find(focusableSelectors)
        .attr('tabindex', '-1');

      // Show the modal now
      $body.removeClass('has-closed-modal').addClass('has-open-modal');
      this.$modalFrame.removeAttr('aria-hidden');

      // Set focus on the modal to enable escape key closing
      this.$modalContent
        .attr('tabindex', '0')
        .focus();
      this.isOpen = true;

      // Fire an event for other scripts to hook into and do something
      // after the modal has opened
      this.trigger('modal:opened');
    }

    close() {
      if (!this.isOpen) {
        return false;
      }

      // Hide the modal
      $body.removeClass('has-open-modal').addClass('has-closed-modal');

      // Put the modal contents back to where we found it
      this.$placeholder.replaceWith(this.$target);
      this.$modalContent.html('');
      this.$modalFrame.attr('aria-hidden', 'true');

      // Fire an event for other scripts to hook into and do something
      // when the modal is closed
      this.trigger('modal:close');

      // Reenable tabbing through elements below the modal layer
      this.$site
        .removeAttr('tabindex')
        .removeAttr('aria-hidden')
        .find(focusableSelectors)
        .removeAttr('tabindex');

      // Set the focus back to the element that triggered the modal
      this.$modalTriggerElement.focus();
      this.$modalTriggerElement = null;
      this.isOpen = false;
    }

    closeAll() {
      for(var i = 0; i < allModals.length; i++) {
        allModals[i].close();
      }
    }

    on(event, callback) {
      var eventName = event + '-' + this.options.uniqueID;
      this.$modalContent.on(eventName, callback);
      return this;
    }

    trigger(event) {
      var eventName = event + '-' + this.options.uniqueID;
      this.$modalContent.trigger(eventName, this);
    }
  };

}(jQuery));
