export default function handleTargetedMessages(e, data) {
  if ($('body').is('.is-target-audience--disabled')) {
    return;
  }

  if (!('data' in data)) {
    return;
  }

  const theData = data.data;
  let targetAudience = false;
  if ('current_member' in theData && theData.current_member) {
    targetAudience = 'member';
  } else if ('donate_365' in theData && theData.donate_365) {
    targetAudience = 'donor';
  } else if (
    'newsletter_subscriber' in theData
    && theData.newsletter_subscriber
  ) {
    targetAudience = 'subscriber';
  }

  if (targetAudience) {
    $('body')
      .removeClass((i, className) => {
        const matches = className.match(/(^|\s)(is-target-audience)\S+/g) || [];
        return matches.join(' ');
      })
      .addClass('is-target-audience--' + targetAudience);
  }
}
