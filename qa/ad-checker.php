<?php
use Sunra\PhpSimple\HtmlDomParser;
include 'include.php';

$links_to_check = [
    [
        'name' => 'Billy Penn Homepage',
        'url' => 'https://billypenn.com/',
        'expected' => 13,
    ],
    [
        'name' => 'Billy Penn Article',
        'url' => 'https://billypenn.com/2017/09/26/we-knew-nothing-about-donuts-and-other-stories-in-the-new-federal-donuts-book/',
        'expected' => 3,
    ],
    [
        'name' => 'Billy Penn Story',
        'url' => 'https://billypenn.com/stories/septa-key/',
        'expected' => 12,
    ],
    [
        'name' => 'Billy Penn Who\'s Next',
        'url' => 'https://billypenn.com/2017/03/01/whos-next-bartenders-15-young-drink-maestros-elevating-phillys-booze-game/',
        'expected' => 1,
    ],
    [
        'name' => 'Billy Penn Fact Check',
        'url' => 'https://billypenn.com/2017/09/01/why-the-black-lives-matter-protest-outside-a-philly-police-officers-home-was-not-illegal-occupation/',
        'expected' => 1,
    ],
    [
        'name' => 'Billy Penn Event',
        'url' => 'https://billypenn.com/2017/03/10/drink-local-stouts-instead-of-guinness/',
        'expected' => 3,
    ],
    [
        'name' => 'Billy Penn Embed',
        'url' => 'https://billypenn.com/2017/09/03/heres-what-it-was-like-to-check-in-at-phl-airport-in-the-1970s/',
        'expected' => 3,
    ],
];

?>
<style>
.pass,
.pass a {
    color: #999;
}

.fail,
.fail a {
    color: red;
}
</style>

<p>This series of tests counts the number of DFP ad positions for each test and compares it to the expected number
of ad positions.</p>

<p>We also check that the ad script (dfp-load.js) is loaded.</p>

<p>There should be <strong><?php echo count( $links_to_check ); ?> total checks</strong> below. If there are not, then report to the product team.</p>

<ol>
<?php
foreach ( $links_to_check as $link ) {
    $html = HtmlDomParser::file_get_html( $link['url'] );
    $script_found = count( $html->find( 'script[src*=dfp-load.js]' ) );
    $found_ads = count( $html->find( '.js-dfp' ) );
    if ( $found_ads === $link['expected'] && 1 === $script_found ) {
        echo '<li class="pass">PASS: <a href="' . $link['url'] . '" target="_blank">' . $link['name'] . '</a></li>';
    } else {
        echo '<li class="fail">FAIL: <a href="' . $link['url'] . '" target="_blank">' . $link['name'] . '</a> Found ' . $found_ads . ' ads out of ' . $link['expected'] . ' expected! dfp-load.js found: ' . $script_found . '</li>';
    }
}
?>
</ol>
