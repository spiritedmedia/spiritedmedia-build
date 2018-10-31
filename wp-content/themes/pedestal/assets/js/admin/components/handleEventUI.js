export default function handleEventUI() {
  // Show/hide start/end time fields based on value of All Day checkbox
  $('#fm-event_details-0-all_day-0').on('change', function() {
    const startTime = '.fm-start_time-wrapper .fm-datepicker-time-wrapper';
    const endTime = '.fm-end_time-wrapper .fm-datepicker-time-wrapper';
    const timeSelectors = `${startTime}, ${endTime}`;

    $(timeSelectors).toggle(!this.checked);
  }).change();

  // We should clear out the time inputs when the date value is removed
  // because if the time values still exist upon saving the post, the date
  // input will become repopulated with today's date
  //
  // eslint-disable-next-line max-len
  const dateInputs = '#fm-event_details-0-start_time-0, #fm-event_details-0-end_time-0';
  $(dateInputs).on('change keyup copy paste cut', function() {
    const $this = $(this);
    if ($this.val().length === 0) {
      $this.closest('.fm-item').find('.fm-datepicker-time').val('');
    }
  });
}
