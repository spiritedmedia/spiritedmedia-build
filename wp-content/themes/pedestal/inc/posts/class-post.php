<?php

namespace Pedestal\Posts;

use WP_Post;
use \Pedestal\Utils\Utils;

use Pedestal\Registrations\Post_Types\Types;

use Pedestal\Objects\{
    Notifications,
    Stream,
    User
};

use \Pedestal\Posts\Clusters\Geospaces\Localities\Locality;

/**
 * Base class to represent a WordPress Post
 */
abstract class Post {

    /**
     * Single post view base template
     *
     * The base template for Twig to use when rendering the single view
     * for this post.
     *
     * @var string
     */
    protected $single_base_template = 'base.twig';

    protected $data_attributes = [];

    protected $p2p_data = [];

    protected $post;

    protected static $post_type = 'post';

    public function __construct( $post ) {
        global $wp;
        if ( is_numeric( $post ) ) {
            $post = get_post( $post );
        }

        $this->post = $post;
        $this->set_data_atts();
        $this->set_p2p_data();

        // Cache these objects throughout the duration of the page request...
        if ( ! isset( $wp->pedestal_post_cache ) || ! is_array( $wp->pedestal_post_cache ) ) {
            $wp->pedestal_post_cache = [];
        }

        // Cache these author objects throughout the duration of the page request...
        if ( ! isset( $wp->pedestal_author_cache ) || ! is_array( $wp->pedestal_author_cache ) ) {
            $wp->pedestal_author_cache = [];
        }
    }

    /**
     * Get an instantiated proper object based on a post ID
     *
     * @param int|str Integer or numeric string post ID
     */
    public static function get_by_post_id( $post_id ) {
        global $wp;
        $errors = new \WP_Error;

        if ( empty( $post_id ) ) {
            return false;
        }

        if ( ! is_numeric( $post_id ) ) {
            $var_type_post_id = gettype( $post_id );
            $errors->add( 'post_id_not_int', "Expected \$post_id as numeric integer or string, got non-numeric {$var_type_post_id}." );
            trigger_error( $errors->get_error_message(), E_USER_ERROR );
            return false;
        }

        // Have we already gotten this post object?
        if ( isset( $wp->pedestal_post_cache[ $post_id ] ) && ! empty( $wp->pedestal_post_cache[ $post_id ] ) ) {
            return $wp->pedestal_post_cache[ $post_id ];
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            // Cache it!
            $wp->pedestal_post_cache[ $post_id ] = false;
            return false;
        }

        // Cache it!
        $wp->pedestal_post_cache[ $post_id ] = static::get_instance( $post );
        return $wp->pedestal_post_cache[ $post_id ];
    }

    /**
     * Get an instantiated proper object based on a post name (aka slug)
     *
     * @param  string $post_name  Name of post to search for
     * @param  array $args        Optional arguments to modify the WP_Query
     * @return object|array|bool  Post object, array of post objects, or false
     */
    public static function get_by_post_name( $post_name = '', $args = [] ) {
        $defaults = [
            'name'                   => $post_name,
            'post_type'              => 'any',
            'post_status'            => 'publish',
            'numberposts'            => 1,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];
        $args = wp_parse_args( $args, $defaults );
        $posts = Stream::get( $args );
        if ( ! is_array( $posts ) || ! isset( $posts[0] ) ) {
            return false;
        }
        if ( 1 == $args['numberposts'] ) {
            return $posts[0];
        }
        return $posts;
    }

    /**
     * Get a Post family instance from a WP_Post object
     *
     * @param  WP_Post $post WP_Post object
     * @return object|false
     * - `Post`-extending class if successful
     * - `WP_Post` if not one of our post types
     * - false if failure
     */
    public static function get_instance( $post ) {
        if ( ! $post instanceof \WP_Post ) {
            return false;
        }

        // If requested post type is a Locality, then use the Locality instance getter
        if ( 'pedestal_locality' === get_post_type( $post ) ) {
            return Locality::get_instance( $post );
        }

        if ( ! in_array( get_post_type( $post ), Types::get_pedestal_post_types() ) ) {
            return $post;
        }

        $class = Types::get_post_type_class( get_post_type( $post ) );
        if ( ! class_exists( $class ) ) {
            $errors->add( 'post_class_nonexistant', "The requested post class {$class} does not exist." );
            trigger_error( $errors->get_error_message(), E_USER_ERROR );
            return false;
        }

        return new $class( $post );
    }

    /**
     * Get the CSS classes in string form
     *
     * @return string String of classes from `get_css_classes()`
     */
    public function css_classes() {
        return implode( '  ', $this->get_css_classes() );
    }

    /**
     * Get CSS classes
     *
     * @return array
     */
    public function get_css_classes() {
        $classes = [];
        return $classes;
    }

    /**
     * Get the name of the base template for this post's single view
     *
     * @return string
     */
    public function get_single_base_template() {
        return $this->single_base_template;
    }

    /**
     * Get the Post's HTML data attributes in string format
     *
     * @return string HTML
     */
    public function get_the_data_atts() {
        if ( empty( $this->get_data_atts() ) ) {
            return;
        }
        $atts = [];
        foreach ( $this->get_data_atts() as $key => $value ) {
            $attribute = 'data-' . $key;
            if ( ! empty( $value ) ) {
                $attribute .= '="' . $value . '"';
            }
            $atts[] = $attribute;
        }
        $atts = implode( ' ', $atts );
        return $atts;
    }

