<?php
include 'include.php';
use Timber\Timber;
use Pedestal\Icons;

// Common placeholder elements for the styleguide
$post_title = 'This is a long and important post heading';
$post_description = 'This is a really long and important description';
$image_url = 'https://dummyimage.com/1024x16:9.png';
$image_caption = 'This is an image caption for display under the featured image.';
$image_credit = 'Credit/Source';
$date_time = date( PEDESTAL_DATE_FORMAT . ' &\m\i\d\d\o\t; ' . PEDESTAL_TIME_FORMAT );
$date_time = apply_filters( 'pedestal_get_post_date', $date_time );
$machine_time = date( 'c' );

ob_start();
    Timber::render( 'views/partials/post-headers-single-author.twig', [] );
$single_author = ob_get_clean();

ob_start();
    Timber::render( 'views/partials/post-headers-single-author-image.twig', [] );
$single_author_image = ob_get_clean();

ob_start();
    Timber::render( 'views/partials/post-headers-two-authors.twig', [] );
$two_authors = ob_get_clean();
$two_authors_image = Icons::get_logo( 'logo-icon', 'c-meta-info__img__icon', 40 );

ob_start();
    Timber::render( 'views/partials/post-headers-three-authors.twig', [] );
$three_authors = ob_get_clean();

$post_headers = [
    // Single Author
    [
        'title'              => $post_title,
        'description'        => $post_description,
        'permalink'          => 'https://billypenn.com/2017/10/12/hack-the-menu-wm-mulherins-sons-on-a-50-budget/',
        'feat_image_url'     => $image_url,
        'feat_image_caption' => $image_caption,
        'feat_image_credit'  => $image_credit,
        'author'             => $single_author,
        'author_image'       => $single_author_image,
        'author_link'        => get_site_url() . '/about/',
        'author_count'       => 1,
        'date_time'          => $date_time,
        'machine_time'       => $machine_time,
    ],

    // Two Authors
    [
        'title'              => $post_title,
        'description'        => $post_description,
        'permalink'          => 'https://billypenn.com/2017/10/27/fear-and-loathing-and-1-margaritas-an-afternoon-at-the-center-city-applebees/',
        'feat_image_url'     => $image_url,
        'feat_image_caption' => $image_caption,
        'feat_image_credit'  => $image_credit,
        'author'             => $two_authors,
        'author_image'       => $two_authors_image,
        'author_link'        => get_site_url() . '/about/',
        'author_count'       => 2,
        'date_time'          => $date_time,
        'machine_time'       => $machine_time,
    ],

    // Three Authors
    [
        'title'              => $post_title,
        'description'        => $post_description,
        'permalink'          => 'https://billypenn.com/2017/03/14/winter-storm-stella-the-funniest-images-from-philadelphia-tv-news/',
        'feat_image_url'     => $image_url,
        'feat_image_caption' => $image_caption,
        'feat_image_credit'  => $image_credit,
        'author'             => $three_authors,
        'author_image'       => $two_authors_image,
        'author_link'        => get_site_url() . '/about/',
        'author_count'       => 3,
        'date_time'          => $date_time,
        'machine_time'       => $machine_time,
    ],

    // No Featured Image
    [
        'title'              => $post_title,
        'description'        => $post_description,
        'permalink'          => '',
        'author'             => $single_author,
        'author_image'       => $single_author_image,
        'author_link'        => get_site_url() . '/about/',
        'author_count'       => 1,
        'date_time'          => $date_time,
        'machine_time'       => $machine_time,
    ],
];

$context = Timber::get_context();
$context['post_headers_html'] = '';
foreach ( $post_headers as $post_header ) {
    ob_start();
        Timber::render( 'views/partials/post-headers-header.twig', $post_header );
    $context['post_headers_html'] .= ob_get_clean();
}

Timber::render( 'views/post-headers.twig', $context );
