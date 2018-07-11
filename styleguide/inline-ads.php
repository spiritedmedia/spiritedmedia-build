<?php
include 'include.php';
use Timber\Timber;
use Sunra\PhpSimple\HtmlDomParser;
use Pedestal\Adverts;

$context = Timber::get_context();

function inject_ads( $html = '' ) {
    $adverts = Adverts::get_instance();
    return $adverts->inject_inline_ads( $html );
}

$examples = [
    // Title => Name of file
    'Short Paragraphs'    => 'short-paragraphs.php',
    'Long Paragraphs'     => 'long-paragraphs.php',
    'One Line Paragraphs' => 'one-line-paragraphs.php',
    'Blockquotes'         => 'blockquotes.php',
    'Events'              => 'events.php',
];

if ( ! empty( $_GET['example'] ) ) {
    foreach ( $examples as $title => $file ) {
        if ( sanitize_title( $title ) == $_GET['example'] ) {
            $path = dirname( __FILE__ ) . '/inline-ads/' . sanitize_file_name( $file );
            ob_start();
            include( $path );
            $html = ob_get_clean();
            $html = apply_filters( 'the_content', $html );
            $html = inject_ads( $html );
            $context['title'] = $title;
            $context['example_content'] = $html;
            Timber::render( 'views/inline-ads-example.twig', $context );
            die();
        }
    }
}

foreach ( $examples as $title => $file ) {
    $context['examples'][] = [
        'title' => $title,
        'slug'  => sanitize_title( $title ),
    ];
}

Timber::render( 'views/inline-ads.twig', $context );