    /**
     * Get the Post's HTML data attributes in an associative array
     *
     * @return array
     */
    public function get_data_atts() {
        return $this->data_attributes;
    }

    /**
     * Set up the Post's HTML data attributes
     */
    protected function set_data_atts() {
        $author_role = '';
        $author_count = count( $this->get_authors() );
        if ( $author = $this->get_single_author() ) {
            $public_role = $author->get_public_role();
            $author_role = $public_role['name'];
        }
        $this->data_attributes = [
            'post-type'    => $this->get_type(),
            'author-count' => $author_count,
            'author-role'  => $author_role,
        ];
    }

    /**
     * Get the ID for the post
     *
     * @return int
     */
    public function get_id() {
        return (int) $this->get_field( 'ID' );
    }

    /**
     * Get the filtered title for the post
     *
     * For exporting outside of the application, use `get_title()`.
     */
    public function get_the_title() {
        return apply_filters( 'the_title', $this->get_title() );
    }

    /**
     * Get the title for the post
     *
     * In most cases for title display within the application, you'll want to
     * use `get_the_title()` or `the_title()`. But this raw method is useful for
     * getting the title that's safe for exporting via email or ICS etc.
     *
     * @return string
     */
    public function get_title() {
        if ( $title = $this->get_field( 'post_title' ) ) {
            if ( 'dev' == PEDESTAL_ENV ) {
                return "[{$this->get_id()}] $title";
            } else {
                return $title;
            }
        } else {
            return false;
        }
    }

    /**
     * Set the title of the post
     *
     * @param string
     */
    public function set_title( $title ) {
        $this->set_field( 'post_title', $title );
    }

    /**
     * Get the name of the post (used in the permalink)
     */
    public function get_name() {
        return $this->get_field( 'post_name' );
    }

    /**
     * Set the name of the post (used in the permalink)
     *
     * @param string
     */
    public function set_name( $name ) {
        return $this->set_field( 'post_name', $name );
    }

    /**
     * Get this post's type plural name
     *
     * @return string
     */
    public function get_type_name_plural() {
        return Types::get_post_type_name( static::$post_type );
    }

    /**
     * Get this post's type singular name
     *
     * @return string
     */
    public function get_type_name() {
        return Types::get_post_type_name( static::$post_type, false );
    }

    /**
     * Get the type of post in a pretty format
     *
     * @return string
     */
    public function get_type() {
        return Utils::remove_name_prefix( static::$post_type );
    }

    /**
     * Get the status of the post
     *
     * @return string
     */
    public function get_status() {
        return $this->get_field( 'post_status' );
    }

    /**
     * Get the filtered excerpt for the post
     *
     * @return string
     */
    public function get_the_excerpt() {
        return apply_filters( 'the_excerpt', $this->get_excerpt() );
    }

    /**
     * Get the excerpt for the post
     *
     * @return string
     */
    public function get_excerpt() {
        return $this->get_field( 'post_excerpt' );
    }

    /**
     * Set the excerpt for the post
     *
     * @param string $excerpt
     */
    public function set_excerpt( $excerpt ) {
        $this->set_field( 'post_excerpt', $excerpt );
    }

    /**
     * Get the filtered content for the post
     *
     * @return string
     */
    public function get_the_content() {
        return apply_filters( 'the_content', $this->get_content() );
    }

    /**
     * Get the content for the post
     *
     * @return string
     */
    public function get_content() {
        return $this->get_field( 'post_content' );
    }

    /**
     * Get the filtered content for the RSS feed
     *
     * @return string
     */
    public function get_the_content_rss() {

        $type = static::$post_type;
        $type_obj = get_post_type_object( $type );
        $singular_name = strtolower( $type_obj->labels->singular_name );
        $url = esc_url( $this->get_the_permalink() );
        switch ( $type ) {
            case 'pedestal_link':
                if ( $source = $this->get_source() ) {
                    $source = esc_html( $source->get_name() );
                }
                break;

            case 'pedestal_embed':
                $url = esc_url( $this->get_embed_url() );
                $source = esc_html( $this->get_source() );
                break;

            case 'pedestal_event':
                break;

            default:
                return $this->get_the_content();
                break;
        }

        $out = '';
        if ( $source ) {
            $out .= esc_html( 'See it at ', 'pedestal' );
            $out .= '<a href="' . $url . '">' . $source . '</a>.';
        } else {
            $out .= sprintf( esc_html( 'See the original %s ', 'pedestal' ), $singular_name );
            $out .= '<a href="' . $url . '">';
            $out .= esc_html( 'here', 'pedestal' );
            $out .= '</a>.';
        }
        return $out;

    }

    /**
     * Set the content for the post
     *
     * @param string $content
     */
    public function set_content( $content ) {
        $this->set_field( 'post_content', $content );
    }

    /**
     * Get the authors with links
     *
     * @return string HTML
     */
    public function get_the_authors( $truncate = false ) {
        $pretext = esc_html__( '%s', 'pedestal' );
        $posttext = esc_html__( 'and', 'pedestal' );

        $authors = $this->get_authors();
        $authors_names_with_links = [];
        foreach ( $authors as $author ) {
            $authors_names_with_links[] = sprintf( '<a href="%s" data-ga-category="Author" data-ga-label="Name|%s">%s</a>',
                esc_url( $author->get_permalink() ),
                esc_attr( $author->get_display_name() ),
                esc_html( $author->get_display_name() )
            );

        }

        return Utils::get_byline_list( $authors_names_with_links, [ 'truncate' => $truncate ] );
    }

