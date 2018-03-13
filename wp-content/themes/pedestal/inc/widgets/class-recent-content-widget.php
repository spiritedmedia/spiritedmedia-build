<?php

namespace Pedestal\Widgets;

use Timber\Timber;
use \Pedestal\Registrations\Post_Types\Types;
use \Pedestal\Posts\Post;

class Recent_Content_Widget extends \WP_Widget {

    public function __construct() {
        $widget_options = [
            'description' => esc_html( 'The most recent stories or articles.' ),
        ];
        parent::__construct( 'pedestal-recent-content',
            esc_html( 'Recent Content' ),
            $widget_options
        );
    }

    public function widget( $args, $instance ) {
        $post_types = [];
        switch ( $instance['type'] ) {
            case 'stories':
                $post_types[] = 'pedestal_story';
                break;
            case 'articles':
                $post_types[] = 'pedestal_article';
                break;
            case 'factchecks':
                $post_types[] = 'pedestal_factcheck';
                break;
            case 'original':
                $post_types = Types::get_original_post_types();
                break;
        }

        if ( empty( $post_types ) ) {
            return;
        }

        $posts = new \WP_Query( [
            'posts_per_page'         => intval( $instance['number'] ),
            'paged'                  => 1,
            'post_status'            => 'publish',
            'ignore_sticky_posts'    => true,
            'post_type'              => $post_types,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ] );

        if ( empty( $posts->posts ) ) {
            return false;
        }

        $items = '';
        foreach ( $posts->posts as $index => $post ) {
            $ped_post = Post::get( $post );
            if ( ! Types::is_post( $ped_post ) ) {
                continue;
            }
            $item_context = [
                'title'     => $ped_post->get_the_title(),
                'permalink' => $ped_post->get_the_permalink(),
                'type'      => $instance['type'],
                'thumbnail' => false,
            ];
            if ( $instance['show_thumbs'] ) {
                $feat_image = $ped_post->get_featured_image_html( 48, [
                    'class' => 'o-media__img recent-content-widget__thumbnail',
                    'sizes' => '48px',
                    'srcset' => [
                        'ratio'  => 1,
                        'widths' => 48,
                    ],
                ] );

                // If no featured image, use a placeholder graphic
                if ( ! $feat_image ) {
                    $feat_image = '<i class="o-media__img recent-content-widget__placeholder"></i>';
                }
                $item_context['thumbnail'] = $feat_image;
            }
            ob_start();
                Timber::render( 'widgets/recent-content-item.twig', $item_context );
            $items .= ob_get_clean();
        }

        $widget_context = [
            'before_widget' => $args['before_widget'],
            'after_widget'  => $args['after_widget'],
            'before_title'  => $args['before_title'],
            'after_title'   => $args['after_title'],
            'title'         => esc_html( $instance['title'] ),
            'title_link'    => esc_url( $instance['title_link'] ),
            'items'         => $items,
        ];
        Timber::render( 'widgets/recent-content.twig', $widget_context );
    }

    public function form( $instance ) {

        $instance = wp_parse_args( $instance, [
            'title'       => esc_html( PEDESTAL_BLOG_NAME . ' Originals' ),
            'title_link'  => '',
            'number'      => 10,
            'show_thumbs' => true,
            'type'        => 'original',
        ] );

        $types = [
            'stories'    => 'Stories',
            'articles'   => 'Articles',
            'factchecks' => 'Factchecks',
            'original'   => 'Original Content',
        ];

        // Escape field ids and names
        $field_ids = $instance;
        $field_names = $instance;
        array_walk( $field_ids, function( &$v, $k ) {
            $v = $this->get_field_id( $k );
        } );
        array_walk( $field_names, function( &$v, $k ) {
            $v = $this->get_field_name( $k );
        } );

        ?>

        <p>
            <label for="<?php echo esc_attr( $field_ids['title'] ); ?>">
                <?php echo esc_html( 'Title' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $field_ids['title'] ); ?>"
                name="<?php echo esc_attr( $field_names['title'] ); ?>"
                type="text"
                value="<?php echo esc_attr( $instance['title'] ); ?>"
            />
        </p>

        <p>
            <label for="<?php echo esc_attr( $field_ids['title_link'] ); ?>">
                <?php echo esc_html( 'Title Link' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $field_ids['title_link'] ); ?>"
                name="<?php echo esc_attr( $field_names['title_link'] ); ?>"
                type="url"
                value="<?php echo esc_attr( $instance['title_link'] ); ?>"
            />
        </p>

        <p>
            <label for="<?php echo esc_attr( $field_ids['number'] ); ?>">
                <?php echo esc_html( 'Number of posts to show' ); ?>
            </label>
            <input id="<?php echo esc_attr( $field_ids['number'] ); ?>"
                name="<?php echo esc_attr( $field_names['number'] ); ?>"
                type="text"
                value="<?php echo (int) $instance['number']; ?>"
                size="3"
            />
        </p>

        <p>
            <label for="<?php echo esc_attr( $field_ids['show_thumbs'] ); ?>">
                <?php echo esc_html( 'Display thumbnails?' ); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $field_ids['show_thumbs'] ); ?>"
                name="<?php echo esc_attr( $field_names['show_thumbs'] ); ?>"
                type="checkbox"
                value="show_thumbs"
                <?php checked( (bool) $instance['show_thumbs'], true ); ?>
            />
        </p>

        <p>
            <label for="<?php echo esc_attr( $field_ids['type'] ); ?>">
                <?php echo esc_html( 'Type of content' ); ?>
            </label>
            <select name="<?php echo esc_attr( $field_names['type'] ); ?>" id="<?php echo esc_attr( $field_ids['type'] ); ?>">
                <?php foreach ( $types as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $instance['type'] ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </p>

        <?php

    }

    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['title_link'] = esc_url_raw( $new_instance['title_link'] );
        $instance['number'] = (int) $new_instance['number'];
        $instance['show_thumbs'] = $new_instance['show_thumbs'];
        $instance['type'] = sanitize_key( $new_instance['type'] );
        return $instance;
    }
}
