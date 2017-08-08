<?php
function sent_home_contact_form() {
    $required_fields = [ 'contact-message', 'contact-check', 'contact-year' ];
    foreach ( $required_fields as $field ) {
        if ( ! isset( $_POST[ $field ] ) ) {
            return false;
        }
    }

    if ( ! empty( $_POST['contact-check'] ) || date( 'Y' ) != $_POST['contact-year'] ) {
        return false;
    }

    $their_name = sanitize_text_field( $_POST['contact-name'] );
    $their_email = sanitize_email( $_POST['contact-email'] );
    $their_subject = sanitize_text_field( $_POST['contact-subject'] );
    if ( ! $their_subject ) {
        $their_subject = '(no subject)';
    }
    $their_message = sanitize_text_field( $_POST['contact-message'] );
    $headers = [ "From: $their_name <$their_email>" ];

    return wp_mail( 'contact@spiritedmedia.com', $their_subject, $their_message, $headers );
}

$press_links = [
    [
        'title' => 'With a cross-country merger, Spirited Media aims to build a nationwide digital local news chain',
        'publication' => 'Nieman Journalism Lab',
        'date' => 'March 8, 2017',
        'url' => 'http://www.niemanlab.org/2017/03/newsonomics-with-a-cross-country-merger-spirited-media-aims-to-build-a-nationwide-digital-local-news-chain/',
    ],
    [
        'title' => 'Billy Penn taking its formula to Pittsburgh with help from Gannett investment',
        'publication' => 'CNN Money',
        'date' => 'May 9, 2016',
        'url' => 'http://money.cnn.com/2016/05/09/media/billy-penn-gannett-pittsburgh/index.html',
    ],
    [
        'title' => 'Former Post editor gets Gannett investment in Philadelphia news website',
        'publication' => 'The Washington Post',
        'date' => 'March 22, 2016',
        'url' => 'https://www.washingtonpost.com/business/capitalbusiness/former-post-editor-gets-gannett-investment-in-philadelphia-news-website/2016/03/22/7d351986-f058-11e5-85a6-2132cf446d0a_story.html?wprss=rss_social-postbusinessonly',
    ],
    [
        'title' => 'Philadelphia’s Billy Penn — live events and all — may soon be coming to a city near you',
        'publication' => 'Nieman Journalism Lab',
        'date' => 'March 22, 2016',
        'url' => 'http://www.niemanlab.org/2016/03/philadelphias-billy-penn-live-events-and-all-may-soon-be-coming-to-a-city-near-you/',
    ],
    [
        'title' => 'Amid big changes in Philly media, startup Billy Penn sticks to its vision',
        'url' => 'http://www.cjr.org/united_states_project/billy_penn.php',
        'publication' => 'Columbia Journalism Review',
        'date' => 'Feb. 4, 2016',
    ],
    [
        'title' => 'Coming soon: PolitiFact Pennsylvania!',
        'url' => 'http://www.politifact.com/truth-o-meter/article/2016/jan/13/coming-soon-politifact-pennsylania/',
        'publication' => 'PolitiFact.com',
        'date' => 'Jan. 13, 2015',
    ],
    [
        'title' => 'Billy Penn and Knight Foundation to create mobile journalism guide',
        'url' => 'http://www.knightfoundation.org/press-room/press-release/news-startup-billy-penn-create-mobile-journalism-g/',
        'publication' => 'KnightFoundation.org',
        'date' => 'Dec. 10, 2015',
    ],
    [
        'title' => 'Billy Penn wins Startup of the Year',
        'url' => 'http://www.geekadelphia.com/2015/08/17/the-philadelphia-geek-awards-2015-thank-you/',
        'publication' => 'Geekadelphia',
        'date' => 'Aug. 17, 2015',
    ],
    [
        'title' => 'The Billy Penn guide to local news that doesn’t suck',
        'url' => 'http://digiday.com/publishers/billy-penn-guide-local-news-doesnt-suck/',
        'publication' => 'Digiday',
        'date' => 'June 8, 2015',
    ],
    [
        'title' => 'Inside Billy Penn with CEO Jim Brady',
        'url' => 'http://ajr.org/2015/05/18/inside-billy-penn-with-ceo-jim-brady/',
        'publication' => 'American Journalism Review',
        'date' => 'May 18, 2015',
    ],
    [
        'title' => 'Jim Brady’s mobile-millennial Philadelphia local-news adventure',
        'url' => 'http://www.politico.com/media/story/2015/03/what-are-they-thinking-jim-bradys-mobile-millennial-philadelphia-local-news-adventure-003550',
        'publication' => 'Capital New York',
        'date' => 'March 10, 2015',
    ],
    [
        'title' => 'Billy Penn Is Brady’s Biggest Digital Bet',
        'url' => 'http://www.netnewscheck.com/article/39078/billy-penn-is-bradys-biggest-digital-bet',
        'publication' => 'NetNewsCheck',
        'date' => 'Feb. 25, 2015',
    ],
    [
        'title' => 'A New Philly Website With an Activist Streak',
        'url' => 'http://www.usatoday.com/story/money/columnist/rieder/2014/11/25/billy-penn-gets-underway-in-philly/70086222/',
        'publication' => 'USA Today',
        'date' => 'Nov. 25, 2014',
    ],
    [
        'title' => 'The Billy Pulpit',
        'url' => 'http://www.phillymag.com/articles/jim-brady-profile-billy-pulpit/',
        'publication' => 'Philadelphia Magazine',
        'date' => 'Sept. 25, 2014',
    ],
    [
        'title' => 'Brady Takes Another Shot at Local Journalism With New Venture',
        'url' => 'http://www.poynter.org/2014/brady-takes-another-shot-at-local-journalism-with-new-venture/258898/',
        'publication' => 'Poynter',
        'date' => 'July 16, 2014',
    ],
];

