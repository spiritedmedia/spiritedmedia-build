<?php

namespace Pedestal;

use Pedestal\Utils\Utils;
use Pedestal\Frontend;
use Pedestal\Posts\Post;
use Pedestal\Registrations\Post_Types\Types;

class Schema_Metadata {

    /**
     * @var array
     */
    private $data = [];

    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();

            $instance->data = [
                '@context'            => 'http://schema.org',
                '@type'               => 'WebPage',
                'url'                 => esc_url( home_url( Utils::get_request_uri() ) ),
                'image'               => '',
                'isAccessibleForFree' => true,
                'publisher'           => $instance->get_site_org_schema(),
                'copyrightYear'       => date( 'Y' ),
            ];

            $instance->set_data();
        }
        return $instance;
    }

    /**
     * Set up data based on page context
     */
    private function set_data() {
        $maybe_id = get_queried_object_id();

        if ( is_singular() ) {
            $data = $this->prepare_post_data( $maybe_id );
        } elseif ( is_author() ) {
            // @TODO Currently broken, see https://github.com/spiritedmedia/spiritedmedia/issues/3087
            $user = new User( $maybe_id );
            $name = Utils::sanitize_string_for_json( 'Author — ' . $user->get_display_name() );
            $data = [
                'name' => $name,
                'url'  => $user->get_permalink(),
            ];
        } elseif ( is_archive() ) {
            $name = Utils::sanitize_string_for_json( Frontend::get_archive_title() );
            $data = [
                'name' => $name,
            ];
        } else {
            $data = [
                'name' => PEDESTAL_BLOG_NAME,
                'url'  => home_url(),
            ];
        }

        $data = wp_parse_args( $data, $this->data );

        if ( ! empty( $data['url'] ) ) {
            $data['mainEntityOfPage'] = $data['url'];
        }

        // Set these last in case the values have ended up empty after everything
        if ( empty( $data['description'] ) ) {
            $data['description'] = PEDESTAL_BLOG_TAGLINE;
        }
        if ( empty( $data['author'] ) ) {
            $data['author'] = $this->get_site_org_schema();
        }
        if ( empty( $data['creator'] ) ) {
            $data['creator'] = $this->get_site_org_schema();
        }

        $this->data = $data;
    }

    /**
     * Get the data in JSON-LD format
     *
     * @return string A JSON string surrounded by JSON-LD script tags
     */
    public function get_markup() {
        $output  = '<script type="application/ld+json">';
        $output .= json_encode( $this->data, JSON_UNESCAPED_SLASHES );
        $output .= '</script>';
        return $output;
    }

    /**
     * Prepare the metadata for an original post
     *
     * @param int $id Post ID
     * @return array
     */
    private function prepare_post_data( $id ) {
        $post = Post::get( $id );
        if ( ! Types::is_post( $post ) ) {
            return [];
        }

        $post_title = Utils::sanitize_string_for_json( $post->get_title() );
        $data       = [
            'name'          => $post_title,
            'url'           => $post->get_permalink(),
            'description'   => Utils::sanitize_string_for_json( $post->get_seo_description() ),
            'image'         => $post->get_featured_image_url( 'large' ),
            'thumbnailUrl'  => $post->get_featured_image_url( 'medium-square' ),
            'dateCreated'   => $post->get_post_date_gmt( 'c' ),
            'datePublished' => $post->get_post_date_gmt( 'c' ),
            'dateModified'  => $post->get_modified_date_gmt( 'c' ),
            'copyrightYear' => $post->get_post_date( 'Y' ),
        ];

        if ( $post->is_entity() ) {
            $clusters = $post->get_clusters( [
                'include_stories' => true,
            ] );
            if ( ! empty( $clusters ) ) {
                $data['keywords'] = [];
                foreach ( $clusters as $cluster ) {
                    $data['keywords'][] = $cluster->get_title();
                }
            }
        }

        if ( ! $post->is_original() ) {
            return $data;
        }

        $author_schema_data = $post->get_authors_schema_data();
        $data               = array_merge( $data, [
            '@type'    => 'NewsArticle',
            'headline' => $post_title,
            'author'   => $author_schema_data,
            'creator'  => $author_schema_data,
        ] );

        $category = $post->get_category_term();
        if ( ! empty( $category->name ) ) {
            $data['articleSection'] = $category->name;
        }

        return $data;
    }

    /**
     * Get an Organization schema about this site
     *
     * @return array
     */
    protected function get_site_org_schema() {
        return [
            '@type'            => 'Organization',
            'name'             => PEDESTAL_BLOG_NAME,
            'description'      => PEDESTAL_BLOG_TAGLINE,
            'email'            => PEDESTAL_EMAIL_CONTACT,
            'url'              => site_url(),
            'mainEntityOfPage' => site_url(),
            'logo'             => $this->get_site_logo_image_object(),
        ];
    }

    /**
     * Get Schema.org ImageObject data for the site logo
     *
     * @return array
     */
    protected function get_site_logo_image_object() {
        $logo_rel_path  = '/assets/images/logos/logo-60h.png';
        $logo_file_path = get_stylesheet_directory() . $logo_rel_path;
        $logo_url       = get_stylesheet_directory_uri() . $logo_rel_path;

        if ( ! exif_imagetype( $logo_file_path ) ) {
            return [];
        }

        $data = [
            '@type' => 'ImageObject',
            'url'   => $logo_url,
        ];

        $size_data = getimagesize( $logo_file_path );
        if ( ! empty( $size_data ) ) {
            $data = array_merge( $data, [
                'width'  => $size_data[0],
                'height' => $size_data[1],
            ] );
        }

        return $data;
    }
}
