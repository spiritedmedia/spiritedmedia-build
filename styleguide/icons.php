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
    'at-symbol' => [
        'label' => 'At Symbol',
        'usage' => [
            'Email signup shortcode',
        ],
    ],
    'balance-scale-solid' => [
        'label' => 'Balance Scale',
        'usage' => [],
    ],
    'bars' => [
        'label' => 'Bars',
        'usage' => [],
    ],
    'birthday-cake' => [
        'label' => 'Birthday Cake',
        'usage' => [
            "Who's Next single posts",
        ],
    ],
    'bolt-solid' => [
        'label' => 'Bolt (Solid)',
        'usage' => [
            'Message spot (override variant)',
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
    'calendar-alt' => [
        'label' => 'Calendar Alt (Solid)',
        'usage' => [
            'Message spot',
        ],
    ],
    'calendar-alt-regular' => [
        'label' => 'Calendar Alt (Regular/Outline)',
        'usage' => [
            'Message spot',
        ],
    ],
    'caret-down' => [
        'label' => 'Caret (Down)',
        'usage' => [],
    ],
    'caret-up' => [
        'label' => 'Caret (Up)',
        'usage' => [],
    ],
    'check' => [
        'label' => 'Check',
        'usage' => [
            'Newsletter signup prompt',
            'Message spot',
        ],
    ],
    'chess' => [
        'label' => 'Chess',
        'useage' => [
            'The Incline nav',
        ],
    ],
    'close' => [
        'label' => 'Close / X (multiplication sign)',
        'usage' => [
            'Signup daily modal',
            'Search form',
        ],
    ],
    'coffee' => [
        'label' => 'Coffee',
        'usage' => [
            'Daily email signup widget (Denverite)',
            'Message spot',
        ],
    ],
    'comment' => [
        'label' => 'Comment',
        'usage' => [],
    ],
    'comment-dots' => [
        'label' => 'Comment Dots',
        'usage' => [
            'The Incline Nav',
        ],
    ],
    'comments' => [
        'label' => 'Comments',
        'usage' => [
            'Message spot',
        ],
    ],
    'dollar-sign' => [
        'label' => 'Dollar Sign',
        'usage' => [
            'Donate form',
        ],
    ],
    'envelope' => [
        'label' => 'Envelope',
        'usage' => [
            'Message spot',
        ],
    ],
    'envelope-o' => [
        'label' => 'Envelope (Outline)',
        'usage' => [
            'User card (author profile + author grid + featured contributor bio)',
            'Message spot',
        ],
    ],
    'envelope-open' => [
        'label' => 'Envelope (Open)',
        'usage' => [
            'Email share button',
        ],
    ],
    'envelope-open' => [
        'label' => 'Envelope (Open) v2',
        'usage' => [],
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
    'facebook' => [
        'label' => 'Facebook',
        'usage' => [
            'Embeds in stream and single post templates',
            'Site footer',
        ],
    ],
    'facebook-square' => [
        'label' => 'Facebook (Square)',
        'usage' => [
            'Social share button',
        ],
    ],
    'fast-forward' => [
        'label' => 'Fast Forward',
        'usage' => [
            'The Incline Nav',
        ],
    ],
    'fish-solid' => [
        'label' => 'Fish',
        'usage' => [],
    ],
    'gavel-solid' => [
        'label' => 'Gavel',
        'usage' => [],
    ],
    'hand-peace' => [
        'label' => 'Hand making a peace sign',
        'usage' => [
            'Denverite culture menu item icon',
        ],
    ],
    'hand-point-right' => [
        'label' => 'Hand pointing right',
        'usage' => [
            'Message spot',
        ],
    ],
    'hand-spock' => [
        'label' => 'The Hand of Spock',
        'usage' => [
            'Newsletter signup prompt (Denverite only)',
        ],
    ],
    'heart' => [
        'label' => 'Heart (Solid)',
        'usage' => [],
    ],
    'heart-regular' => [
        'label' => 'Heart (Regular/Outline)',
        'usage' => [],
    ],
    'home' => [
        'label' => 'Home',
        'usage' => [],
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
    'link' => [
        'label' => 'Link',
        'usage' => [
            'Message spot',
        ],
    ],
    'linkedin' => [
        'label' => 'LinkedIn',
        'usage' => [
            "Who's Next single posts",
        ],
    ],
    'list-ol' => [
        'label' => 'List (ordered)',
        'usage' => [
            'Message spot',
        ],
    ],
    'lock' => [
        'label' => 'Lock',
        'usage' => [
            'Donation form',
        ],
    ],
    'location-arrow' => [
        'label' => 'Location arrow',
        'usage' => [
            'Message spot',
        ],
    ],
    'newspaper' => [
        'label' => 'Newspaper',
        'usage' => [],
    ],
    'newspaper-solid' => [
        'label' => 'Newspaper (Solid)',
        'usage' => [],
    ],
    'paper-plane-solid' => [
        'label' => 'Paper Plane',
        'usage' => [],
    ],
    'phone-square' => [
        'label' => 'Phone (Square)',
        'usage' => [],
    ],
    'play' => [
        'label' => 'Play',
        'usage' => [
            'YouTube placeholder',
        ],
    ],
    'quidditch-solid' => [
        'label' => 'Quidditch',
        'usage' => [],
    ],
    'rocket' => [
        'label' => 'Rocket',
        'usage' => [
            'The Incline Nav',
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
    'star' => [
        'label' => 'Star',
        'usage' => [
            'Message spot',
            'Secondary nav menu',
        ],
    ],
    'subway-solid' => [
        'label' => 'Subway',
        'usage' => [],
    ],
    'sun' => [
        'label' => 'Sun',
        'usage' => [
            'The Incline Nav',
        ],
    ],
    'times-circle' => [
        'label' => 'Times Circle',
        'usage' => [
            'Modal close',
        ],
    ],
    'twitter' => [
        'label' => 'Twitter',
        'usage' => [
            'Embeds in stream and single post templates',
            "Who's Next single posts",
            'User card (author profile + author grid + featured contributor bio)',
            'Site footer',
        ],
    ],
    'twitter-square' => [
        'label' => 'Twitter (Square)',
        'usage' => [
            'Social share button',
        ],
    ],
    'university' => [
        'label' => 'University',
        'usage' => [],
    ],
    'utensils' => [
        'label' => 'Utensils',
        'usage' => [],
    ],
    'vine' => [
        'label' => 'Vine',
        'usage' => [
            'Embeds in stream and single post templates',
        ],
    ],
    'walking-solid' => [
        'label' => 'Walking',
        'usage' => [],
    ],
    'youtube' => [
        'label' => 'YouTube',
        'usage' => [
            'Embeds in stream and single post templates',
        ],
    ],
];
$context['icons'] = $icons;
$context['facebook_icon_html'] = Icons::get_icon( 'facebook', 'o-icon-text__icon' );

Timber::render( 'views/icons.twig', $context );
