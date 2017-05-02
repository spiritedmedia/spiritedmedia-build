<footer class="site-footer" role="contentinfo">

  <div class="row">

    <div class="site-footer-social medium-4 medium-push-8 columns">
      <ul class="iconset">
        <li class="social-icon ">
          <a href="#subscribe-to-newsletter" data-reveal-id="subscribe-to-newsletter" data-ga-category="Social Icon - Footer" data-ga-label="Email">
            <?php styleguide_icon( 'envelope' ); ?>
          </a>
        </li>

        <li class="social-icon ">
          <a href="https://www.instagram.com/billy_penn/" target="_blank" data-ga-category="Social Icon - Footer" data-ga-label="Instagram">
            <?php styleguide_icon( 'instagram' ); ?>
          </a>
        </li>
        <li class="social-icon ">
          <a href="https://twitter.com/billy_penn" target="_blank" data-ga-category="Social Icon - Footer" data-ga-label="Twitter">
            <?php styleguide_icon( 'twitter' ); ?>
          </a>
        </li>
        <li class="social-icon ">
          <a href="https://www.facebook.com/billypennnews" target="_blank" data-ga-category="Social Icon - Footer" data-ga-label="Facebook">
            <?php styleguide_icon( 'facebook' ); ?>
          </a>
        </li>

      </ul>
    </div>

    <div class="site-footer-links large-6 large-pull-6 medium-8 medium-pull-4 columns">
      <div class="row">
        <div class="columns medium-4">
          <ul class="no-bullet">
            <li><a href="/about" data-ga-category="Footer Links" data-ga-label="About">About</a></li>
            <li><a href="https://medium.com/billy-penn" data-ga-category="Footer Links" data-ga-label="Blog">Blog</a></li>
          </ul>
        </div>
        <div class="columns medium-4">
          <ul class="no-bullet">
            <li><a href="/jobs" data-ga-category="Footer Links" data-ga-label="Jobs">Jobs</a></li>
            <li><a href="/press" data-ga-category="Footer Links" data-ga-label="Press">Press</a></li>
          </ul>
        </div>
        <div class="columns medium-4">
          <ul class="last no-bullet">
            <li><a href="/advertising" data-ga-category="Footer Links" data-ga-label="Advertising">Advertising</a></li>
            <li><a href="/terms-of-use" data-ga-category="Footer Links" data-ga-label="Terms of Use">Terms of Use</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="site-footer-copyright columns small-12">
      <p class="site-copyright"><i class="footer-logo"></i><span class="copyright-text">Copyright &copy; 2017 Spirited Media. All rights reserved.</span></p>
    </div>
  </div>

</footer>

<nav class="main-navigation" role="navigation">

  <div class="off-canvas-menu right-off-canvas-menu">

    <ul class="off-canvas-menu__list off-canvas-list">

      <li class="off-canvas-menu__item"><a href="/newsletter-signup" data-ga-category="Menu - Main Navigation" data-ga-label="Newsletter">Newsletter</a></li>

      <li class="off-canvas-menu__follow off-canvas-menu__item">
        <a href="#" class="disabled" data-ga-category="Menu - Main Navigation" data-ga-label="Disabled|Follow">Follow</a>
        <ul class="off-canvas-menu__follow__icons iconset">

          <li class="social-icon ">
            <a href="https://www.instagram.com/billy_penn/" target="_blank" data-ga-category="Social Icon - Off_canvas" data-ga-label="Instagram">
              <?php styleguide_icon( 'instagram' ); ?>
            </a>
          </li>
          <li class="social-icon ">
            <a href="https://twitter.com/billy_penn" target="_blank" data-ga-category="Social Icon - Off_canvas" data-ga-label="Twitter">
              <?php styleguide_icon( 'twitter' ); ?>
            </a>
          </li>
          <li class="social-icon ">
            <a href="https://www.facebook.com/billypennnews" target="_blank" data-ga-category="Social Icon - Off_canvas" data-ga-label="Facebook">
              <?php styleguide_icon( 'facebook' ); ?>
            </a>
          </li>

        </ul>
      </li>

      <li class="off-canvas-menu__item"><a href="/about" data-ga-category="Menu - Main Navigation" data-ga-label="About">About</a></li>
      <li class="off-canvas-menu__item"><a href="https://medium.com/billy-penn" data-ga-category="Menu - Main Navigation" data-ga-label="Blog">Blog</a></li>
      <li class="off-canvas-menu__item"><a href="/jobs" data-ga-category="Menu - Main Navigation" data-ga-label="Jobs">Jobs</a></li>
      <li class="off-canvas-menu__item"><a href="/press" data-ga-category="Menu - Main Navigation" data-ga-label="Press">Press</a></li>
      <li class="off-canvas-menu__item"><a href="/advertising" data-ga-category="Menu - Main Navigation" data-ga-label="Advertising">Advertising</a></li>
      <li class="off-canvas-menu__item"><a href="/terms-of-use" data-ga-category="Menu - Main Navigation" data-ga-label="Terms of Use">Terms of Use</a></li>
      <li class="off-canvas-menu__item"><a href="/privacy-policy" data-ga-category="Menu - Main Navigation" data-ga-label="Privacy Policy">Privacy Policy</a></li>

    </ul>

  </div>

  <a class="exit-off-canvas" href="#close" data-ga-category="Menu - Main Navigation" data-ga-label="Close"></a>

</nav>


</div>
</div>

