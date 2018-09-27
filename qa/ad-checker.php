<?php
use Sunra\PhpSimple\HtmlDomParser;
include 'include.php';

$file_get_html_context = stream_context_create( [
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ],
] );


$blog_id = get_current_blog_id();
$links_to_check = [];
switch ( $blog_id ) :
    case 2:
        $links_to_check = [
            [
                'name'     => 'Billy Penn Homepage',
                'path'     => '/',
                'expected' => 2,
            ],
            [
                'name'     => 'Billy Penn Article',
                'path'     => '/2017/09/26/we-knew-nothing-about-donuts-and-other-stories-in-the-new-federal-donuts-book/',
                'expected' => 4,
            ],
            [
                'name'     => 'Billy Penn Story',
                'path'     => '/stories/septa-key/',
                'expected' => 2,
            ],
            [
                'name'     => 'Billy Penn Who\'s Next',
                'path'     => '/2017/03/01/whos-next-bartenders-15-young-drink-maestros-elevating-phillys-booze-game/',
                'expected' => 0,
            ],
            [
                'name'     => 'Billy Penn Fact Check',
                'path'     => '/2017/09/01/why-the-black-lives-matter-protest-outside-a-philly-police-officers-home-was-not-illegal-occupation/',
                'expected' => 1,
            ],
            [
                'name'     => 'Billy Penn Event',
                'path'     => '/2017/03/10/drink-local-stouts-instead-of-guinness/',
                'expected' => 1,
            ],
            [
                'name'     => 'Billy Penn Embed',
                'path'     => '/2017/09/03/heres-what-it-was-like-to-check-in-at-phl-airport-in-the-1970s/',
                'expected' => 1,
            ],
        ];
        break;

    case 3:
        $links_to_check = [
            [
                'name'     => 'The Incline Homepage',
                'path'     => '/',
                'expected' => 2,
            ],
        ];
        break;

    case 4:
        $links_to_check = [
            [
                'name'     => 'Denverite Homepage',
                'path'     => '/',
                'expected' => 2,
            ],
            [
                'name'     => 'Denverite Article',
                'path'     => '/2017/12/18/denver-council-delays-consideration-massive-flood-control-project/',
                'expected' => 2,
            ],
        ];
        break;
endswitch;
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
// Grab the root domain name of the site for the current environment
$root = pedestal_get_root_url( $blog_id );

foreach ( $links_to_check as $link ) {
    $url              = $root . $link['path'];
    $use_include_path = false;
    $html             = HtmlDomParser::file_get_html( $url, $use_include_path, $file_get_html_context );
    $script_found     = count( $html->find( 'script[src*=dfp-load.js],script[src*=dfp-load-patch.js]' ) );
    $found_ads        = count( $html->find( '.js-dfp' ) );
    if ( $found_ads === $link['expected'] && 1 === $script_found ) {
        echo '<li class="pass">PASS: <a href="' . $url . '" target="_blank">' . $link['name'] . '</a></li>';
    } else {
        echo '<li class="fail">FAIL: <a href="' . $url . '" target="_blank">' . $link['name'] . '</a> Found ' . $found_ads . ' ads out of ' . $link['expected'] . ' expected! dfp-load.js found: ' . $script_found . '</li>';
    }
}
?>
</ol>
