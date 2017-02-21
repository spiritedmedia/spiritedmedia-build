<?php

namespace Pedestal\Widgets;

use Timber\Timber;

use \Pedestal\Registrations\Post_Types\Types;

use \Pedestal\Posts\Post;

use \Pedestal\Objects\Stream;

class Recent_Content_Widget extends \WP_Widget {

    public function __construct() {
        $widget_options = [ 'description' => esc_html( 'The most recent stories or articles.' ) ];
        parent::__construct( 'pedestal-recent-content',
            esc_html( 'Recent Content' ),
            $widget_options
        );
    }

    public function widget( $args, $instance ) {

        $obj = Post::get_by_post_id( get_queried_object_id() );

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
            case 'editorial':
                $post_types = Types::get_editorial_post_types();
                break;
        }

        if ( empty( $post_types ) ) {
            return;
        }

        $stream = new Stream( [
            'posts_per_page'      => $instance['number'],
            'paged'               => 1,
            'no_found_rows'       => true,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'post_type'           => $post_types,
        ] );
        $items = $stream->get_stream();

        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'];
            if ( ! empty( $instance['title_link'] ) ) {
                echo '<a href="' . esc_url( $instance['title_link'] ) . '">';
            }
            echo esc_html( $instance['title'] );
            if ( ! empty( $instance['title_link'] ) ) {
                echo '</a>';
            }
            echo $args['after_title'];
        }

        $context = Timber::get_context();
        $context['items'] = $items;
        $context['current_item'] = $obj;
        $context['show_thumbs'] = (bool) $instance['show_thumbs'];
        Timber::render( 'widgets/recent-content.twig', $context );

        echo $args['after_widget'];

    }

    public function form( $instance ) {

        $instance = wp_parse_args( $instance, [
            'title'       => esc_html( PEDESTAL_BLOG_NAME . ' Originals' ),
            'title_link'  => '',
            'number'      => 10,
            'show_thumbs' => true,
            'type'        => 'editorial',
        ] );

        $types = [
            'stories'    => 'Stories',
            'articles'   => 'Articles',
            'factchecks' => 'Factchecks',
            'editorial'  => 'Editorial Content',
        ];

        // Escape field ids and names
        $field_ids = $field_names = $instance;
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
                <?php checked( (bool) $instance['show_thumbs'], true ) ?>
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