<div id="subscribe-to-newsletter" class="reveal-modal dark-reveal-modal small" data-reveal>
  <a class="close-reveal-modal">&#215;</a>
  <form action="https://billypenn.com/subscribe-to-email-list/" method="POST" data-confirm-id="confirm-subscribe-to-newsletter" data-ga-category="Submitted Form" data-ga-label="Newsletter Signup Modal">
    <div class="form-fields">
      <fieldset>
        <legend><span class="show-for-sr">Sign up for </span>Newsletter and Breaking News</legend>
        <label>
    Get once-daily headlines and occasional breaking news in your inbox.
    <input type="email" name="email_address" placeholder="william.penn@example.org" required />
</label>
        <input type="hidden" name="list-ids[]" value="320">
        <input type="hidden" name="list-ids[]" value="381">
        <label class="pedestal-current-year-check" for="pedestal-current-year-check">
    What is the current year?
    <input type="text" name="pedestal-current-year-check" id="pedestal-current-year-check" class="js-pedestal-current-year-check" value="" placeholder="YYYY" >
</label>
        <label style="display:none;" for="pedestal-blank-field-check">
    Leave this field blank.
    <input type="text" name="pedestal-blank-field-check" id="pedestal-blank-field-check" value="" placeholder="">
</label>
      </fieldset>
      <button class="btn btn--secondary form-submit js-form-submit" type="submit" name="subscribe">
        <span class="form-submit__text">Sign up</span>
        <div class="form-submit__loader"><div class="c-spinner js-spinner">
            <div class="c-spinner__inner sk-three-bounce">
                <div class="sk-child sk-bounce1"></div>
                <div class="sk-child sk-bounce2"></div>
                <div class="sk-child sk-bounce3"></div>
            </div>
          </div>
        </div>
      </button>
    </div>
  </form>
</div>

<div id="confirm-subscribe-to-newsletter" class="reveal-modal dark-reveal-modal small" data-reveal>
  <a class="close-reveal-modal">&#215;</a>
  <form>
    <fieldset>
      <legend>Thanks for signing up!</legend>
      <p>Please check your email to confirm your subscription.</p>
    </fieldset>
  </form>
</div>

<div class="c-search c-search--sitewide">
  <form class="c-search__form row js-search-form" action="https://billypenn.com" role="search">

    <div class="c-search__input js-search-input">
      <button type="submit" class="c-search__icon--search  c-search__icon">
        <?php styleguide_icon( 'search' ); ?>
        <span class="show-for-sr">submit</span>
      </button>

      <input type="text" class="c-search__field  js-search-field" name="s" id="the-search-field" placeholder="Enter search term" value="" tabindex="1" />

      <a class="c-search__icon--close  c-search__icon  js-search-icon-close" href="#">
        <?php styleguide_icon( 'times' ); ?>
        <span class="show-for-sr">close search</span>
      </a>
    </div>

    <div class="c-spinner js-spinner">
      <div class="c-spinner__inner sk-three-bounce">
        <div class="sk-child sk-bounce1"></div>
        <div class="sk-child sk-bounce2"></div>
        <div class="sk-child sk-bounce3"></div>
      </div>
    </div>

  </form>
</div>

<!-- 10.0.2.181 -->
<script type='text/javascript' src='https://a.spirited.media/wp-content/themes/pedestal/assets/dist/js/fastclick.js?ver=1.0'></script>
<script type='text/javascript' src='https://a.spirited.media/wp-content/themes/pedestal/assets/dist/js/pedestal.js?ver=5.2.5'></script>
<script type='text/javascript' src='https://s.ntv.io/serve/load.js'></script>
<script type='text/javascript' src='https://boxter.co/f23.js'></script>
<script type='text/javascript' src='https://a.spirited.media/wp-includes/js/wp-embed.min.js?ver=4.7.2'></script>
<script type='text/javascript' src='https://a.spirited.media/wp-content/plugins/shortcake-bakery/assets/js/shortcake-bakery.js?ver=0.2.0-alpha'></script>



<script>
  (function(i, s, o, g, r, a, m) {
    i['GoogleAnalyticsObject'] = r;
    i[r] = i[r] || function() {
      (i[r].q = i[r].q || []).push(arguments)
    }, i[r].l = 1 * new Date();
    a = s.createElement(o),
      m = s.getElementsByTagName(o)[0];
    a.async = 1;
    a.src = g;
    m.parentNode.insertBefore(a, m)
  })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

  ga('create', 'UA-54099407-1', 'auto');
  ga('require', 'displayfeatures');


  ga('send', 'pageview');
</script>


<!-- START Parse.ly Include: Standard -->
<div id="parsely-root" style="display: none">
  <div id="parsely-cfg" data-parsely-site="billypenn.com"></div>
</div>
<script>
  (function(s, p, d) {
    var h = d.location.protocol,
      i = p + "-" + s,
      e = d.getElementById(i),
      r = d.getElementById(p + "-root"),
      u = h === "https:" ? "d1z2jf7jlzjs58.cloudfront.net" :
      "static." + p + ".com";
    if (e) return;
    e = d.createElement(s);
    e.id = i;
    e.async = true;
    e.src = h + "//" + u + "/p.js";
    r.appendChild(e);
  })("script", "parsely", document);
</script>
<!-- END Parse.ly Include -->

<script type="text/javascript">
  var trackcmp_email = '';
  var trackcmp = document.createElement("script");
  trackcmp.async = true;
  trackcmp.type = 'text/javascript';
  trackcmp.src = '//trackcmp.net/visit?actid=609658886&e=' + encodeURIComponent(trackcmp_email) + '&r=' + encodeURIComponent(document.referrer) + '&u=' + encodeURIComponent(window.location.href);
  var trackcmp_s = document.getElementsByTagName("script");
  if (trackcmp_s.length) {
    trackcmp_s[0].parentNode.appendChild(trackcmp);
  } else {
    var trackcmp_h = document.getElementsByTagName("head");
    trackcmp_h.length && trackcmp_h[0].appendChild(trackcmp);
  }
</script>
</body>

</html>