    /**
     * Display < 3 authors with links, > 2 authors as '{{ site.name }} Staff'
     *
     * Wrapper for `get_the_authors()` with `$truncated` set to true.
     *
     * @return string HTML
     */
    public function get_the_authors_truncated() {
        return $this->get_the_authors( true );
    }

    /**
     * Get the first author only
     *
     * @return User
     */
    public function get_single_author() {
        $authors = $this->get_authors();
        if ( 1 == count( $authors ) ) {
            return $authors[0];
        } else {
            return false;
        }
    }

    /**
     * Get the authors for the post
     *
     * @return User
     */
    public function get_authors() {
        global $wp;
        if ( isset( $wp->pedestal_author_cache[ $this->get_id() ] ) ) {
            $this->authors = $wp->pedestal_author_cache[ $this->get_id() ];
            return $wp->pedestal_author_cache[ $this->get_id() ];
        }
        $authors = get_coauthors( $this->get_id() );
        foreach ( $authors as &$author ) {
            $author = new User( $author );
        }
        $wp->pedestal_author_cache[ $this->get_id() ] = $authors;
        return $authors;
    }

    /**
     * Get the author names in a byline list format safe for RSS
     *
     * @return string
     */
    public function get_author_names_rss() {
        return Utils::get_byline_list( $this->get_author_names(), [ 'pretext' => '' ] );
    }

    /**
     * Get the avatar for the author
     *
     * If there is more than one author or if the author has no avatar defined
     * then this will default to the site logo icon
     *
     * @return string|html
     */
    public function get_author_avatar( $size = 'thumbnail' ) {

        $authors = $this->get_authors();
        if ( 1 == count( $authors ) ) {
            return sprintf( '<a href="%s" data-ga-category="Author" data-ga-label="Image|%s">%s</a>',
                esc_url( $this->get_single_author()->get_permalink() ),
                esc_attr( $this->get_single_author()->get_display_name() ),
                $this->get_single_author()->get_avatar( $size )
            );
        } elseif ( 1 < count( $authors ) ) {
            $html  = '<a href="' . esc_url( home_url( '/about/' ) ) . '" data-ga-category="Author" data-ga-label="Image|Placeholder">';
            $html .= '<i class="icon icon-logo"></i>';
            $html .= '</a>';
            return $html;
        }

        return false;

    }

    /**
     * Get the author names
     *
     * @return array
     */
    public function get_author_names() {
        $authors = $this->get_authors();
        foreach ( $authors as &$author ) {
            $author = $author->get_display_name();
        }
        return $authors;
    }

    /**
     * Get the author names in a JSON string
     *
     * @return string JSON
     */
    public function get_author_json() {
        $authors = $this->get_author_names();
        return json_encode( $authors );
    }

    public function get_edit_link() {
        return get_edit_post_link( $this->get_id() );
    }

    /**
     * Get the permalink for the post
     *
     * @param bool $preview Return the preview link?
     * @return string
     */
    public function get_permalink( $preview = false ) {
        $link = get_permalink( $this->get_id() );
        if ( $preview ) {
            $link = add_query_arg( [ 'preview' => true ], $link );
        }
        return $link;
    }

    /**
     * Get the GUID for the post
     *
     * For use solely in RSS feeds. Returned URL is escaped to make it XML safe.
     *
     * @return string Escaped URL
     */
    public function get_guid() {
        return esc_url( get_the_guid( $this->get_id() ) );
    }

    /**
     * Get the filtered permalink for the post
     *
     * @param bool $preview Return the preview link?
     * @return string Filtered permalink
     */
    public function get_the_permalink( $preview = false ) {
        $link = apply_filters( 'the_permalink', $this->get_permalink() );
        if ( $preview ) {
            $link = add_query_arg( [ 'preview' => true ], $link );
        }
        return $link;
    }

    /**
     * Get the share link for the post
     *
     * @return string
     */
    public function get_share_link() {
        return wp_get_shortlink( $this->get_id(), 'post' );
    }

    /**
     * Get the modified date for the post
     *
     * @param string $format
     * @return string
     */
    public function get_modified_date( $format = 'U' ) {
        return date( $format, strtotime( $this->get_field( 'post_modified' ) ) );
    }

    /**
     * Set the modified date for the post
     *
     * @param string
     */
    public function set_modified_date( $post_modified ) {
        $this->set_field( 'post_modified', date( 'Y-m-d H:i:s', strtotime( $post_modified ) ) );
    }

    /**
     * Get the modified date for the post
     *
     * @param string $format
     * @return mixed
     */
    public function get_modified_date_gmt( $format = 'U' ) {
        return date( $format, strtotime( $this->get_field( 'post_modified_gmt' ) ) );
    }

    /**
     * Set the modified date for the post
     *
     * @param string
     */
    public function set_modified_date_gmt( $post_modified_gmt ) {
        $this->set_field( 'post_modified_gmt', date( 'Y-m-d H:i:s', strtotime( $post_modified_gmt ) ) );
    }

