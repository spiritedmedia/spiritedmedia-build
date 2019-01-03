<?php
use Pedestal\Audience\Audience;
use Timber\Timber;

include 'include.php';

add_action( 'wp_enqueue_scripts', function() {
    $in_footer = true;
    wp_enqueue_script(
        'pedestal-qa-contact-cookie-tool',
        PEDESTAL_DIST_DIRECTORY_URI . '/js/contact-cookie-tool.js',
        [ 'jquery' ],
        PEDESTAL_VERSION,
        $in_footer
    );
} );

$audience = Audience::get_instance();
$contact_data = $audience->get_contact_data( 'abc123' );
foreach ( $contact_data as $key => $val ) {
    $contact_data[ $key ] = [
        'name'  => $key,
        'value' => $val,
        'type'  => gettype( $val ),
    ];
}
$context = Timber::get_context();
$context['contact_data'] = $contact_data;
Timber::render( 'views/contact-cookie-tool.twig', $context );
