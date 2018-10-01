/* exported adUnits */

// AdUnits
var adUnits = [];
var isMobile = (/Mobile/i.test(navigator.userAgent));
if (isMobile) {
  adUnits = [
    {
      'code': 'billy.penn-mobile-m1',
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
            'placementId': '13893616'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297505',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'billypenn-d.openx.net', // Please verify delDomain
            'unit': '540305845'
          }
        },
        {
          'bidder': 'audienceNetwork',
          'params': {
            'placementId': '521361477908471_2079652068746063'
          }
        }
      ],
    },
    {
      'code': 'billy.penn-mobile-m2',
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
            'placementId': '13893617'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297506',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'billypenn-d.openx.net', // Please verify delDomain
            'unit': '540305846'
          }
        },
        {
          'bidder': 'audienceNetwork',
          'params': {
            'placementId': '521361477908471_2079652518746018'
          }
        }
      ],
    },
    {
      'code': 'billy.penn-mobile-m3',
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
            'placementId': '13893618'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297507',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'billypenn-d.openx.net', // Please verify delDomain
            'unit': '540305847'
          }
        },
        {
          'bidder': 'audienceNetwork',
          'params': {
            'placementId': '521361477908471_2079652842079319'
          }
        }
      ],
    }
  ];

} else {
  adUnits = [
    {
      'code': 'billy.penn-desktop-1',
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
            'placementId': '13893598'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297502',
            'size': [
              300,
              600
            ]
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297502',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297502',
            'size': [
              160,
              600
            ]
          }
        },
        {
          'bidder': 'criteo',
          'params': {
            'zoneId': '1308074'
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'billypenn-d.openx.net', // Please verify delDomain
            'unit': '540305842'
          }
        }
      ],
    },
    {
      'code': 'billy.penn-desktop-artclbox1',
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
            'placementId': '13893614'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297503',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'billypenn-d.openx.net', // Please verify delDomain
            'unit': '540305843'
          }
        }
      ],
    },
    {
      'code': 'billy.penn-desktop-artclbox2',
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
            'placementId': '13893615'
          }
        },
        {
          'bidder': 'ix',
          'params': {
            'siteId': '297504',
            'size': [
              300,
              250
            ]
          }
        },
        {
          'bidder': 'openx',
          'params': {
            'delDomain': 'billypenn-d.openx.net', // Please verify delDomain
            'unit': '540305844'
          }
        }
      ],
    }
  ];
}
