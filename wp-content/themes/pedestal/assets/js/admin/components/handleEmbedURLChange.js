/* global fm */

const domainBoxes = {
  instagram: {
    domainSubstring: 'instagr',
    selector: '#fm_meta_box_daily_insta_date',
  },
  twitter: {
    domainSubstring: 'twitter.com',
    selector: '#fm_meta_box_embed_options',
  },
};

export default (e) => {
  const $target = $(e.target);
  const url = $target.val();

  for (const [key, val] of Object.entries(domainBoxes)) {
    const $el = $(val.selector);

    if (url.includes(val.domainSubstring)) {
      $el.show();
    } else {
      $el.hide();
    }

    if (key == 'instagram') {
      // Reload the Fieldmanager datepicker
      fm.datepicker.add_datepicker(e);
    }
  }
};
