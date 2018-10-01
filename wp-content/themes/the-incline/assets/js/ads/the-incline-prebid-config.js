/* exported adUnits */

// AdUnits
var adUnits = [];
var isMobile = (/Mobile/i.test(navigator.userAgent));
if (isMobile) {
  adUnits = [
    {
      'code': 'the.incline-mobile-m1',
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
            'placementId': '13893646'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297511',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'the-incline-d.openx.net', // Please verify delDomain
            'unit': '540305851'
          }
        },
        {
          'bidder': 'audienceNetwork',
          'params': {
            'placementId': '521361477908471_2079650208746249'
          }
        }
      ],
    },
    {
      'code': 'the.incline-mobile-m2',
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
            'placementId': '13893647'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297512',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'the-incline-d.openx.net', // Please verify delDomain
            'unit': '540305852'
          }
        },
        {
          'bidder': 'audienceNetwork',
          'params': {
            'placementId': '521361477908471_2079650615412875'
          }
        }
      ],
    },
    {
      'code': 'the.incline-mobile-m2',
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
            'placementId': '13893648'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297513',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'the-incline-d.openx.net', // Please verify delDomain
            'unit': '540305853'
          }
        },
        {
          'bidder': 'audienceNetwork',
          'params': {
            'placementId': '521361477908471_2079651105412826'
          }
        }
      ],
    }
  ];
} else {
  adUnits = [
    {
      'code': 'the.incline-desktop-1',
      'mediaTypes': {
        'banner': {
          'sizes': [
            [
              300,
              600
            ],
            [
              300,
              600
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
            'placementId': '13893643'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297508',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297508',
            'size': [
              300,
              600
            ]
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297508',
            'size': [
              160,
              600
            ]
          }
        },
        {
          'bidder': 'criteo',
          'params': {
            'zoneId': '1308065'
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'the-incline-d.openx.net', // Please verify delDomain
            'unit': '540305848'
          }
        }
      ],
    },
    {
      'code': 'the.incline-desktop-artclbox1',
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
            'placementId': '13893644'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297509',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'the-incline-d.openx.net', // Please verify delDomain
            'unit': '540305849'
          }
        }
      ],
    },
    {
      'code': 'the.incline-desktop-artclbox2',
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
            'placementId': '13893645'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297510',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'the-incline-d.openx.net', // Please verify delDomain
            'unit': '540305850'
          }
        }
      ],
    }
  ];
}
