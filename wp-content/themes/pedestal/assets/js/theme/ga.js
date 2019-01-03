/* global ga, PedestalGAData, localStorageCookie */

ga('create', PedestalGAData.id, 'auto', { 'allowLinker': true });
ga('require', 'displayfeatures');

if (PedestalGAData.optimizeID) {
  ga('require', PedestalGAData.optimizeID);
}

ga('require', 'linker');
ga('linker:autoLink', ['checkout.fundjournalism.org']);

// Set up custom dimensions
ga((tracker) => {
  // If no cookie is set
  let contactEmailDimension = 'Unknown';
  let memberLevelDimension = 'Unknown';

  const contactData = localStorageCookie('contactData');
  if (contactData && 'data' in contactData) {
    const data = contactData.data;

    // Contact Email Dimension
    if (data.subscribed_to_list) {
      contactEmailDimension = 'Other';
    }
    if (data.newsletter_subscriber) {
      contactEmailDimension = 'Daily Newsletter';
    }

    // Contact Member Level Dimension
    if (!data.current_member && !data.donate_365) {
      memberLevelDimension = 'None';
    }
    if (!data.current_member && data.donate_365) {
      memberLevelDimension = 'Donor';
    }
    // In theory this conditional should never be triggered but if something
    // goes wrong with data.member_level this will have us covered
    if (data.current_member) {
      memberLevelDimension = 'Error';
    }
    if (data.current_member && data.member_level > 0) {
      memberLevelDimension = 'Member ' + data.member_level;
    }
  }
  tracker.set('dimension1', contactEmailDimension);
  tracker.set('dimension2', memberLevelDimension);

  // Contact Post Visiting Frequency
  let contactFrequency = '0 posts';
  const contactHistory = localStorageCookie('contactHistory');
  if (contactHistory) {
    // Only count posts
    const posts = contactHistory.filter((item) => {
      return (item.u.slice(0, 3) === '/20');
    });
    const postCount = posts.length;
    if (postCount == 1) {
      contactFrequency = '1 post';
    } else if (postCount >= 2 && postCount < 4) {
      contactFrequency = '2-3 posts';
    } else if (postCount >= 4 && postCount < 6) {
      contactFrequency = '4-5 posts';
    } else if (postCount >= 6 && postCount < 9) {
      contactFrequency = '6-8 posts';
    } else if (postCount >= 9 && postCount < 14) {
      contactFrequency = '9-13 posts';
    } else if (postCount >= 14 && postCount < 22) {
      contactFrequency = '14-21 posts';
    } else if (postCount >= 22 && postCount < 35) {
      contactFrequency = '22-34 posts';
    } else if (postCount >= 35 && postCount < 56) {
      contactFrequency = '35-55 posts';
    } else if (postCount >= 56) {
      contactFrequency = '56+';
    }
  }
  tracker.set('dimension3', contactFrequency);

  // Set up a global variable to store the cross-domain linker parameter for
  // use in our own scripts
  //
  // https://developers.google.com/analytics/devguides/collection/analyticsjs/linker#linkerparam
  window.gaLinkTrackerParam = tracker.get('linkerParam');
});

ga('send', 'pageview');