    /**
     * Get the post date gmt for the post
     *
     * @return mixed
     */
    public function get_post_date_gmt( $format = 'U' ) {
        return date( $format, strtotime( $this->get_field( 'post_date_gmt' ) ) );
    }

    /**
     * Set the post date gmt for the post
     *
     * @param string
     */
    public function set_post_date_gmt( $post_date_gmt ) {
        $this->set_field( 'post_date_gmt', date( 'Y-m-d H:i:s', strtotime( $post_date_gmt ) ) );
    }

    /**
     * Get the post modified date for the post
     *
     * @return mixed
     */
    public function get_post_modified( $format = 'U' ) {
        return date( $format, strtotime( $this->get_field( 'post_modified' ) ) );
    }

    /**
     * Set the post modified for the post
     *
     * @param string
     */
    public function set_post_modified( $post_modified ) {
        $this->set_field( 'post_modified', date( 'Y-m-d H:i:s', strtotime( $post_modified ) ) );
    }

    /**
     * Get the post date for the post
     *
     * @param string $format
     * @return mixed
     */
    public function get_post_date( $format = 'U' ) {
        return date( $format, strtotime( $this->get_field( 'post_date' ) ) );
    }

    /**
     * Set the post date for the post
     *
     * @param string
     */
    public function set_post_date( $post_date ) {
        $this->set_field( 'post_date', date( 'Y-m-d H:i:s', strtotime( $post_date ) ) );
    }

    /**
     * Get the parent id for the post
     *
     * @return int
     */
    public function get_parent_id() {
        return (int) $this->get_field( 'post_parent' );
    }

    /**
     * Set the parent id of the post
     *
     * @param int
     */
    public function set_parent_id( $parent_id ) {
        $this->set_field( 'post_parent', $parent_id );
    }

    /**
     * Get the featured image as an enclosure for RSS
     *
     * @link https://github.com/kasparsd/feed-image-enclosure
     *
     * @return string XML tag for the enclosure
     */
    public function get_featured_image_enclosure() {

        if ( ! $this->has_featured_image() ) {
            return false;
        }

        $thumbnail = image_get_intermediate_size( $this->get_featured_image_id(), 'medium' );

        if ( empty( $thumbnail ) ) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        return sprintf(
            '<enclosure url="%s" length="%s" type="%s" />',
            $thumbnail['url'],
            filesize( path_join( $upload_dir['basedir'], $thumbnail['path'] ) ),
            get_post_mime_type( $this->get_featured_image_id() )
        );

    }

    /**
     * Whether or not this post has a featured image
     *
     * @return bool
     */
    public function has_featured_image() {
        return (bool) $this->get_featured_image();;
    }

    /**
     * Get the featured image ID for the post
     *
     * @return int|false
     */
    public function get_featured_image_id() {
        return (int) $this->get_meta( '_thumbnail_id' );
    }

    /**
     * Set the featured image for the post
     *
     * @param int $featured_image_id
     */
    public function set_featured_image_id( $featured_image_id ) {
        $this->set_meta( '_thumbnail_id', (int) $featured_image_id );
    }

    /**
     * Get the featured image url for the given featured image id
     *
     * @param string $size
     * @param array $args
     * @return string|false
     */
    public function get_featured_image_url( $size = 'full', $args = [] ) {
        $attachment = $this->get_featured_image();
        if ( $attachment ) {
            return $attachment->get_url( $size, $args );
        } else {
            return '';
        }
    }

    /**
     * Get the HTML for the featured image
     *
     * @return string
     */
    public function get_featured_image_html( $size = 'full', $args = [] ) {
        $attachment = $this->get_featured_image();
        if ( $attachment && method_exists( $attachment, 'get_html' ) ) {
            return $attachment->get_html( $size, $args );
        } else {
            return '';
        }
    }

    /**
     * Get the featured image object for the post
     *
     * @return Attachment|false
     */
    public function get_featured_image() {
        $id = $this->get_featured_image_id();
        if ( ! empty( $id ) && $attachment = Attachment::get_by_post_id( (int) $id ) ) {
            return $attachment;
        } else {
            return false;
        }
    }

    /**
     * Get the lead image
     *
     * Includes caption and credit, if available.
     *
     * @return string|html
     */
    public function get_lead_image_html() {
        $attachment = $this->get_featured_image();
        $size = 'lead-image';
        if ( is_feed( 'fias' ) ) {
            $size = 'max-4-3';
        }
        $content = $this->get_featured_image_html( $size );
        $atts = [
            'attachment'             => $this->get_featured_image_id(),
            'linkto'                 => '',
            'caption'                => $attachment->get_caption(),
            'credit'                 => $attachment->get_credit(),
            'credit_link'            => $attachment->get_credit_link(),
            'omit_presentation_mode' => true,
        ];
        return Attachment::get_img_caption_html( $content, $atts );
    }

    /**
     * Get the SEO title for the post
     *
     * @return string
     */
    public function get_seo_title() {
        if ( $title = $this->get_fm_field( 'pedestal_distribution', 'seo', 'title' ) ) {
            return $title;
        } else {
            return $this->get_default_seo_title();
        }
    }

    /**
     * Get the default SEO title for the post
     *
     * @return string
     */
    public function get_default_seo_title() {
        return $this->get_title();
    }

