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
$author_image = '<img src="https://dummyimage.com/150.png">';
$date_time = date( PEDESTAL_DATE_FORMAT . ' &\m\i\d\d\o\t; ' . PEDESTAL_TIME_FORMAT );
$date_time = apply_filters( 'pedestal_get_post_date', $date_time );
$machine_time = date( 'c' );
$tweet_embed = '<div class="pedestal-embed pedestal-embed-twitter"><figure id="figure_3e4ea3ba" aria-labelledby="figcaption_3e4ea3ba"  class="c-figure  c-figure--embed  wp-caption    op-interactive" ><div  class=" c-figure__content-wrap  column-width"><blockquote class="twitter-tweet"><a href="https://twitter.com/PhillyMayor/status/879396798321872897">Tweet from @PhillyMayor</a></blockquote><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script></div></figure></div>';

/*
Avaialble Options for a stream item:

'post' => '',
'type' => '',
'stream_index' => '',
'featured_image' => '',
'thumbnail_image' => '',
'overline' => '',
'overline_url' => '',
'title' => '',
'permalink' => '',
'date_time' => '',
'machine_time' => '',
'description' => '',
'author_names' => '',
'author_image' => '',
'author_link' => '',
'source_name' => '',
'source_image' => '',
'source_link' => '',
'is_footer_compact' => false,
 */

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
        'description' => '<p>“If you’re stereotyping about people who are criminals...it’s that they’re insensitive to how their actions affect society,” said the artist best known for the Obama Hope image. “But people who make art are clearly trying to do something they think is pleasing to people, that creates healthy conversations.”</p>',// 308 characters
        'author_names' => $single_author,
        'author_image' => $author_image,
        'author_link' => $example_url,
    ],

    // External Link
    [
        'type' => 'link',
        'title' => 'An External Link to Another Site',
        'permalink' => $example_url,
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'source_name' => 'Name of Source',
        'source_image' => Icons::get_icon( 'external-link' ),
        'source_link' => $example_url,
    ],

	// External Link with Compact Footer
    [
        'type' => 'link',
        'title' => 'An External Link to Another Site with A Compact Footer',
        'permalink' => $example_url,
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'source_name' => 'Name of Source',
        'source_image' => Icons::get_icon( 'external-link' ),
        'source_link' => $example_url,
		'is_footer_compact' => true,
    ],

    // Instagram Embed
    [
        'type' => 'embed',
        'title' => 'An Instagram Embed',
        'thumbnail_image' => $thumbnail_image,
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
        'description' => '<p>A description of the embedded Tweet that appears below:</p>' . $tweet_embed,
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
        'description' => '',
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
        'thumbnail_image' => $thumbnail_image,
        'description' => '<p>A description of the YouTube video.</p>',
        'permalink' => 'https://billypenn.com/2017/07/22/for-some-reason-penn-live-produced-a-weird-kinda-tone-deaf-video-on-the-origins-of-philadelphia/',
        'date_time' => $date_time,
        'machine_time' => $machine_time,
        'source_name' => 'YouTube',
        'source_image' => Icons::get_icon( 'youtube' ),
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
        'source_name' => 'Sponsored by Sponsor\'s Name',
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
