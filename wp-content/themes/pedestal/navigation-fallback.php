<?php
use Timber\Timber;
use Pedestal\Menus\Menus;

// Don't index this page
add_action( 'wp_head', 'wp_no_robots' );
add_filter( 'body_class', function( $class = [] ) {
    $class[] = 'page-navigation-fallback';
    return $class;
});

$context = Timber::get_context();

$menus = Menus::get_instance();
$context['primary_nav'] = $menus->get_menu_data( 'header-navigation' );
$context['secondary_nav'] = $menus->get_menu_data( 'header-secondary-navigation' );
$context['secondary_nav_mobile'] = $menus->get_menu_data( 'header-secondary-navigation-mobile' );

Timber::render( 'navigation-fallback.twig', $context );