get_header(); ?>
    <section class="section--full-width home-hero">
        <h1 class="home-hero--heading">Reimagining Local News</h1>
        <p class="home-hero--tagline">A local news operation for the next generation</p>
    </section>

    <section class="section home-about">
        <h2 class="home-about--heading">About Spirited Media</h2>
        <p>Spirited Media is a company committed to finding a new editorial and business model for local journalism, and currently manages <a href="https://billypenn.com">Billy Penn</a> in Philadelphia, <a href="https://theincline.com">The Incline</a> in Pittsburgh, and <a href="https://www.denverite.com/">Denverite</a> in Denver.</p>

        <p>Our sites are produced and designed for mobile, and are aimed at an under-40 audience. We are all local all the time, and deliver the news via a stream that combines our original stories and curated links to other news of interest to local consumers. First and foremost, we value our readers’ time and their user experience. We circulate our work heavily via social platforms, and create a lively, unique voice for each city we cover.</p>

        <p>We are focused on bringing our local audiences together both online and offline, as we hold approximately four to five events per month and derive most of our revenue from events. We also believe that while all cities have real problems, the voices of those trying to solve those problems often go unheard, and we try and cover many of those efforts.</p>

        <div class="home-about--columns">
            <div class="home-about--column about-statement">
                <h3 class="about-statement--heading">Mission Statement</h3>
                <p class="about-statement--blurb">Spirited Media is a new kind of local media company for a new generation of news consumers.</p>
            </div>
            <div class="home-about--column about-statement">
                <h3 class="about-statement--heading">Team</h3>
                <p class="about-statement--blurb">Spirited Media's team includes veterans of The Washington Post, Philadelphia Inquirer, the Denver Post, WHYY, Bleacher Report, the Pittsburgh Post-Gazette, Digital First Media, Chalkbeat, Variety, America Online, the Hollywood Reporter and the Dallas Morning News.</p>
            </div>
            <div class="home-about--column about-statement">
                <h3 class="about-statement--heading">Company History</h3>
                <p class="about-statement--blurb">Spirited Media was founded by Jim and Joan Brady in 2014, and we launched our first site, Billy Penn, in October of that year. After a significant investment by Gannett in early 2016, we expanded the Billy Penn staff and launched in The Incline in Pittsburgh in September 2016. Spirited Media began operating Denverite in March 2017 after we merged with that site’s parent company, Avoriaz.</p>
            </div>
        </div>
    </section>

    <section class="home-strengths">
        <div class="section">
            <div class="home-strength">
                <?php echo svg_icon( 'gear' ); ?>
                <h3 class="home-strength--heading">Easily Scannable</h3>
                <p class="home-strength--blurb">Built to respect your time by delivering news to you in a simple stream format uncluttered by intrusive advertisements.</p>
            </div>
            <div class="home-strength">
                <?php echo svg_icon( 'star-outline' ); ?>
                <h3 class="home-strength--heading">Mobile First</h3>
                <p class="home-strength--blurb">Designed and produced primarily for consumers using mobile devices.</p>
            </div>
            <div class="home-strength">
                <?php echo svg_icon( 'paper-plane' ); ?>
                <h3 class="home-strength--heading">Curated Experience</h3>
                <p class="home-strength--blurb">In addition to our own stories, we curate good stories from other sites to allow consumers to use our sites as jumping-off points for news about their city.</p>
            </div>
        </div>
    </section>

    <section class="section home-our-board">
        <h2 class="home-our-board--heading">Our Board</h2>
        <ul class="home-board-members">
            <li class="home-board-members--item">
                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/home/jim-brady-headshot.jpg" class="home-board-members--image">
                <a href="https://billypenn.com/about/jim-brady/" class="home-board-members--name">Jim Brady</a>
                Founder &amp; CEO, Spirited Media
            </li>
            <li class="home-board-members--item">
                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/home/joanne-lipman-headshot.jpg" class="home-board-members--image">
                <a href="http://www.gannett.com/who-we-are/leadership-team/Joanne-Lipman/" class="home-board-members--name">Joanne Lipman</a>
                Chief Content Officer, USA Today Network and Editor-In-Chief, USA Today
            </li>
            <li class="home-board-members--item">
                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/home/gordon-crovitz-headshot.jpg" class="home-board-members--image">
                <a href="https://en.wikipedia.org/wiki/L._Gordon_Crovitz" class="home-board-members--name">Gordon Crovitz</a>
                partner in NextNews Ventures
            </li>
        </ul>
    </section>

    <section class="section home-cities" id="cities">
        <h2 class="home-cities--heading">Our Cities</h2>
        <a href="https://billypenn.com" class="home-cities--city home-cities--philadelphia">Philadelphia</a>
        <a href="https://theincline.com" class="home-cities--city home-cities--pittsburgh">Pittsburgh</a>
        <a href="https://www.denverite.com/" class="home-cities--city home-cities--denver">Denver</a>
    </section>

    <section class="section home-advertising-partners">
        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/home/advertising-partners.png">
        <h2>Advertising Partners</h2>
        <p>Spirited Media’s advertising partners include Comcast NBC Universal, the Philadelphia Eagles, the Knight Foundation, Beneficial Bank. PECO, SugarHouse Casino, the Philadelphia Foundation, VisitPhilly, the Shops at Liberty Place and many others.</p>
        <a href="http://billypenn.com/advertising/" class="button home-advertising-partners--button">Advertise with Us</a>
    </section>

    <section class="section home-press-coverage" id="press">
        <h2 class="home-press-coverage--heading">National Press Coverage</h2>
        <ul class="home-press-list">
        <?php
        foreach ( $press_links as $press ) :
            $press = (object) $press;
        ?>
            <li class="home-press-list-item"><a href="<?php echo esc_url( $press->url ); ?>" class="home-press-list-link"><?php echo $press->title; ?></a> <?php echo $press->date; ?>, <em class="home-press-list-publisher"><?php echo $press->publication; ?></em></li>
        <?php endforeach; ?>
        </ul>

    </section>

    <section class="home-contact" id="contact">
        <div class="section">
        <?php if ( sent_home_contact_form() ) : ?>
            <div class="home-contact--form-sent">
                <h2 class="home-contact--heading">Your message has been sent!</h2>
                <p>Thank you. We'll get back to you shortly.</p>
            </div>

        <?php else : ?>
            <h2 class="home-contact--heading">Get in Touch</h2>
            <form class="home-contact--form" action="<?php echo esc_url( trailingslashit( get_site_url() ) ); ?>#contact" method="post">
                <label for="contact-name" class="home-contact--field-label">Name</label>
                <input type="text" name="contact-name" id="contact-name" class="home-contact--field" required>

                <label for="contact-email" class="home-contact--field-label">Email</label>
                <input type="email" name="contact-email" id="contact-email" class="home-contact--field" required>

                <label for="contact-subject" class="home-contact--field-label">Subject</label>
                <input type="text" name="contact-subject" id="contact-subject" class="home-contact--field">

                <label for="contact-message" class="home-contact--field-label">Message</label>
                <textarea id="contact-message" name="contact-message" class="home-contact--field" rows="10" required></textarea>

                <label for="contact-check" class="home-contact--field-label hide">Leave the following field blank</label>
                <input type="text" name="contact-check" id="contact-check" class="home-contact--field hide" >

                <label for="contact-year" class="home-contact--field-label js-contact-year">What is the current year?</label>
                <input type="text" name="contact-year" pattern="\d{4}" id="contact-year" class="home-contact--field js-contact-year">

                <input type="submit" value="Send" class="home-contact--button">
            </form>

            <div class="home-contact--contact-details">
                <p><strong>Media Inquiries:</strong> <a href="mailto:<?php echo antispambot( 'contact@billypenn.com' ); ?>"><?php echo antispambot( 'contact@billypenn.com' ); ?></a></p>
                <p><?php echo svg_icon( 'map-marker' ); ?> Spirited Media<br>P.O. Box 577<br>Great Falls, VA 22066</p>
                <p><?php echo svg_icon( 'phone' ); ?> <a href="tel:2158218477">(215) 821-8477</a></p>
                <p><?php echo svg_icon( 'envelope' ); ?> <a href="mailto:<?php echo antispambot( 'contact@spiritedmedia.com' ); ?>"><?php echo antispambot( 'contact@spiritedmedia.com' ); ?></a></p>
            </div>
            <?php endif; ?>

        </div>
    </section>

<?php get_footer(); ?>
