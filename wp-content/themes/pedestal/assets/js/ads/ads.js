/* eslint-disable max-len */
const classes = 'adsbox text_ads text-ads pub_300x250m pub_300x250 text-ad text-ad-links pub_728x90 text_ad textAd';
const styleInvisible = 'width: 1px !important; height: 1px !important; position: absolute !important; left: -10000px !important; top: -1000px !important;';
/* eslint-enable max-len */
const bait = document.createElement('div');
bait.setAttribute('class', classes);
bait.setAttribute('style', styleInvisible);
document.body.appendChild(bait);