    /**
     * Get the SEO description for the post
     *
     * @return string
     */
    public function get_seo_description() {
        if ( $description = $this->get_fm_field( 'pedestal_distribution', 'seo', 'description' ) ) {
            return $description;
        } else {
            return $this->get_default_seo_description();
        }
    }

    /**
     * Get the default SEO description for the post
     *
     * @param integer $len Length of description in characters. Defaults to 150.
     *
     * @return string
     */
    public function get_default_seo_description( $len = 150 ) {

        $description = $this->get_field( 'post_excerpt' );
        if ( $this instanceof Newsletter ) {
            $description = $this->get_newsletter_subtitle();
        }
        if ( ! $description ) {
            $content = $this->get_field( 'post_content' );
            $description = Utils::str_limit( $content, $len );
        }
        if ( ! $description ) {
            return false;
        }
        return strip_tags( $description );
    }

    /**
     * Get a given Facebook open graph tag for this post
     *
     * @param string $tag_name
     * @return string
     */
    public function get_facebook_open_graph_tag( $tag_name ) {

        switch ( $tag_name ) {

            case 'title':
                $val = $this->get_fm_field( 'pedestal_distribution', 'facebook', 'title' );
                break;

            case 'description':
                $val = $this->get_fm_field( 'pedestal_distribution', 'facebook', 'description' );
                break;

            case 'url':
                $val = $this->get_permalink();
                break;

            case 'image':
                $image_id = $this->get_fm_field( 'pedestal_distribution', 'facebook', 'image' );
                if ( $src = wp_get_attachment_image_src( $image_id, 'facebook-open-graph' ) ) {
                    $val = $src[0];
                } else {
                    $val = '';
                }
                break;

            case 'author':
                $val = [];
                foreach ( $this->get_authors() as $author ) {
                    $val[] = [
                        'name' => $author->get_display_name(),
                        'profile' => $author->get_facebook_profile_url(),
                    ];
                }
                break;

            default:
                break;
        }

        if ( ! empty( $val ) ) {
            return $val;
        } else {
            return $this->get_default_facebook_open_graph_tag( $tag_name );
        }

    }

    /**
     * Get the default Facebook Open Graph tag for this post
     *
     * @param string $tag_name
     * @return string
     */
    public function get_default_facebook_open_graph_tag( $tag_name ) {

        switch ( $tag_name ) {

            case 'title':
                $val = $this->get_title();
                break;

            case 'description':
                $val = $this->get_default_seo_description( 300 );
                if ( ! $val ) {
                    $val = get_bloginfo( 'description' );
                }
                break;

            case 'url':
                $val = $this->get_permalink();
                break;

            case 'image':
                $val = $this->get_featured_image_url( 'facebook-open-graph' );
                break;

            default:
                $val = '';
                break;
        }

        return $val;

    }

    /**
     * Get a given Twitter card tag for this post
     *
     * @param string $tag_name
     * @return string
     */
    public function get_twitter_card_tag( $tag_name ) {

        switch ( $tag_name ) {

            case 'title':
                $title = $this->get_fm_field( 'pedestal_distribution', 'twitter', 'title' );
                $val = Utils::str_limit( $title, 70 );
                break;

            case 'description':
                $description = $this->get_fm_field( 'pedestal_distribution', 'twitter', 'description' );
                $val = Utils::str_limit( $description, 200 );
                break;

            case 'url':
                $val = $this->get_permalink();
                break;

            case 'image':
                $image_id = $this->get_fm_field( 'pedestal_distribution', 'twitter', 'image' );
                if ( $src = wp_get_attachment_image_src( $image_id, 'twitter-card' ) ) {
                    $val = $src[0];
                } else {
                    $val = '';
                }
                break;

            default:
                $val = '';
                break;
        }

        if ( ! empty( $val ) ) {
            return $val;
        } else {
            return $this->get_default_twitter_card_tag( $tag_name );
        }

    }

    /**
     * Get the default Twitter card tag for this post
     *
     * @param string $tag_name
     * @return string
     */
    public function get_default_twitter_card_tag( $tag_name ) {

        switch ( $tag_name ) {

            case 'title':
                $title = $this->get_title();
                $val = Utils::str_limit( $title, 70 );
                break;

            case 'description':
                $description = $this->get_excerpt();
                $val = Utils::str_limit( $description, 70 );
                break;

            case 'url':
                $val = $this->get_permalink();
                break;

            case 'image':
                $val = $this->get_featured_image_url( 'twitter-card' );
                break;

            default:
                $val = '';
                break;
        }

        return $val;

    }

    /**
     * Get the text to use when a user shares a link on Twitter
     *
     * @return string
     */
    public function get_twitter_share_text() {

        $share_text = $this->get_fm_field( 'pedestal_distribution', 'twitter', 'share_text' );
        if ( empty( $share_text ) ) {
            $share_text = $this->get_title();
        }

        if ( strlen( $share_text ) > PEDESTAL_TWITTER_SHARE_TEXT_MAX_LENGTH ) {
            $share_text = substr( $share_text, 0, PEDESTAL_TWITTER_SHARE_TEXT_MAX_LENGTH );
        }

        return $share_text;
    }

    /**
     * Get the summary text to use when a user shares a link on LinkedIn
     *
     * @return string
     */
    public function get_linkedin_summary() {

        $summary = $this->get_fm_field( 'pedestal_distribution', 'linkedin', 'summary' );
        if ( empty( $summary ) ) {
            $summary = '';
        }

        return $summary;
    }

