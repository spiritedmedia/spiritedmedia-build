<?php
use Timber\Timber;
use Pedestal\Adverts;
use Pedestal\Objects\Stream;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Email\Newsletter_Emails;

$item = Post::get( get_the_ID() );
$context = Timber::get_context();

if ( Types::is_post( $item ) ) :

    $context['cluster'] = $item->get_primary_story();
    $context['featured_image_sizes'] = [
        '(max-width: 640px) 95vw',
        '(max-width: 1024px) 97vw',
        '(min-width: 1025px) 676px',
        '96vw',
    ];
    $context['featured_image_srcset'] = [
        'ratio'  => 16 / 9,
        'widths' => [ 320, 480, 640, 676, 700, 800, 1024 ],
    ];
    if ( is_active_sidebar( 'sidebar-entity' ) ) {
        $context['sidebar'] = '<li class="widget widget_pedestal_dfp_rail_right">' . Adverts::render_sidebar_ad_unit() . '</li>';
        $context['sidebar'] .= Timber::get_widgets( 'sidebar-entity' );
    }

    $context['newsletter_signup_prompt'] = Newsletter_Emails::get_signup_form();

    $adverts = new Adverts;
    $sponsored_item = $adverts->get_the_sponsored_item();
    if ( $sponsored_item ) {
        $context['sponsored_item'] = $sponsored_item;
    }

    // Handle the footer recirculation stream
    if ( Types::is_original_content( $item ) ) {
        $query_args = [
            'posts_per_page'         => 20,
            'paged'                  => 1,
            'post_status'            => 'publish',
            'ignore_sticky_posts'    => true,
            'post_type'              => Types::get_original_post_types(),
            'post__not_in'           => [ $item->get_id() ],
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];
        $query = new \WP_Query( $query_args );
        $stream = new Stream( $query );
        $context['recirc_stream'] = $stream->get_the_stream();

        ob_start();
        Timber::render( 'partials/pagination-load-more.twig', [
            'url'  => site_url( '/originals/page/2/' ),
            'text' => 'More ' . PEDESTAL_BLOG_NAME_SANS_THE,
        ] );
        $context['recirc_pagination'] = ob_get_clean();
    }

    // Load Post context after everything else so it takes priority
    $context = $item->get_context( $context );

    // Do some post-processing
    if ( isset( $context['content_classes'] ) && is_array( $context['content_classes'] ) ) {
        $context['content_classes'] = implode( ' ', $context['content_classes'] );
    }

endif;

// Special exception for Denverite featured images pre-migration to Bridge
// See https://github.com/spiritedmedia/spiritedmedia/issues/2667
if ( 4 === get_current_blog_id() ) :

    $arbitrary_cutoff = date( 'U', strtotime( '2018-06-13' ) );
    $published_time = time();
    if ( Types::is_post( $item ) ) {
        $published_time = $item->get_post_date( 'U' );
    }

    if ( (int) $published_time < (int) $arbitrary_cutoff ) {
        unset( $context['featured_image'] );
    }

endif;

$context['item'] = $item;
Timber::render( 'single-entity.twig', $context );
