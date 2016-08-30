<?php

namespace Pedestal\Widgets;

use Timber\Timber;

use \Pedestal\Registrations\Post_Types\Types;

use \Pedestal\Posts\Post;

use \Pedestal\Objects\Stream;

class Recent_Content_Widget extends \WP_Widget {

    public function __construct() {
        parent::__construct( 'pedestal-recent-content', esc_html__( 'Recent Content', 'pedestal' ), [ 'description' => esc_html__( 'The most recent stories or articles.', 'pedestal' ) ] );
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
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        }

        $context = Timber::get_context();
        $context['items'] = $items;
        $context['current_item'] = $obj;
        Timber::render( 'widgets/recent-content.twig', $context );

        echo $args['after_widget'];

    }

    public function form( $instance ) {

        $instance = array_merge( [
            'title'      => esc_htmL__( PEDESTAL_BLOG_NAME . ' Originals', 'pedestal' ),
            'number'     => 10,
            'type'       => 'editorial',
        ], $instance );

        $types = [
            'stories'   => esc_html__( 'Stories', 'pedestal' ),
            'articles'  => esc_html__( 'Articles', 'pedestal' ),
            'editorial' => esc_html__( 'Editorial Content', 'pedestal' ),
        ];

        ?>

        <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'pedestal' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" /></p>

        <p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:', 'pedestal' ); ?></label>
        <input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo (int) $instance['number']; ?>" size="3" /></p>

        <p><label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Type of content:' ); ?></label>
        <select name="<?php echo $this->get_field_name( 'type' ); ?>" id="<?php echo $this->get_field_id( 'type' ); ?>">
            <?php foreach ( $types as $key => $label ) : ?>
            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $instance['type'] ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
        </p>

        <?php

    }

    public function update( $new_instance, $old_instance ) {
        $instance = [];
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['number'] = (int) $new_instance['number'];
        $instance['type'] = sanitize_key( $new_instance['type'] );
        return $instance;
    }
}
