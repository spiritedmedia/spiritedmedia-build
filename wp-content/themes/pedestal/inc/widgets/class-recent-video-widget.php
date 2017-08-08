<?php

namespace Pedestal\Widgets;

use Timber\Timber;
use Pedestal\Utils\Utils;
use Pedestal\Objects\{
    Figure,
    YouTube
};
use Pedestal\Posts\Entities\Embed;

class Recent_Video_Widget extends \WP_Widget {

    /**
     * YouTube class instance
     *
     * @var YouTube
     */
    public $youtube;

    public function __construct() {
        $this->youtube = new YouTube;
        $widget_options = [
			'description' => 'Display a recent video from YouTube.',
		];
        parent::__construct( 'pedestal-recent-video',
            esc_html( 'Recent Video' ),
            $widget_options
        );
    }

    public function widget( $args, $instance ) {
        $url = $instance['video_url'];

        if ( ! $url ) {
            // Because the `search` endpoint only includes a truncated video
            // description, we need to perform a new API request to get the full
            // description.
            $latest_video_id = $this->youtube->get_latest_site_channel_video_id();
            $url = YouTube::get_url_from_id( $latest_video_id );
        }
        $data = $this->youtube->get_single_video_data( $url );

        if ( ! $data || empty( $data->snippet ) ) {
            return;
        }

        $snippet = $data->snippet;

        // @codingStandardsIgnoreStart
        if (
            empty( $snippet->title )
            || empty( $snippet->publishedAt )
            || empty( $snippet->channelTitle )
        ) {
            return false;
        }

        $datetime = $snippet->publishedAt;
        $channel_title = $snippet->channelTitle;
        $description = rtrim( $snippet->description );
        // @codingStandardsIgnoreEnd

        $context = [
            'title'       => esc_html( $snippet->title ),
            'url'         => $url,
            'datetime'    => $datetime,
            'date'        => esc_html( date( 'd M Y', strtotime( $datetime ) ) ),
            'author'      => '',
            'description' => '',
        ];

        if ( $instance['video_url'] && $instance['display_author'] ) {
            $context['author'] = esc_html( $channel_title );
        }

        if ( $instance['display_description'] && ! empty( $description ) ) {
            $context['description'] = esc_html( Utils::str_limit_sentence( $description, 200 ) );
            $context['description'] = make_clickable( $context['description'] );
        }

        ob_start();
        Timber::render( 'widgets/recent-video-caption.twig', $context );
        $caption_html = ob_get_clean();

        $embed_url = YouTube::get_embeddable_url( $url );
        if ( empty( $embed_url ) ) {
            return false;
        }

        $embed_url = esc_url( $embed_url );
        $content = sprintf( '<iframe src="%s" width="640" height="360" frameborder="0"></iframe>', $embed_url );
        $figure = new Figure( 'embed', $content, [
            'caption_html'       => $caption_html,
            'classes'            => 'c-video-widget',
            'figcaption_classes' => 'c-video-widget__caption',
        ] );

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

        echo $figure->get_html();

        echo $args['after_widget'];

    }

    public function form( $instance ) {

        $instance = wp_parse_args( $instance, [
            'title'               => PEDESTAL_BLOG_NAME . ' Videos',
            'title_link'          => '',
            'video_url'           => '',
            'display_description' => true,
            'display_author'      => true,
        ] );

        // Hide the Display Author checkbox field and its label unless a specfic
        // video URL is provided
        $display_display_author_field = empty( $instance['video_url'] ) ? 'style="display: none;"' : '';

        ?>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <strong>Widget Title (Optional)</strong>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                type="text"
                value="<?php echo esc_attr( $instance['title'] ); ?>"
            />
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title_link' ) ); ?>">
                <strong>Widget Title Link (Optional)</strong>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title_link' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'title_link' ) ); ?>"
                type="url"
                value="<?php echo esc_attr( $instance['title_link'] ); ?>"
            />
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'display_description' ) ); ?>">
                <strong>Display Description?</strong>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display_description' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'display_description' ) ); ?>"
                type="checkbox"
                value="display_description"
                <?php checked( (bool) $instance['display_description'], true ); ?>
            />
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'video_url' ) ); ?>">
                <strong>Specific Video URL (Optional)</strong>
                <br />

                If a valid YouTube video URL is supplied, then display that
                specific video. If left empty, the most recent video from
                <?php echo PEDESTAL_BLOG_NAME; ?>'s YouTube channel will be used.

            </label>


            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'video_url' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'video_url' ) ); ?>"
                type="url"
                value="<?php echo esc_attr( $instance['video_url'] ); ?>"
            />
        </p>

        <p>
            <label
                for="<?php echo esc_attr( $this->get_field_id( 'display_author' ) ); ?>"
                <?php echo $display_display_author_field; ?>
            >
                <strong>Display the selected video's YouTube author name?</strong>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display_author' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'display_author' ) ); ?>"
                type="checkbox"
                value="display_author"
                <?php
                checked( (bool) $instance['display_author'], true );
                echo $display_display_author_field;
                ?>
            />
        </p>

        <?php

    }

    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['title_link'] = esc_url_raw( $new_instance['title_link'] );
        $instance['display_description'] = $new_instance['display_description'];
        $instance['display_author'] = $new_instance['display_author'];

        // Only allow YouTube video URLs
        $instance['video_url'] = '';
        if ( 'youtube' == Embed::get_embed_type_from_url( $new_instance['video_url'] ) ) {
            $instance['video_url'] = esc_url_raw( $new_instance['video_url'] );
        }

        // Flush cached video ID and data
        $url = $instance['video_url'];
        if ( ! $url ) {
            $this->youtube->flush_latest_channel_video();
            $latest_video_id = $this->youtube->get_latest_site_channel_video_id();
            $url = YouTube::get_url_from_id( $latest_video_id );
        }
        $this->youtube->flush_single_video_data( $url );

        return $instance;
    }
}
