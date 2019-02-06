<?php
use Timber\Timber;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

$item    = Post::get( get_the_ID() );
$context = Timber::get_context();

if ( Types::is_post( $item ) ) {

    if ( $item->is_password_required() ) {
        $context['form_action'] = site_url( 'wp-login.php?action=postpass', 'login_post' );
        $context['input_id']    = 'password-form-' . get_the_ID();
        Timber::render( 'single-entity-protected.twig', $context );
        return;
    }

    $context = $item->get_context( $context );

    // Do some post-processing
    if ( isset( $context['content_classes'] ) && is_array( $context['content_classes'] ) ) {
        $context['content_classes'] = implode( ' ', $context['content_classes'] );
    }
}

$context['item'] = $item;
Timber::render( [ 'single-page.twig', 'single.twig' ], $context );
