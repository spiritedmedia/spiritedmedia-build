// Video controls will be hidden for small screens < 480px wide
const showVideoControls = ($(window).width() >= 480) ? 1 : 0;

export default function lazyLoad(e) {
  const $this = $(this);
  const youTubeID = $this.data('youtube-id');

  if (!youTubeID) {
    return;
  }

  const $parent = $this.parents('.js-yt-placeholder');
  const params = {
    autoplay: 1,
    cc_load_policy: 1,
    color: 'white',
    controls: showVideoControls,
    rel: 0,
    showinfo: 0
  };
  const query = $.param(params);
  const iframeURL = `https://www.youtube.com/embed/${youTubeID}?${query}`;

  let youTubeIframe = '<iframe ';
  youTubeIframe += 'src="' + iframeURL + '" ';
  youTubeIframe += 'frameborder="0" ';
  youTubeIframe += 'allowfullscreen ';
  youTubeIframe += '/>';

  // Append the iFrame so it can load a little bit
  $parent.append(youTubeIframe);
  // Fadeout the play icon and link
  $this.fadeOut(750, function() {
    $this.remove();
  });
  e.preventDefault();
}
