<?php
include 'include.php';
use Timber\Timber;

$context = Timber::get_context();
$context['primary_nav'] = [
    'news' => [
        'label' => 'News',
        'icon' => 'newspaper',
    ],
    'things-to-do' => [
        'label' => 'Things To Do',
        'icon' => 'calendar-alt',
    ],
    'food' => [
        'label' => 'Food',
        'icon' => 'utensils',
    ],
    'homes-cranes' => [
        'label' => 'Homes &amp; Cranes',
        'icon' => 'home',
    ],
    'government-politics' => [
        'label' => 'Government &amp; Politics',
        'icon' => 'university',
    ],
    'search' => [
        'label' => 'Search',
        'icon' => 'search',
    ],
];

$context['secondary_nav'] = [
    'newsletters' => [
        'label' => 'Newsletters',
        'icon' => 'envelope-open',
    ],
    'ask' => [
        'label' => 'Ask',
        'icon' => 'heart',
    ],
    'about' => [
        'label' => 'About',
        'icon' => 'comment',
    ],
    'support-us' => [
        'label' => 'Support Us',
        'icon' => 'star',
    ],
];
Timber::render( 'views/headers.twig', $context );
