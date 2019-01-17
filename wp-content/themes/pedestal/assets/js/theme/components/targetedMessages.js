/**
 * Handle targeting messages based on contactData cookie values
 *
 * @param  {Event} e     Data about the event being triggered
 * @param  {Object} data contactData cookie value
 */
export function handleTargetedMessages(e, data) {
  if ($('body').is('.is-target-audience--disabled')) {
    return;
  }

  let targetAudience = false;
  if (('data' in data)) {
    const theData = data.data;
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
  }

  if (targetAudience) {
    setTargetAudience(targetAudience);
  }
}

/**
 * Set the target audience class on the <body>
 *
 * @param {[String} targetAudience The attribute-friendly target audience
 */
export function setTargetAudience(targetAudience) {
  $('body')
    .removeClass((i, className) => {
      const matches = className.match(/(^|\s)(is-target-audience)\S+/g) || [];
      return matches.join(' ');
    })
    .addClass('is-target-audience--' + targetAudience);
}
