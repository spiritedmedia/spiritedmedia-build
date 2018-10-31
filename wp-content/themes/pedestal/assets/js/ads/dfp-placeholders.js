var googletag = window.googletag || {};
googletag.cmd = googletag.cmd || [];
googletag.cmd.push(function() {
  googletag.pubads().addEventListener('slotRenderEnded', function() {

    jQuery(document).ready(function($) {
    // Get the theme color from the page to be used for the placeholder ads
      var themeColor = $('meta[name="theme-color"]').attr('content');
      themeColor = themeColor.replace('#', '');
      if (!themeColor) {
        themeColor = 'ccc';
      }

      $('.js-dfp').each(function(index, ad) {
        var $ad = $(ad);
        var adSizes = $ad.data('dfp-sizes').split(',');
        var adName = $ad.data('dfp-name');
        var newHTML = '';
        for (var i = 0; i < adSizes.length; i++) {
          var adSize = adSizes[i];
          var adWidth = adSize.split('x')[0];
          var adHeight = adSize.split('x')[1];
          var dummyImg = 'https://dummyimage.com/' + adSize + '/' + themeColor + '/fff/.png';
          var imgHTML = `<img src="${dummyImg}">`;

          var displayVal = 'none';
          if (i === 0) {
            displayVal = 'block;';
          }

          var counterText = '';
          if (adSizes.length > 1) {
            counterText = (i + 1) + '/' + adSizes.length;
          }

          var $placeholder = $('<div></div>').css({
            width: adWidth + 'px',
            height: adHeight + 'px',
            marginLeft: 'auto',
            marginRight: 'auto',
            position: 'relative',
            display: displayVal
          });
          $placeholder.append(
            $('<a href="#" class="js-dfp-placeholder">' + imgHTML + '</a>'),
            $('<p>' + adName + '</p>').css({
              position: 'absolute',
              top: '5px',
              right: '5px',
              margin: 0,
              padding: 0,
              color: '#fff',
              fontSize: '10px'
            }),
            $('<p class="">' + counterText + '</p>').css({
              position: 'absolute',
              top: '18px',
              right: '5px',
              margin: 0,
              padding: 0,
              color: '#fff',
              fontSize: '10px'
            })
          );
          // Use outerHTML so we get the container <div> we started with
          newHTML += $placeholder[0].outerHTML;
        }
        // Need show() to override inline display: none; set by DFP script
        $ad.html(newHTML).show();
      }).on('click', '.js-dfp-placeholder', function(e) {
        e.preventDefault();
        var $this = $(this);
        var $children = $this.parents('.js-dfp').children();
        var numOfSizes = $children.length;
        if (numOfSizes < 2) {
          return;
        }
        var nextIndex = $this.parent().index() + 1;
        if (nextIndex > numOfSizes - 1) {
          nextIndex = 0;
        }
        $children.hide();
        $children.eq(nextIndex).show();
      });
    });
  });
});

window.googletag = googletag;