    /**
     * Get the title text to use when a user shares a link on LinkedIn
     *
     * @return string
     */
    public function get_linkedin_title() {

        $title = $this->get_fm_field( 'pedestal_distribution', 'linkedin', 'title' );
        if ( empty( $title ) ) {
            $title = $this->get_title();
        }

        return $title;
    }

    /**
     * Get the link to share something on Facebook
     *
     * @return string
     */
    public function get_facebook_share_link() {
        return 'https://www.facebook.com/sharer/sharer.php?u=' . rawurldecode( $this->get_share_link() );
    }

    /**
     * Get the link to share something on Twitter
     *
     * @return string
     */
    public function get_twitter_share_link() {
        $share_link = rawurldecode( $this->get_share_link() );
        $text = rawurlencode( $this->get_twitter_share_text() );
        $twitter_args = [
            'url'        => $share_link,
            'text'       => $text,
            'via'        => PEDESTAL_TWITTER_USERNAME,
        ];
        return add_query_arg( $twitter_args, 'https://twitter.com/share' );
    }

    /**
     * Get the link to share something on LinkedIn
     *
     * @return string
     */
    public function get_linkedin_share_link() {
        $share_link = rawurldecode( $this->get_share_link() );
        $title = rawurldecode( $this->get_linkedin_title() );
        $source = rawurlencode( get_bloginfo( 'name' ) );
        $summary = rawurlencode( $this->get_linkedin_summary() );
        $linkedin_args = [
            'mini'    => 'true',
            'url'     => $share_link,
            'title'   => $title,
            'source'  => $source,
            'summary' => $summary,
        ];
        return add_query_arg( $linkedin_args, 'http://www.linkedin.com/shareArticle' );
    }

    /**
     * Get the info to share something via email
     *
     * @return string
     */
    public function get_mailto_share_string() {
        $link = $this->get_permalink();
        $excerpt = $this->get_excerpt();
        if ( strlen( $excerpt ) ) {
            $excerpt = '%0A%0A&ldquo;' . rawurlencode( $excerpt ) . '&rdquo;';
        }
        $body = 'Yo, %0A%0AYou should see this link on BillyPenn.com: ' . $link . $excerpt;
        $subject = rawurlencode( $this->get_title() );
        return "mailto:?subject=$subject&body=$body";
    }

    /**
     * Get the post's Parsely data
     *
     * @return string JSON-LD Parsely data
     */
    public function get_parsely_data() {
        $parsely = new \Pedestal\Objects\Parsely( [
            'scope' => 'post',
            'id'    => $this->get_id(),
        ] );
        return $parsely->get_data();
    }

    /**
     * Create a new instance
     *
     * @return Post|false
     */
    public static function create( $args = [] ) {

        $defaults = [
            'post_type'     => static::$post_type,
            'post_status'   => 'draft',
            'post_author'   => get_current_user_id(),
        ];
        $args = array_merge( $defaults, $args );
        add_filter( 'wp_insert_post_empty_content', '__return_false' );
        $post_id = wp_insert_post( $args );
        remove_filter( 'wp_insert_post_empty_content', '__return_false' );
        if ( ! $post_id ) {
            return false;
        }

        $class = get_called_class();

        return new $class( $post_id );
    }

    /**
     * Maybe resize image source if Jetpack is available
     *
     * @param array $src
     * @param array $args
     * @return array
     */
    protected function maybe_resize_image_src( $src, $args ) {

        if ( ! $src || empty( $args['height'] ) || empty( $args['width'] ) || ! Utils::is_photon_available() ) {
            return $src;
        }

        $width = (int) $args['width'];
        $height = (int) $args['height'];

        if ( 9999 == $height ) {
            $transform_args = [ 'w' => $width ];
        } elseif ( 9999 == $width ) {
            $transform_args = [ 'h' => $height ];
        } else {
            $transform_args = [ 'resize' => $width . ',' . $height ];
        }

        $src = jetpack_photon_url( $src, $transform_args );

        return $src;
    }

    /**
     * Get the version of Pedestal at the time of publishing
     *
     * @return string Pedestal SemVer string
     */
    public function get_published_pedestal_ver() {
        return $this->get_meta( 'published_pedestal_ver' );
    }

    /**
     * Save the current Pedestal version to post meta
     */
    public function set_published_pedestal_ver() {
        $this->set_meta( 'published_pedestal_ver', PEDESTAL_VERSION );
    }

    /**
     * Get a field from the post object
     *
     * @param string $key
     * @return mixed
     */
    protected function get_field( $key ) {
        return $this->post->$key;
    }

    /**
     * Set a field for the post object
     *
     * @param string $key
     * @param mixed $value
     */
    protected function set_field( $key, $value ) {
        global $wp, $wpdb;
        $wpdb->update( $wpdb->posts, [ $key => $value ], [ 'ID' => $this->get_id() ] );
        // Clear our own internal post cache for this given object for the duration of the request
        if ( isset( $wp->pedestal_post_cache[ $this->get_id() ] ) ) {
            unset( $wp->pedestal_post_cache[ $this->get_id() ] );
        }
        clean_post_cache( $this->get_id() );
        $this->post = get_post( $this->get_id() );
    }

