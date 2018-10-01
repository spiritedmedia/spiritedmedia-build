/* global adUnits googletag */
var pbjs = pbjs || {};
pbjs.que = pbjs.que || [];

pbjs.que.push(function () {
  // Enable Google Analytics
  pbjs.enableAnalytics([{
    provider: 'ga',
    options: {
      enableDistribution: false,
      sampling: 0.01 // any value between 0 to 1
    }
  }]);

  // Adding adUnits to pbjs
  pbjs.addAdUnits(adUnits);
  // request Bids
  pbjs.requestBids({
    bidsBackHandler: function (bidResponses) {
      googletag.cmd.push(function () {
        if (pbjs) {
          pbjs.setTargetingForGPTAsync();
        }
        googletag.pubads().refresh();
      });
      if (window.location.search.indexOf('pbjs_debug') >= 0) {
        /* eslint-disable no-console */
        console.log('pbjs_bidResponses');
        for (var key in bidResponses) {
          if (!bidResponses.hasOwnProperty(key)) {continue;}
          var obj = bidResponses[key];
          for (var prop in obj) {
            if (!obj.hasOwnProperty(prop)) {continue;}
            console.log('bids for ' + key + ' = ' + obj[prop].length);
            console.log(obj[prop]);
          }
          /* eslint-enable no-console */
        }
      }
    },
    timeout: 1000
  });
});
