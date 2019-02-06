<?php
use Timber\Timber;
use Pedestal\Adverts;
use Pedestal\Audience\Conversion_Prompts;
use Pedestal\Objects\Stream;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;
use Pedestal\Email\Newsletter_Emails;

$post_id = get_the_ID();
$item    = Post::get( $post_id );
$context = Timber::get_context();

$templates = [];

if ( Types::is_post( $item ) ) :

    if ( $item->is_password_required() ) {
        $context['form_action'] = site_url( 'wp-login.php?action=postpass', 'login_post' );
        $context['input_id']    = 'password-form-' . $post_id;
        Timber::render( 'single-entity-protected.twig', $context );
        return;
    }

    $templates[] = 'single-' . $item->get_type() . '.twig';

    $context['cluster']               = $item->get_primary_story();
    $context['featured_image_sizes']  = [
        '(max-width: 640px) 95vw',
        '(max-width: 1024px) 97vw',
        '(min-width: 1025px) 676px',
        '96vw',
    ];
    $context['featured_image_srcset'] = [
        'ratio'  => 16 / 9,
        'widths' => [ 320, 480, 640, 676, 700, 800, 1024 ],
    ];

    $context['conversion_prompts'] = Conversion_Prompts::get_rendered_messages( 'entity', [
        'signup_source' => 'Post footer',
    ] );

    $context['entity_circulation_hook'] = 'Want some more?';

    $adverts        = new Adverts;
    $sponsored_item = $adverts->get_the_sponsored_item();
    if ( $sponsored_item ) {
        $context['sponsored_item'] = $sponsored_item;
    }

    // Handle the footer recirculation stream
    if ( Types::is_original_content( $item ) ) {
        $query_args               = [
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
        $query                    = new \WP_Query( $query_args );
        $stream                   = new Stream( $query );
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

    $context['rail_class'] = '';
    if ( empty( $context['sidebar'] ) ) {
        $context['rail_class'] .= 'is-sticky';
    }

endif;

// Special exception for Denverite featured images pre-migration to Bridge
// See https://github.com/spiritedmedia/spiritedmedia/issues/2667
if ( 'denverite' == PEDESTAL_THEME_NAME ) :

    $arbitrary_cutoff = date( 'U', strtotime( '2018-06-13' ) );
    $published_time   = time();
    if ( Types::is_post( $item ) ) {
        $published_time = $item->get_post_date( 'U' );
    }

    if ( (int) $published_time < (int) $arbitrary_cutoff ) {
        unset( $context['featured_image'] );
    }

endif;

if ( 'the-incline' == PEDESTAL_THEME_NAME && Types::is_post( $item ) ) {
    // Remove the "Want some more?" text from the entity circulation prompt
    // on the "Shooting at Tree of Life Synagogue" story
    //
    // This is a nasty hack but in the interest of time this seemed to be the
    // optimal route.
    //
    // @TODO Develop a more universal way of handling this sort of
    // customization
    $story = $item->get_primary_story();
    if ( Types::is_story( $story ) && 51076 === $story->get_id() ) {
        $context['entity_circulation_hook'] = '';
    }
}

$templates[] = 'single-entity.twig';

$context['item'] = $item;
Timber::render( $templates, $context );