    /**
     * Get a Fieldmanager field
     *
     * @param string
     * @return mixed
     */
    protected function get_fm_field() {
        $fields = func_get_args();
        $parent = array_shift( $fields );

        $meta = $this->get_meta( $parent );
        foreach ( $fields as $key ) {
            if ( isset( $meta[ $key ] ) ) {
                $meta = $meta[ $key ];
            } else {
                return false;
            }
        }

        if ( is_string( $meta ) ) {
            $meta = trim( $meta );
        }

        return $meta;
    }

    /**
     * Get a meta value for a post
     *
     * @param string $key
     * @param bool   $single Return a single value?
     *
     * @return mixed
     */
    public function get_meta( $key, $single = true ) {
        return get_post_meta( $this->get_id(), $key, $single );
    }

    /**
     * Update a meta value for a post
     *
     * If the key already exists, its value will be updated.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set_meta( $key, $value ) {
        update_post_meta( $this->get_id(), $key, $value );
    }

    /**
     * Add a new meta key and value for a post
     *
     * A new key will be created even if it's not unique.
     *
     * @param string $key
     * @param mixed $value
     */
    public function add_meta( $key, $value ) {
        add_post_meta( $this->get_id(), $key, $value );
    }

    /**
     * Delete a meta value for a post
     *
     * @param  string $key   Meta key
     * @param  string $value Optional meta value to match
     * @return void
     */
    public function delete_meta( string $key, $value = '' ) {
        delete_post_meta( $this->get_id(), $key, $value );
    }

    /**
     * Get the taxonomy terms for a post
     *
     * @param string $taxonomy
     * @return array|false
     */
    public function get_taxonomy_terms( $taxonomy ) {

        $terms = get_the_terms( $this->get_id(), $taxonomy );
        if ( $terms && ! is_wp_error( $terms ) ) {
            return $terms;
        } else {
            return false;
        }

    }

    /**
     * Set taxonomy terms for a post
     *
     * @param string $taxonomy
     * @param array $terms Array of term slugs or term objects
     */
    public function set_taxonomy_terms( $taxonomy, $terms ) {

        if ( ! is_array( $terms ) ) {
            $terms = [ $terms ];
        }

        // Maybe this was an array of objects
        $first_term = $terms[0];
        if ( is_object( $first_term ) ) {
            $terms = wp_list_pluck( $terms, 'slug' );
        }

        // Terms need to exist in order to use wp_set_object_terms(), sadly
        foreach ( $terms as $term ) {
            if ( ! get_term_by( 'slug', $term, $taxonomy ) ) {
                // @TODO Create new terms if they don't exist, but that would
                // require a more complex input to support both slugs and names
                continue;
            }
        }

        wp_set_object_terms( $this->get_id(), array_map( 'sanitize_title', $terms ), $taxonomy );
    }

    /**
     * Determine whether a post type is an entity
     *
     * @param string The post type to check. Defaults to the current post's type
     *
     * @return boolean
     */
    public function is_entity() {
        return Types::is_entity( static::$post_type );
    }

    /**
     * Determine whether a post type is an cluster
     *
     * @param string The post type to check. Defaults to the current post's type
     *
     * @return boolean
     */
    public function is_cluster() {
        return Types::is_cluster( static::$post_type );
    }

    /**
     * Determine whether a post type is a story
     *
     * @param string The post type to check. Defaults to the current post's type
     *
     * @return boolean
     */
    public function is_story() {
        return Types::is_story( static::$post_type );
    }

    /**
     * Determine whether a post type is a Locality
     *
     * @param string The post type to check. Defaults to the current post's type
     *
     * @return boolean
     */
    public function is_locality() {
        return Types::is_locality( static::$post_type );
    }

    /**
     * Get the Post's post type name label
     *
     * @param  boolean $plural   Whether to return the plural name or singular.
     * @param  boolean $sanitize Whether to return a sanitized name
     * @return string          The label name of the post type
     */
    public function get_post_type_name( $plural = true, $sanitize = false ) {
        return Types::get_post_type_name( static::$post_type, $plural, $sanitize );
    }

    /**
     * Get the post type
     *
     * @return string Post type
     */
    public function get_post_type() {
        return static::$post_type;
    }

