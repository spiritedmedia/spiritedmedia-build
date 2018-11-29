<?php
use Pedestal\Subscribers;
use Timber\Timber;

include 'include.php';

add_action( 'wp_enqueue_scripts', function() {
    $in_footer = true;
    wp_enqueue_script(
        'pedestal-qa-subscriber-cookie-tool',
        PEDESTAL_DIST_DIRECTORY_URI . '/js/subscriber-cookie-tool.js',
        [ 'jquery' ],
        PEDESTAL_VERSION,
        $in_footer
    );
} );

$subscribers = Subscribers::get_instance();
$subscriber_data = $subscribers->get_subscriber_data( 'abc123' );
foreach ( $subscriber_data as $key => $val ) {
    $subscriber_data[ $key ] = [
        'name'  => $key,
        'value' => $val,
        'type'  => gettype( $val ),
    ];
}
$context = Timber::get_context();
$context['subscriber_data'] = $subscriber_data;
Timber::render( 'views/subscriber-cookie-tool.twig', $context );
