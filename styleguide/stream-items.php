<?php
include 'include.php';
use Timber\Timber;
use Pedestal\Objects\Stream;
use Pedestal\Icons;

$context = Timber::get_context();

// Common placeholder elements for the styleguide
$example_url = 'http://example.com';
$featured_image = '<img src="https://dummyimage.com/1024x576.png">';
$thumbnail_image = '<img src="https://dummyimage.com/600.png">';
$single_author = '<a href="#" data-ga-category="Author" data-ga-label="Name|FirstName LastName">FirstName LastName</a>';
$author_image = Icons::get_logo( 'logo-icon', '', '28' );
$date_time = date( PEDESTAL_DATE_FORMAT . ' &\m\i\d\d\o\t; ' . PEDESTAL_TIME_FORMAT );
$date_time = apply_filters( 'pedestal_get_post_date', $date_time );
$machine_time = date( 'c' );
$tweet_embed = do_shortcode( '[twitter url="https://twitter.com/LIL_ICEBUNNY/status/901553987610136576"]' );
$youtube_embed = do_shortcode( '[youtube url="https://www.youtube.com/watch?v=I4agXcHLySs"]' );
$facebook_embed = do_shortcode( '[facebook url="https://www.facebook.com/genesis.breyerporridge.1/posts/1507100369379949?pnref=story" /]' );

ob_start();
?>

<div class="embed embed--instagram"><div class="embed__inner"><figure id="figure_5283ed25" aria-labelledby="figcaption_5283ed25"  class="c-figure  c-figure--script  wp-caption" ><div  class=" c-figure__content-wrap"><blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="7" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:8px;"><div style=" background:#F8F8F8; line-height:0; margin-top:40px; padding:50.0% 0; text-align:center; width:100%;"><div style=" background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAMAAAApWqozAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAMUExURczMzPf399fX1+bm5mzY9AMAAADiSURBVDjLvZXbEsMgCES5/P8/t9FuRVCRmU73JWlzosgSIIZURCjo/ad+EQJJB4Hv8BFt+IDpQoCx1wjOSBFhh2XssxEIYn3ulI/6MNReE07UIWJEv8UEOWDS88LY97kqyTliJKKtuYBbruAyVh5wOHiXmpi5we58Ek028czwyuQdLKPG1Bkb4NnM+VeAnfHqn1k4+GPT6uGQcvu2h2OVuIf/gWUFyy8OWEpdyZSa3aVCqpVoVvzZZ2VTnn2wU8qzVjDDetO90GSy9mVLqtgYSy231MxrY6I2gGqjrTY0L8fxCxfCBbhWrsYYAAAAAElFTkSuQmCC); display:block; height:44px; margin:0 auto -44px; position:relative; top:-22px; width:44px;"></div></div><p style=" margin:8px 0 0 0; padding:0 4px;"><a href="https://www.instagram.com/p/BZJ4no2HqrK/" style=" color:#000; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none; word-wrap:break-word;" target="_blank">Here I am before I participated in the Running of the Wieners today! I lost but mom said she was still proud of me!  #RunningoftheWieners #dachshund #minidachshund #sausagedog #wienerdog #doxiesonly #justdachshunds #dachshundofinstagram #dachshundoftheday #pawsomedachshunds #weenteam #ween #hotdog</a></p><p style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;">A post shared by Penguin (@pengweenie22) on <time style=" font-family:Arial,sans-serif; font-size:14px; line-height:17px;" datetime="2017-09-17T19:49:01+00:00">Sep 17, 2017 at 12:49pm PDT</time></p></div></blockquote><script async defer src="https://platform.instagram.com/en_US/embeds.js"></script></div><figcaption id="figcaption_5283ed25"  class="c-figure__text  wp-caption-text "><h1 class="c-figure__text__caption">At Sunday's Running of the Wieners, they're all winners</h1></figcaption></figure></div></div>

<?php
$instagram_embed = ob_get_clean();

