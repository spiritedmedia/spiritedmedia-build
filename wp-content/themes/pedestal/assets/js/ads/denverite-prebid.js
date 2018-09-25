/* global googletag */

//AdUnits
var adUnits = [];
var isMobile = (/Mobile/i.test(navigator.userAgent));
var PREBID_TIMEOUT = 1000;
var ga_enabled = true; // Use this variable if Google Analytics is available
if (isMobile) {
  adUnits = [
    {
      'code':'denverite-mobile-m1',
      'mediaTypes':{
        'banner':{
          'sizes':[
            [
              300,
              250
            ]
          ]
        }
      },
      'bids':[
        {
          'bidder':'appnexus',
          'params':{
            'placementId':'13893629'
          }
        },
        {
          'bidder':'ix',
          'params':{
            'siteId':'297499',
            'size':[
              300,
              250
            ]
          }
        },
        {
          'bidder':'openx',
          'params':{
            'delDomain':'denverite-d.openx.net', // Please verify delDomain
            'unit':'540305839'
          }
        },
        {
          'bidder':'audienceNetwork',
          'params':{
            'placementId':'521361477908471_2079647845413152'
          }
        }
      ],
    },
    {
      'code':'denverite-mobile-m2',
      'mediaTypes':{
        'banner':{
          'sizes':[
            [
              300,
              250
            ]
          ]
        }
      },
      'bids':[
        {
          'bidder':'appnexus',
          'params':{
            'placementId':'13893631'
          }
        },
        {
          'bidder':'ix',
          'params':{
            'siteId':'297500',
            'size':[
              300,
              250
            ]
          }
        },
        {
          'bidder':'openx',
          'params':{
            'delDomain':'denverite-d.openx.net', // Please verify delDomain
            'unit':'540305840'
          }
        },
        {
          'bidder':'audienceNetwork',
          'params':{
            'placementId':'521361477908471_2079648355413101'
          }
        }
      ],
    },
    {
      'code':'denverite-mobile-m1',
      'mediaTypes':{
        'banner':{
          'sizes':[
            [
              300,
              250
            ]
          ]
        }
      },
      'bids':[
        {
          'bidder':'appnexus',
          'params':{
            'placementId':'13893641'
          }
        },
        {
          'bidder':'ix',
          'params':{
            'siteId':'297501',
            'size':[
              300,
              250
            ]
          }
        },
        {
          'bidder':'openx',
          'params':{
            'delDomain':'denverite-d.openx.net', // Please verify delDomain
            'unit':'540305841'
          }
        },
        {
          'bidder':'audienceNetwork',
          'params':{
            'placementId':'521361477908471_2079648822079721'
          }
        }
      ],
    }
  ];
} else {
  adUnits = [
    {
      'code':'denverite-desktop-1',
      'mediaTypes':{
        'banner':{
          'sizes':[
            [
              300,
              600
            ],
            [
              300,
              250
            ],
            [
              160,
              600
            ]
          ]
        }
      },
      'bids':[
        {
          'bidder':'appnexus',
          'params':{
            'placementId':'13893623'
          }
        },
        {
          'bidder':'ix',
          'params':{
            'siteId':'297496',
            'size':[
              300,
              600
            ]
          }
        },
        {
          'bidder':'ix',
          'params':{
            'siteId':'297496',
            'size':[
              300,
              250
            ]
          }
        },
        {
          'bidder':'ix',
          'params':{
            'siteId':'297496',
            'size':[
              160,
              600
            ]
          }
        },
        {
          'bidder':'criteo',
          'params':{
            'zoneId':'1308083'
          }
        },
        {
          'bidder':'openx',
          'params':{
            'delDomain':'denverite-d.openx.net',// Please verify delDomain
            'unit':'540305836'
          }
        }
      ],
    },
    {
      'code':'denverite-desktop-artclbox1',
      'mediaTypes':{
        'banner':{
          'sizes':[
            [
              300,
              250
            ]
          ]
        }
      },
      'bids':[
        {
          'bidder':'appnexus',
          'params':{
            'placementId':'13893624'
          }
        },
        {
          'bidder':'ix',
          'params':{
            'siteId':'297497',
            'size':[
              300,
              250
            ]
          }
        },
        {
          'bidder':'openx',
          'params':{
            'delDomain':'denverite-d.openx.net',// Please verify delDomain
            'unit':'540305837'
          }
        }
      ],
    },
    {
      'code':'denverite-desktop-artclbox2',
      'mediaTypes':{
        'banner':{
          'sizes':[
            [
              300,
              250
            ]
          ]
        }
      },
      'bids':[
        {
          'bidder':'appnexus',
          'params':{
            'placementId':'13893627'
          }
        },
        {
          'bidder':'ix',
          'params':{
            'siteId':'297498',
            'size':[
              300,
              250
            ]
          }
        },
        {
          'bidder':'openx',
          'params':{
            'delDomain':'denverite-d.openx.net', // Please verify delDomain
            'unit':'540305838'
          }
        }
      ],
    }
  ];
}

var pbjs = pbjs || {};
pbjs.que = pbjs.que || [];

if (ga_enabled) {
  pbjs.que.push(function () {
    pbjs.enableAnalytics([{
      provider: 'ga',
      options: {
        enableDistribution: false,
        sampling: 0.01 // any value between 0 to 1
      }
    }]);
  });
}
pbjs.que.push(function () {
  //Adding adUnits to pbjs
  pbjs.addAdUnits(adUnits);
  //request Bids
  pbjs.requestBids({
    bidsBackHandler: function (bidResponses) {
      initAdServer();
      if (getQueryVariable('pbjs_debug')) {
        /* eslint-disable no-console */
        // console.log('Are pbjs bids available? : ' + pbjs.allBidsAvailable());
        console.log('pbjs_bidResponses');
        for (var key in bidResponses) {
          // skip loop if the property is from prototype
          if (!bidResponses.hasOwnProperty(key)) {continue;}
          var obj = bidResponses[key];
          for (var prop in obj) {
            // skip loop if the property is from prototype
            if (!obj.hasOwnProperty(prop)) {continue;}
            console.log('bids for ' + key + ' = ' + obj[prop].length);
            console.log(obj[prop]);
          }
          /* eslint-enable no-console */
        }
      }
    },
    timeout: PREBID_TIMEOUT
  });
});

function getQueryVariable(variable) {
  var query = window.location.search.substring(1);
  var vars = query.split('&');
  for (var i = 0; i < vars.length; i++) {
    var pair = vars[i].split('=');
    if (pair[0] == variable) {
      return pair[1];
    }
  }
  return false;
}

//GPT Request
function initAdServer() {
  googletag.cmd.push(function () {
    if (pbjs) {
      pbjs.setTargetingForGPTAsync();
    }
    googletag.pubads().refresh();
  });
}
