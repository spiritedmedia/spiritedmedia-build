/* exported adUnits */

// AdUnits
var adUnits = [];
var isMobile = (/Mobile/i.test(navigator.userAgent));
if (isMobile) {
  adUnits = [
    {
      'code': 'denverite-mobile-m1',
      'mediaTypes': {
        'banner': {
          'sizes': [
            [
              300,
              250
            ]
          ]
        }
      },
      'bids': [
        {
          'bidder': 'appnexus',
          'params': {
            'placementId': '13893629'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297499',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'denverite-d.openx.net', // Please verify delDomain
            'unit': '540305839'
          }
        },
        {
          'bidder': 'audienceNetwork',
          'params': {
            'placementId': '521361477908471_2079647845413152'
          }
        }
      ],
    },
    {
      'code': 'denverite-mobile-m2',
      'mediaTypes': {
        'banner': {
          'sizes': [
            [
              300,
              250
            ]
          ]
        }
      },
      'bids': [
        {
          'bidder': 'appnexus',
          'params': {
            'placementId': '13893631'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297500',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'denverite-d.openx.net', // Please verify delDomain
            'unit': '540305840'
          }
        },
        {
          'bidder': 'audienceNetwork',
          'params': {
            'placementId': '521361477908471_2079648355413101'
          }
        }
      ],
    },
    {
      'code': 'denverite-mobile-m1',
      'mediaTypes': {
        'banner': {
          'sizes': [
            [
              300,
              250
            ]
          ]
        }
      },
      'bids': [
        {
          'bidder': 'appnexus',
          'params': {
            'placementId': '13893641'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297501',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'denverite-d.openx.net', // Please verify delDomain
            'unit': '540305841'
          }
        },
        {
          'bidder': 'audienceNetwork',
          'params': {
            'placementId': '521361477908471_2079648822079721'
          }
        }
      ],
    }
  ];
} else {
  adUnits = [
    {
      'code': 'denverite-desktop-1',
      'mediaTypes': {
        'banner': {
          'sizes': [
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
      'bids': [
        {
          'bidder': 'appnexus',
          'params': {
            'placementId': '13893623'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297496',
            'size': [
              300,
              600
            ]
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297496',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297496',
            'size': [
              160,
              600
            ]
          }
        },
        {
          'bidder': 'criteo',
          'params': {
            'zoneId': '1308083'
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'denverite-d.openx.net',// Please verify delDomain
            'unit': '540305836'
          }
        }
      ],
    },
    {
      'code': 'denverite-desktop-artclbox1',
      'mediaTypes': {
        'banner': {
          'sizes': [
            [
              300,
              250
            ]
          ]
        }
      },
      'bids': [
        {
          'bidder': 'appnexus',
          'params': {
            'placementId': '13893624'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297497',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'denverite-d.openx.net',// Please verify delDomain
            'unit': '540305837'
          }
        }
      ],
    },
    {
      'code': 'denverite-desktop-artclbox2',
      'mediaTypes': {
        'banner': {
          'sizes': [
            [
              300,
              250
            ]
          ]
        }
      },
      'bids': [
        {
          'bidder': 'appnexus',
          'params': {
            'placementId': '13893627'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297498',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'denverite-d.openx.net', // Please verify delDomain
            'unit': '540305838'
          }
        }
      ],
    }
  ];
}
