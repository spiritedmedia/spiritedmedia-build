export default function handleConversionPromptTargeting(e, data) {
  if ($('body').is('.is-target-audience--disabled')) {
    return;
  }

  // Make sure data has the values we expect
  if (!('data' in data)) {
    return;
  }
  var theData = data.data;
  var targetAudience = false;
  if ('current_member' in theData && theData.current_member) {
    targetAudience = 'member';
  } else if ('donate_365' in theData && theData.donate_365) {
    targetAudience = 'donor';
  } else if (
    'newsletter_subscriber' in theData &&
    theData.newsletter_subscriber
  ) {
    targetAudience = 'subscriber';
  }

  if (targetAudience) {
    swapTargetedAudienceClass(targetAudience);
  }
}

function swapTargetedAudienceClass(target) {
  $('body')
    .removeClass(function(index, className) {
      var matches = className.match(/(^|\s)(is-target-audience)\S+/g);
      if (! matches) {
        matches = [];
      }
      return matches.join(' ');
    })
    .addClass('is-target-audience--' + target);
}
