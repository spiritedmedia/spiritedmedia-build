<?php
include 'include.php';
use Timber\Timber;
use Pedestal\Icons;
use Pedestal\Email\{
    Newsletter_Emails,
    Follow_Updates
};
use Pedestal\Posts\Clusters\Story;
use Pedestal\Registrations\Post_Types\Types;

$cluster_id = false;
$cluster_prompt = '';
$stories = new \WP_Query( [
    'post_type'      => 'pedestal_story',
    'posts_per_page' => 15,
    'post_status'    => 'publish',
    'fields'         => 'ids',
] );
if ( ! empty( $stories->posts ) ) {
    foreach ( $stories->posts as $post_id ) {
        $story = Story::get( $post_id );
        if ( Types::is_story( $story ) ) {
            $cluster_id = $story->get_id();
            $cluster_prompt = Follow_Updates::get_signup_form( [], $cluster_id );
        }

        if ( ! empty( $cluster_prompt ) ) {
            break;
        }
    }
}

$shortcode_prompt = do_shortcode( '[pedestal-email-signup-form ga_category="newsletter-page" signup_source="Newsletter page" /]' );

$context = [
    'default_inline_prompt'   => Newsletter_Emails::get_signup_form(),

    'shortcode_url'           => get_site_url() . '/newsletter-signup/',
    'shortcode_inline_prompt' => '<div class="signup-email--shortcode">' . $shortcode_prompt . '</div>',

    'cluster_url'             => get_permalink( $cluster_id ),
    'cluster_prompt'          => '<div class="signup-email--cluster signup-email">' . $cluster_prompt . '</div>',
] + Timber::get_context();
Timber::render( 'views/signup-forms.twig', $context );
