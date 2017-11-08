<?php
include 'include.php';
use Pedestal\Icons;
use Timber\Timber;

$context = Timber::get_context();

$context['icon_list_html'] = '';
$icons = [
    'angle-left'     => 'Angle Left',
    'angle-right'    => 'Angle Right',
    'birthday-cake'  => 'Birthday Cake',
    'briefcase'      => 'Briefcase',
    'calendar'       => 'Calendar',
    'close'          => 'Close / X (multiplication sign)',
    'envelope-o'     => 'Envelope',
    'envelope-slant' => 'Envelope (Slanted)',
    'external-link'  => 'External Link',
    'facebook'       => 'Facebook',
    'info'           => 'Info',
    'instagram'      => 'Instagram',
    'level-down'     => 'Level Down',
    'linkedin'       => 'LinkedIn',
    'play'           => 'Play',
    'scribd'         => 'Scribd',
    'search'         => 'Search',
    'twitter'        => 'Twitter',
    'vine'           => 'Vine',
    'youtube'        => 'YouTube',
];
foreach ( $icons as $icon => $description ) {
    ob_start();
        Timber::render( 'views/partials/icons-example.twig', [
            'icon'        => Icons::get_icon( $icon, 'o-icon-text__icon' ),
            'icon_name'   => $icon,
            'description' => $description,
        ] );
    $context['icon_list_html'] .= ob_get_clean();
}

$context['facebook_icon_html'] = Icons::get_icon( 'facebook', 'o-icon-text__icon' );

Timber::render( 'views/icons.twig', $context );