    /**
     * Determine whether the post is hidden from the specified stream
     *
     * @param  string $stream The shortname for a stream. Can be `home`.
     * @return bool
     */
    public function is_hidden_in_stream( $stream ) {
        if ( $stream === $this->get_fm_field( 'hidden_in_stream', 0 ) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get P2P connection data
     *
     * @return array
     */
    public function get_p2p_data() {
        return $this->p2p_data;
    }

    /**
     * Setup P2P connection data if it is present
     *
     * Posts queried by the P2P function `get_connected()` will contain this
     */
    protected function set_p2p_data() {
        if ( property_exists( $this->post, 'p2p_id' ) ) {
            if ( $this->get_id() == $this->post->p2p_from ) {
                $dir = 'from';
                $connected_id = $this->post->p2p_to;
            } elseif ( $this->get_id() == $this->post->p2p_to ) {
                $dir = 'to';
                $connected_id = $this->post->p2p_from;
            } else {
                return;
            }
            $this->p2p_data = [
                'connection_id' => $this->post->p2p_id,
                'type'          => $this->post->p2p_type,
                'dir'           => $dir,
                'connected_id'  => $connected_id,
            ];
        }
    }

    /**
     * Notify Slack on post publish
     *
     * Wrapper for `notify_on_status_change()`.
     *
     * @param  array  $args Settings
     */
    public function notify_on_publish( $args = [] ) {
        $this->notify_on_status_change( 'publish', $args );
    }

    /**
     * Notify Slack on post status change
     *
     * @param  string $new_status Name of the new status
     * @param  array  $args       Settings
     * @uses \Pedestal\Notifications::send()
     */
    public function notify_on_status_change( $new_status, $args = [] ) {
        $args = wp_parse_args( $args, [
            'channel' => PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL,
        ] );

        $msg = '';
        switch ( $new_status ) {
            case 'publish':
                $msg = '*New ' . $this->get_type() . ' published:* “' . $this->get_title() . '” ' . $this->get_permalink();
                break;
        }

        if ( ! empty( $msg ) ) {
            $notifier = new Notifications;
            $notifier->send( $msg, $args );
        }
    }

    /**
     * Can the currently logged in user edit this post?
     *
     * @return boolean
     */
    public function is_editable_by_current_user() {
        if ( current_user_can( 'edit_post', $this->get_id() ) ) {
            return true;
        }
        return false;
    }
}

/**
 * Editorial Content
 */
trait EditorialContent {

    /**
     * Generated footnotes for the post
     *
     * @var array
     */
    protected $footnotes_generated_notes = [];

    /**
     * Generated footnotes start number
     *
     * @var integer
     */
    protected $footnotes_generated_start = 1;

    /**
     * Setup data attributes
     */
    public function set_data_atts() {
        parent::set_data_atts();
        $atts = parent::get_data_atts();
        $new_atts = [
            'editorial-content' => '',
        ];
        $this->data_attributes = array_merge( $atts, $new_atts );
    }

    /**
     * Get the name of the icon for this entity's source
     *
     * @return string
     */
    public function get_source_icon_name() {
        return 'bp-logo-head';
    }

    /**
     * Get the filtered footnotes for this post
     *
     * Includes the generated footnotes from the main post content.
     *
     * @return string
     */
    public function get_the_footnotes() {
        $footnotes = apply_filters( 'the_content', $this->get_footnotes() );

        /**
         * Filter the output of the footnotes field
         *
         * @param string $footnotes Footnotes field content
         * @param int    $post_id   Post ID
         */
        $footnotes = apply_filters( 'the_footnotes', $footnotes, $this->get_id() );
        return $footnotes;
    }

    /**
     * Get the footnotes for this post
     *
     * Does not include the generated footnotes from the main post content.
     *
     * @return string
     */
    public function get_footnotes() {
        return $this->get_meta( 'footnotes' );
    }

    /**
     * Get the start offset of the generated footnotes
     *
     * @return int Start offset number. Usually is 1.
     */
    public function get_footnotes_generated_start() {
        return $this->footnotes_generated_start;
    }

    /**
     * Get the generated footnotes array
     *
     * @return array Generated footnotes
     */
    public function get_footnotes_generated_notes() {
        return $this->footnotes_generated_notes;
    }

    /**
     * Set up the generated footnotes
     *
     * @param array $notes Notes
     * @param int   $start Start offset
     */
    public function set_footnotes_generated( array $notes, int $start ) {
        if ( empty( $notes ) ) {
            return $notes;
        }
        $this->footnotes_generated_notes = $notes;
        $this->footnotes_generated_start = $start;
    }

    /**
     * Are ads in Instant Articles placed automatically?
     *
     * @return string true|false
     */
    public function fias_use_automatic_ad_placement() {
        if ( empty( $this->fias_use_automatic_ad_placement ) ) {
            return 'true';
        }
        return $this->fias_use_automatic_ad_placement;
    }

    /**
     * Nasty hack to get a live canonical URL for FIAs
     *
     * Replaces the home URL with the live site URL constant.
     *
     * Allows us to test FIAs on a site with a different URL than the URL
     * registered for the Facebook Page.
     *
     * @return string Live canonical URL
     */
    public function get_fias_canonical_url() {
        $url = $this->get_permalink();
        return str_replace( home_url( '/' ), $site_config['site_live_domain'], $url );
    }
}

/**
 * Methods for emailable content
 *
 * Currently, due to the differences in the way Cluster Following and
 * Newsletter and Breaking News following work, the Emailable trait only
 * applies to the latter.
 *
 * Once the migration to MailChimp email sending is completed, the follow
 * functionality should be unified across all post types.
 */
trait Emailable {

    /**
     * Get the time this email was sent
     *
     * @return mixed
     */
    public function get_sent_date( $format = 'U' ) {
        if ( $sent_date = $this->get_meta( 'sent_date' ) ) {
            return date( $format, strtotime( date( 'Y-m-d H:i:s', $sent_date ) ) );
        } else {
            return false;
        }
    }

    /**
     * Set the sent email flag
     *
     * @param string $email_type
     */
    public function set_sent_flag( $email_type = 'unknown' ) {
        $this->set_meta( 'sent_email', $email_type );
    }

    /**
     * Set the time this email was sent
     */
    public function set_sent_date( $time = 'false' ) {
        if ( ! $time ) {
            $time = time();
        }
        $this->set_meta( 'sent_date', $time );
    }
}
