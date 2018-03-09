<?php
include 'include.php';
use Pedestal\Icons;
use Timber\Timber;

$context = Timber::get_context();

$context['icon_list_html'] = '';
$icons = [
    'angle-left' => [
        'label' => 'Angle Left',
        'usage' => [],
    ],
    'angle-right' => [
        'label' => 'Angle Right',
        'usage' => [],
    ],
    'birthday-cake' => [
        'label' => 'Birthday Cake',
        'usage' => [
            "Who's Next single posts",
        ],
    ],
    'briefcase' => [
        'label' => 'Briefcase',
        'usage' => [
            "Who's Next single posts",
        ],
    ],
    'calendar' => [
        'label' => 'Calendar',
        'usage' => [],
    ],
    'close' => [
        'label' => 'Close / X (multiplication sign)',
        'usage' => [
            'Signup daily modal',
            'Search form',
        ],
    ],
    'envelope-o' => [
        'label' => 'Envelope',
        'usage' => [
            'User card (author profile + author grid + featured contributor bio)',
            'Email share button',
        ],
    ],
    'envelope-slant' => [
        'label' => 'Envelope (Slanted)',
        'usage' => [
            'Signup daily component',
            'Site header newsletter CTA',
        ],
    ],
    'external-link'  => [
        'label' => 'External Link',
        'usage' => [
            'Sponsored stream items',
            'Embeds without source logo',
            'Event more info link',
        ],
    ],
    'facebook'       => [
        'label' => 'Facebook',
        'usage' => [
            'Embeds in stream and single post templates',
            'Social share button',
            'Site footer',
        ],
    ],
    'info' => [
        'label' => 'Info',
        'usage' => [],
    ],
    'instagram' => [
        'label' => 'Instagram',
        'usage' => [
            'Embeds in stream and single post templates',
            "Who's Next single posts",
            'Site footer',
        ],
    ],
    'level-down' => [
        'label' => 'Level Down',
        'usage' => [
            'Footnotes',
        ],
    ],
    'linkedin' => [
        'label' => 'LinkedIn',
        'usage' => [
            "Who's Next single posts",
        ],
    ],
    'lock' => [
        'label' => 'Lock',
        'usage' => [
            'Donation form',
        ],
    ],
    'play' => [
        'label' => 'Play',
        'usage' => [
            'YouTube placeholder',
        ],
    ],
    'scribd' => [
        'label' => 'Scribd',
        'usage' => [
            'Embeds in stream and single post templates',
        ],
    ],
    'search' => [
        'label' => 'Search',
        'usage' => [
            'Search form submit',
        ],
    ],
    'soundcloud' => [
        'label' => 'Soundcloud',
        'usage' => [
            'Embeds in stream and single post templates',
        ],
    ],
    'twitter' => [
        'label' => 'Twitter',
        'usage' => [
            'Embeds in stream and single post templates',
            "Who's Next single posts",
            'User card (author profile + author grid + featured contributor bio)',
            'Social share button',
            'Site footer',
        ],
    ],
    'vine' => [
        'label' => 'Vine',
        'usage' => [
            'Embeds in stream and single post templates',
        ],
    ],
    'youtube' => [
        'label' => 'YouTube',
        'usage' => [
            'Embeds in stream and single post templates',
        ],
    ],
];
foreach ( $icons as $icon => $details ) {
    ob_start();
        Timber::render( 'views/partials/icons-example.twig', [
            'icon'      => Icons::get_icon( $icon, 'o-icon-text__icon' ),
            'icon_name' => $icon,
            'label'     => $details['label'],
            'usage'     => $details['usage'],
        ] );
    $context['icon_list_html'] .= ob_get_clean();
}

$context['facebook_icon_html'] = Icons::get_icon( 'facebook', 'o-icon-text__icon' );

Timber::render( 'views/icons.twig', $context );
