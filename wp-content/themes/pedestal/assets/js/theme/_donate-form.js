/* exported DonateForm */

function DonateForm() {
  const $form = $('.js-donate-form');

  updateFormActionURL();
  calculateAmountForPeriod();

  /**
   * Handle the changing donation form action URL
   *
   * Recurring and one-time payments have different action URLs.
   *
   * @param  {jQuery} $form jQuery object of the donate form
   */
  function updateFormActionURL() {
    const endpointDomain = $form.data('nrh-endpoint-domain');

    $form.on('change', '.js-donate-form-frequency', function() {
      let endpointPath;

      // Handle different action URLs for one-time vs. recurring donations
      if ($(this).val() === '') {
        endpointPath = '/donateform';
      } else {
        endpointPath = '/memberform';
      }
      $form.attr('action', endpointDomain + endpointPath);
    });
  }

  /**
   * Calculate the amount input value based on the selected installation period
   */
  function calculateAmountForPeriod() {
    let prevPeriod;

    $form.on('change', '.js-donate-form-frequency', function() {
      const $amountInput = $form.find('.js-donate-form-amount');
      const currentPeriod = $(this).val();
      const oldAmount = parseInt($amountInput.val());
      let newAmount = oldAmount;

      if (
        (currentPeriod === 'yearly' && prevPeriod !== '') ||
        (currentPeriod === '' && prevPeriod !== 'yearly')
      ) {
        newAmount = oldAmount * 12;
      } else if (currentPeriod === 'monthly') {
        newAmount = oldAmount / 12;
      }

      newAmount = Math.ceil(newAmount);
      $amountInput.val(newAmount);
      prevPeriod = currentPeriod;
    });
  }
}