$stream_items = [
    // Standard Article
    [
        'type' => 'article',
        'featured_image' => $featured_image,
        'title' => 'A Standard Article Stream Item',
        'permalink' => $example_url,
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'description' => '<p>An example of a stream item description.</p>',
        'show_meta_info' => true,
        'author_names' => $single_author,
        'author_image' => $author_image,
        'author_link' => $example_url,
    ],

    // Standard Article with Overline
    [
        'type' => 'article',
        'featured_image' => $featured_image,
        'overline' => 'An Overline',
        'overline_url' => $example_url,
        'title' => 'A Standard Article Stream Item with Overline',
        'permalink' => $example_url,
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'description' => '<p>An example of a stream item description.</p>',
        'show_meta_info' => true,
        'author_names' => $single_author,
        'author_image' => $author_image,
        'author_link' => $example_url,
    ],

    // Standard Article with Two Authors
    [
        'type' => 'article',
        'featured_image' => $featured_image,
        'overline' => 'An Overline',
        'overline_url' => $example_url,
        'title' => 'A Standard Article Stream Item with Two Authors',
        'permalink' => $example_url,
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'description' => '<p>An example of a stream item with multiple authors. We show a site icon as the author image.</p>',
        'show_meta_info' => true,
        'author_names' => $single_author . ' and ' . $single_author,
        'author_image' => Icons::get_logo( 'logo-icon', '', 40 ),
        'author_link' => $example_url,
    ],

    // Standard Article without a Featured Image
    [
        'type' => 'article',
        'title' => 'A Standard Article Stream Item Without a Featured Image',
        'permalink' => $example_url,
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'description' => '<p>I doubt this would ever happen but here is what it looks like.</p>',
        'show_meta_info' => true,
        'author_names' => $single_author,
        'author_image' => $author_image,
        'author_link' => $example_url,
    ],

    // Standard Article with Longest Title with Longest Overline
    [
        'type' => 'article',
        'featured_image' => $featured_image,
        'overline' => 'Rittenhouse Square wall-sitting ban', // 35 characters
        'overline_url' => 'https://billypenn.com/?p=62601',
        'title' => 'The Amtrak 188 victims: Justin Zemser, Jim Gaines, Rachel Jacobs, Abid Gilani, Derrick Griffith, Bob Gildersleeve, Giuseppe Piras and Laura Finamore', // 148 characters
        'permalink' => 'https://billypenn.com/?p=8998',
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'description' => '<p>This is the longest article title in our database at 148 characters along with the longest overline at 35 characters.</p>',
        'show_meta_info' => true,
        'author_names' => $single_author,
        'author_image' => $author_image,
        'author_link' => $example_url,
    ],

    // Standard Article with Longest Description
    [
        'type' => 'article',
        'featured_image' => $featured_image,
        'title' => 'The longest description in our database is 308 Characters',
        'permalink' => 'https://billypenn.com/?p=8998',
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'description' => '<p>“If you’re stereotyping about people who are criminals...it’s that they’re insensitive to how their actions affect society,” said the artist best known for the Obama Hope image. “But people who make art are clearly trying to do something they think is pleasing to people, that creates healthy conversations.”</p>', // 308 characters
        'show_meta_info' => true,
        'author_names' => $single_author,
        'author_image' => $author_image,
        'author_link' => $example_url,
    ],

    // Standard Factcheck
    [
        'type' => 'factcheck',
        'thumbnail_image' => $thumbnail_image,
        'title' => 'A Standard Factcheck Stream Item',
        'permalink' => 'https://billypenn.com/?p=8998',
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'description' => '<p>Factchecks are the journalist\'s bread and butter.</p>',
        'show_meta_info' => true,
        'author_names' => $single_author,
        'author_image' => $author_image,
        'author_link' => $example_url,
    ],

    // External Link
    [
        'type' => 'link',
        'title' => 'An External Link to Another Site',
        'permalink' => $example_url,
        'show_meta_info' => true,
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'source_name' => 'Name of Source',
        'source_image' => Icons::get_icon( 'external-link' ),
        'source_link' => $example_url,
    ],

    // Event
    [
        'type'        => 'event',
        'title'       => 'An Event Stream Item',
        'permalink'   => 'https://example.com',
        'show_header' => true,
        'what'        => 'What',
        'where'       => 'Where',
        'when'        => 'When',
        'cost'        => 'Cost',
        'cta_link'    => 'https://example.com',
        'cta_label'   => 'Label',
        'cta_source'  => 'Source',
        'content'     => '',
    ],

    // Instagram Embed
    [
        'type' => 'embed',
        'title' => 'An Instagram Embed',
        'description' => 'A description of the embedded Instagram post that appears below.',
        'embed_html' => $instagram_embed,
        'permalink' => $example_url,
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'source_name' => 'Instagram',
        'source_image' => Icons::get_icon( 'instagram' ),
        'source_link' => $example_url,
    ],

    // Twitter Embed
    [
        'type' => 'embed',
        'title' => 'A Twitter Embed',
        'description' => 'A description of the embedded Twitter post that appears below.',
        'embed_html' => $tweet_embed,
        'permalink' => $example_url,
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'source_name' => 'Twitter',
        'source_image' => Icons::get_icon( 'twitter' ),
        'source_link' => $example_url,
    ],

    // Facebook Embed
    [
        'type' => 'embed',
        'title' => 'A Facebook Embed',
        'description' => 'A description of the embedded Facebook post that appears below.',
        'embed_html' => $facebook_embed,
        'permalink' => $example_url,
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'source_name' => 'Facebook',
        'source_image' => Icons::get_icon( 'facebook' ),
        'source_link' => $example_url,
    ],

    // YouTube Embed
    [
        'type' => 'embed',
        'title' => 'A YouTube Embed',
        'description' => 'A description of the embedded YouTube video that appears below.',
        'embed_html' => $youtube_embed,
        'permalink' => 'https://billypenn.com/2017/07/22/for-some-reason-penn-live-produced-a-weird-kinda-tone-deaf-video-on-the-origins-of-philadelphia/',
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'show_meta_info' => true,
        'source_name' => 'Mike Patton',
        'source_link' => 'https://www.youtube.com/watch?v=5as_X9N6Ee0',
    ],

    // Sponsored Stream Item
    [
        'type' => 'sponsored',
        '__context' => 'standard',
        'title' => 'A Sponsored Stream Item',
        'thumbnail_image' => $thumbnail_image,
        'overline' => 'Advertisement',
        'permalink' => $example_url,
        'source_name' => 'Sponsor\'s Name',
        'source_image' => Icons::get_icon( 'external-link' ),
        'source_link' => $example_url,
    ],
];

$stream = new Stream;
$context['stream'] = '';
foreach ( $stream_items as $index => $item_context ) {
    $item_context['stream_index'] = $index;
    $context['stream'] .= $stream->get_the_stream_item( $item_context );
}
Timber::render( 'views/stream-items.twig', $context );
